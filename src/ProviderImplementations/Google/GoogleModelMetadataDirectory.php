<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\Google;

use RuntimeException;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\AbstractOpenAiCompatibleModelMetadataDirectory;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Class for the Google model metadata directory.
 *
 * @since n.e.x.t
 */
class GoogleModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    /**
     * @inheritDoc
     */
    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        /*
         * Since we're calling the primary Google API models endpoint here, we need to use the Google specific API key
         * authentication class.
         */
        $requestAuthentication = parent::getRequestAuthentication();
        if (!$requestAuthentication instanceof ApiKeyRequestAuthentication) {
            return $requestAuthentication;
        }
        return new GoogleApiKeyRequestAuthentication($requestAuthentication->getApiKey());
    }

    /**
     * @inheritDoc
     */
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        /*
         * We don't call Google's OpenAI compatible models endpoint here because it provides fewer details about the
         * models than the primary models endpoint.
         * For Google's models endpoint, set pageSize=1000 which is the maximum page size.
         * This allows us to retrieve all models in one go.
         */
        if ($path === 'models' && $data === null) {
            $data = ['pageSize' => 1000];
        }
        return new Request(
            $method,
            GoogleProvider::BASE_URI . '/' . ltrim($path, '/'),
            $headers,
            $data
        );
    }

    /**
     * @inheritDoc
     */
    protected function parseResponseToModelMetadataList(Response $response): array
    {
        $responseData = $response->getData();
        if (!isset($responseData['models']) || !$responseData['models']) {
            throw new RuntimeException(
                'Unexpected API response: Missing the models key.'
            );
        }

        $geminiCapabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
        ];
        $geminiLegacyOptions = [
            new SupportedOption(ModelConfig::KEY_SYSTEM_INSTRUCTION),
            new SupportedOption(ModelConfig::KEY_CANDIDATE_COUNT),
            new SupportedOption(ModelConfig::KEY_MAX_TOKENS),
            new SupportedOption(ModelConfig::KEY_TEMPERATURE),
            new SupportedOption(ModelConfig::KEY_TOP_P),
            new SupportedOption(ModelConfig::KEY_STOP_SEQUENCES),
            new SupportedOption(ModelConfig::KEY_PRESENCE_PENALTY),
            new SupportedOption(ModelConfig::KEY_FREQUENCY_PENALTY),
            new SupportedOption(ModelConfig::KEY_LOGPROBS),
            new SupportedOption(ModelConfig::KEY_TOP_LOGPROBS),
            new SupportedOption(ModelConfig::KEY_OUTPUT_MIME_TYPE, ['text/plain', 'application/json']),
            new SupportedOption(ModelConfig::KEY_OUTPUT_SCHEMA),
            new SupportedOption(ModelConfig::KEY_FUNCTION_DECLARATIONS),
        ];
        $geminiOptions = $geminiLegacyOptions + [
            new SupportedOption(
                ModelConfig::KEY_INPUT_MODALITIES,
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                    [ModalityEnum::text(), ModalityEnum::image(), ModalityEnum::audio()],
                ]
            ),
        ];
        $geminiWebSearchOptions = $geminiOptions + [
            new SupportedOption(ModelConfig::KEY_WEB_SEARCH),
        ];
        $geminiMultimodalImageOutputOptions = $geminiOptions + [
            new SupportedOption(
                ModelConfig::KEY_OUTPUT_MODALITIES,
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                ]
            ),
        ];
        $imagenCapabilities = [
            CapabilityEnum::imageGeneration(),
        ];
        $imagenOptions = [
            new SupportedOption(ModelConfig::KEY_CANDIDATE_COUNT),
            new SupportedOption(ModelConfig::KEY_OUTPUT_MIME_TYPE, ['image/png', 'image/jpeg', 'image/webp']),
            new SupportedOption(ModelConfig::KEY_OUTPUT_FILE_TYPE, [FileTypeEnum::inline()]),
            new SupportedOption(ModelConfig::KEY_OUTPUT_MEDIA_ORIENTATION, [
                MediaOrientationEnum::square(),
                MediaOrientationEnum::landscape(),
                MediaOrientationEnum::portrait(),
            ]),
            new SupportedOption(ModelConfig::KEY_OUTPUT_MEDIA_ASPECT_RATIO, ['1:1', '16:9', '4:3', '9:16', '3:4']),
        ];

        /** @var array<string, array<string, mixed>> $modelsData */
        $modelsData = (array) $responseData['models'];

        return array_values(
            array_map(
                static function (array $modelData) use (
                    $geminiCapabilities,
                    $geminiLegacyOptions,
                    $geminiOptions,
                    $geminiWebSearchOptions,
                    $geminiMultimodalImageOutputOptions,
                    $imagenCapabilities,
                    $imagenOptions,
                ): ModelMetadata {
                    /** @var string $modelId */
                    $modelId = $modelData['baseModelId'] ?? $modelData['name'];
                    if (str_starts_with($modelId, 'models/')) {
                        $modelId = substr($modelId, 7);
                    }
                    if (
                        isset($modelData['supportedGenerationMethods']) &&
                        in_array('generateContent', $modelData['supportedGenerationMethods'], true)
                    ) {
                        $modelCaps = $geminiCapabilities;
                        if (
                            str_starts_with($modelId, 'gemini-1.0') ||
                            str_starts_with($modelId, 'gemini-pro') // 'gemini-pro' without version refers to 1.0.
                        ) {
                            $modelOptions = $geminiLegacyOptions;
                        } else {
                            if (
                                // Web search is supported by Gemini 2.0 and newer.
                                str_starts_with($modelId, 'gemini-') &&
                                ! str_starts_with($modelId, 'gemini-1.5-')
                            ) {
                                $modelOptions = $geminiWebSearchOptions;
                            } elseif (
                                // New multimodal output model for image generation.
                                str_contains($modelId, 'image-generation') ||
                                str_starts_with($modelId, 'gemini-2.0-flash-exp')
                            ) {
                                $modelOptions = $geminiMultimodalImageOutputOptions;
                            } else {
                                $modelOptions = $geminiOptions;
                            }
                        }
                    } elseif (
                        isset($modelData['supportedGenerationMethods']) &&
                        in_array('predict', $modelData['supportedGenerationMethods'], true)
                    ) {
                        $modelCaps = $imagenCapabilities;
                        $modelOptions = $imagenOptions;
                    } else {
                        $modelCaps = [];
                        $modelOptions = [];
                    }

                    return new ModelMetadata(
                        $modelId,
                        $modelData['displayName'] ?? $modelId,
                        $modelCaps,
                        $modelOptions
                    );
                },
                $modelsData
            )
        );
    }
}

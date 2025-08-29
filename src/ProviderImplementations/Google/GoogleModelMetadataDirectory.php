<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\Google;

use RuntimeException;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

/**
 * Class for the Google model metadata directory.
 *
 * @since 0.1.0
 *
 * @phpstan-type ModelsResponseData array{
 *     models: list<array{
 *         baseModelId?: string,
 *         name: string,
 *         supportedGenerationMethods?: list<string>,
 *         displayName?: string
 *     }>
 * }
 */
class GoogleModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
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
     * {@inheritDoc}
     *
     * @since 0.1.0
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
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected function parseResponseToModelMetadataList(Response $response): array
    {
        /** @var ModelsResponseData $responseData */
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
        $geminiBaseOptions = [
            new SupportedOption(OptionEnum::systemInstruction()),
            new SupportedOption(OptionEnum::candidateCount()),
            new SupportedOption(OptionEnum::maxTokens()),
            new SupportedOption(OptionEnum::temperature()),
            new SupportedOption(OptionEnum::topP()),
            new SupportedOption(OptionEnum::stopSequences()),
            new SupportedOption(OptionEnum::presencePenalty()),
            new SupportedOption(OptionEnum::frequencyPenalty()),
            new SupportedOption(OptionEnum::logprobs()),
            new SupportedOption(OptionEnum::topLogprobs()),
            new SupportedOption(OptionEnum::outputMimeType(), ['text/plain', 'application/json']),
            new SupportedOption(OptionEnum::outputSchema()),
            new SupportedOption(OptionEnum::functionDeclarations()),
            new SupportedOption(OptionEnum::customOptions()),
        ];
        $geminiLegacyOptions = array_merge($geminiBaseOptions, [
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
        ]);
        $geminiOptions = array_merge($geminiBaseOptions, [
            new SupportedOption(
                OptionEnum::inputModalities(),
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                    [ModalityEnum::text(), ModalityEnum::image(), ModalityEnum::audio()],
                ]
            ),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
        ]);
        $geminiWebSearchOptions = array_merge($geminiOptions, [
            new SupportedOption(OptionEnum::webSearch()),
        ]);
        $geminiMultimodalImageOutputOptions = array_merge($geminiBaseOptions, [
            new SupportedOption(
                OptionEnum::inputModalities(),
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                    [ModalityEnum::text(), ModalityEnum::image(), ModalityEnum::audio()],
                ]
            ),
            new SupportedOption(
                OptionEnum::outputModalities(),
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                ]
            ),
        ]);
        $imagenCapabilities = [
            CapabilityEnum::imageGeneration(),
        ];
        $imagenOptions = [
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::image()]]),
            new SupportedOption(OptionEnum::candidateCount()),
            new SupportedOption(OptionEnum::outputMimeType(), ['image/png', 'image/jpeg', 'image/webp']),
            new SupportedOption(OptionEnum::outputFileType(), [FileTypeEnum::inline()]),
            new SupportedOption(OptionEnum::outputMediaOrientation(), [
                MediaOrientationEnum::square(),
                // The following orientations are normally supported, but not when using the OpenAI compatible endpoint.
                // MediaOrientationEnum::landscape(),
                // MediaOrientationEnum::portrait(),
            ]),
            // Aspect ratio is normally supported, but not when using the OpenAI compatible endpoint.
            // new SupportedOption(OptionEnum::outputMediaAspectRatio(), ['1:1', '16:9', '4:3', '9:16', '3:4']),
            new SupportedOption(OptionEnum::customOptions()),
        ];

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
                    $imagenOptions
                ): ModelMetadata {
                    $modelId = $modelData['baseModelId'] ?? $modelData['name'];
                    if (str_starts_with($modelId, 'models/')) {
                        $modelId = substr($modelId, 7);
                    }
                    if (
                        isset($modelData['supportedGenerationMethods']) &&
                        is_array($modelData['supportedGenerationMethods']) &&
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
                        is_array($modelData['supportedGenerationMethods']) &&
                        in_array('predict', $modelData['supportedGenerationMethods'], true)
                    ) {
                        $modelCaps = $imagenCapabilities;
                        $modelOptions = $imagenOptions;
                    } else {
                        $modelCaps = [];
                        $modelOptions = [];
                    }

                    $modelName = $modelData['displayName'] ?? $modelId;

                    return new ModelMetadata(
                        $modelId,
                        $modelName,
                        $modelCaps,
                        $modelOptions
                    );
                },
                $modelsData
            )
        );
    }
}

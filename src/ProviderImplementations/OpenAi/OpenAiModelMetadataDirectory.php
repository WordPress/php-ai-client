<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

use RuntimeException;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\AbstractOpenAiCompatibleModelMetadataDirectory;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Class for the OpenAI model metadata directory.
 *
 * @since n.e.x.t
 */
class OpenAiModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    /**
     * @inheritDoc
     */
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        return new Request(
            $method,
            OpenAiProvider::BASE_URI . '/' . ltrim($path, '/'),
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
        if (!isset($responseData['data']) || !$responseData['data']) {
            throw new RuntimeException(
                'Unexpected API response: Missing the data key.'
            );
        }

        // Unfortunately, the OpenAI API does not return model capabilities, so we have to hardcode them here.
        $gptCapabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
        ];
        $gptOptions = [
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
            new SupportedOption(ModelConfig::KEY_CUSTOM_OPTIONS),
        ];
        $gptMultimodalInputOptions = $gptOptions + [
            new SupportedOption(
                ModelConfig::KEY_INPUT_MODALITIES,
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                    [ModalityEnum::text(), ModalityEnum::image(), ModalityEnum::audio()],
                ]
            ),
        ];
        $gptMultimodalSpeechOutputOptions = $gptMultimodalInputOptions + [
            new SupportedOption(
                ModelConfig::KEY_OUTPUT_MODALITIES,
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::audio()],
                ]
            ),
        ];
        $imageCapabilities = [
            CapabilityEnum::imageGeneration(),
        ];
        $dalleImageOptions = [
            new SupportedOption(ModelConfig::KEY_CANDIDATE_COUNT),
            new SupportedOption(ModelConfig::KEY_OUTPUT_MIME_TYPE, ['image/png']),
            new SupportedOption(ModelConfig::KEY_OUTPUT_FILE_TYPE, [FileTypeEnum::inline(), FileTypeEnum::remote()]),
            new SupportedOption(ModelConfig::KEY_OUTPUT_MEDIA_ORIENTATION, [
                MediaOrientationEnum::square(),
                MediaOrientationEnum::landscape(),
                MediaOrientationEnum::portrait(),
            ]),
            new SupportedOption(ModelConfig::KEY_OUTPUT_MEDIA_ASPECT_RATIO, ['1:1', '7:4', '4:7']),
            new SupportedOption(ModelConfig::KEY_CUSTOM_OPTIONS),
        ];
        $gptImageOptions = [
            new SupportedOption(ModelConfig::KEY_CANDIDATE_COUNT),
            new SupportedOption(ModelConfig::KEY_OUTPUT_MIME_TYPE, ['image/png', 'image/jpeg', 'image/webp']),
            new SupportedOption(ModelConfig::KEY_OUTPUT_FILE_TYPE, [FileTypeEnum::inline()]),
            new SupportedOption(ModelConfig::KEY_OUTPUT_MEDIA_ORIENTATION, [
                MediaOrientationEnum::square(),
                MediaOrientationEnum::landscape(),
                MediaOrientationEnum::portrait(),
            ]),
            new SupportedOption(ModelConfig::KEY_OUTPUT_MEDIA_ASPECT_RATIO, ['1:1', '3:2', '2:3']),
            new SupportedOption(ModelConfig::KEY_CUSTOM_OPTIONS),
        ];
        $ttsCapabilities = [
            CapabilityEnum::textToSpeechConversion(),
        ];
        $ttsOptions = [
            new SupportedOption(ModelConfig::KEY_OUTPUT_MIME_TYPE, ['audio/mpeg', 'audio/ogg', 'audio/wav']),
            new SupportedOption(ModelConfig::KEY_OUTPUT_SPEECH_VOICE),
            new SupportedOption(ModelConfig::KEY_CUSTOM_OPTIONS),
        ];

        /** @var array<string, array<string, mixed>> $modelsData */
        $modelsData = (array) $responseData['data'];

        return array_values(
            array_map(
                static function (array $modelData) use (
                    $gptCapabilities,
                    $gptOptions,
                    $gptMultimodalInputOptions,
                    $gptMultimodalSpeechOutputOptions,
                    $imageCapabilities,
                    $dalleImageOptions,
                    $gptImageOptions,
                    $ttsCapabilities,
                    $ttsOptions,
                ): ModelMetadata {
                    /** @var string $modelId */
                    $modelId = $modelData['id'];
                    if (
                        str_starts_with($modelId, 'dall-e-') ||
                        str_starts_with($modelId, 'gpt-image-')
                    ) {
                        $modelCaps = $imageCapabilities;
                        if (str_starts_with($modelId, 'gpt-image-')) {
                            $modelOptions = $gptImageOptions;
                        } else {
                            $modelOptions = $dalleImageOptions;
                        }
                    } elseif (
                        str_starts_with($modelId, 'tts-') ||
                        str_contains($modelId, '-tts')
                    ) {
                        $modelCaps = $ttsCapabilities;
                        $modelOptions = $ttsOptions;
                    } elseif (
                        (str_starts_with($modelId, 'gpt-') || str_starts_with($modelId, 'o1-'))
                        && !str_contains($modelId, '-instruct')
                        && !str_contains($modelId, '-realtime')
                    ) {
                        if (str_starts_with($modelId, 'gpt-4o')) {
                            $modelCaps = $gptCapabilities;
                            $modelOptions = $gptMultimodalInputOptions;
                            // New multimodal output model for audio generation.
                            if (str_contains($modelId, '-audio')) {
                                $modelOptions = $gptMultimodalSpeechOutputOptions;
                            }
                        } elseif (!str_contains($modelId, '-audio')) {
                            $modelCaps = $gptCapabilities;
                            $modelOptions = $gptOptions;
                        } else {
                            $modelCaps = [];
                            $modelOptions = [];
                        }
                    } else {
                        $modelCaps = [];
                        $modelOptions = [];
                    }

                    return new ModelMetadata(
                        $modelId,
                        $modelId, // The OpenAI API does not return a display name.
                        $modelCaps,
                        $modelOptions
                    );
                },
                $modelsData
            )
        );
    }
}

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
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

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
        $gptMultimodalInputOptions = $gptOptions + [
            new SupportedOption(
                OptionEnum::inputModalities(),
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                    [ModalityEnum::text(), ModalityEnum::image(), ModalityEnum::audio()],
                ]
            ),
        ];
        $gptMultimodalSpeechOutputOptions = $gptMultimodalInputOptions + [
            new SupportedOption(
                OptionEnum::outputModalities(),
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
            new SupportedOption(OptionEnum::candidateCount()),
            new SupportedOption(OptionEnum::outputMimeType(), ['image/png']),
            new SupportedOption(OptionEnum::outputFileType(), [FileTypeEnum::inline(), FileTypeEnum::remote()]),
            new SupportedOption(OptionEnum::outputMediaOrientation(), [
                MediaOrientationEnum::square(),
                MediaOrientationEnum::landscape(),
                MediaOrientationEnum::portrait(),
            ]),
            new SupportedOption(OptionEnum::outputMediaAspectRatio(), ['1:1', '7:4', '4:7']),
        ];
        $gptImageOptions = [
            new SupportedOption(OptionEnum::candidateCount()),
            new SupportedOption(OptionEnum::outputMimeType(), ['image/png', 'image/jpeg', 'image/webp']),
            new SupportedOption(OptionEnum::outputFileType(), [FileTypeEnum::inline()]),
            new SupportedOption(OptionEnum::outputMediaOrientation(), [
                MediaOrientationEnum::square(),
                MediaOrientationEnum::landscape(),
                MediaOrientationEnum::portrait(),
            ]),
            new SupportedOption(OptionEnum::outputMediaAspectRatio(), ['1:1', '3:2', '2:3']),
        ];
        $ttsCapabilities = [
            CapabilityEnum::textToSpeechConversion(),
        ];
        $ttsOptions = [
            new SupportedOption(OptionEnum::outputMimeType(), ['audio/mpeg', 'audio/ogg', 'audio/wav']),
            new SupportedOption(OptionEnum::outputSpeechVoice()),
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

<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

/**
 * Class for the OpenAI model metadata directory.
 *
 * @since 0.1.0
 *
 * @phpstan-type ModelsResponseData array{
 *     data: list<array{id: string}>
 * }
 */
class OpenAiModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        return new Request(
            $method,
            OpenAiProvider::url($path),
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
        if (!isset($responseData['data']) || !$responseData['data']) {
            throw ResponseException::fromMissingData('OpenAI', 'data');
        }

        // Unfortunately, the OpenAI API does not return model capabilities, so we have to hardcode them here.
        $gptCapabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
        ];
        $gptBaseOptions = [
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
        $gptOptions = array_merge($gptBaseOptions, [
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
        ]);
        $gptMultimodalInputOptions = array_merge($gptBaseOptions, [
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
        $gptMultimodalSpeechOutputOptions = array_merge($gptBaseOptions, [
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
                    [ModalityEnum::text(), ModalityEnum::audio()],
                ]
            ),
        ]);
        $gptSearchOptions = [
            new SupportedOption(OptionEnum::systemInstruction()),
            new SupportedOption(OptionEnum::outputMimeType(), ['text/plain', 'application/json']),
            new SupportedOption(OptionEnum::outputSchema()),
            new SupportedOption(OptionEnum::customOptions()),
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
        ];
        $imageCapabilities = [
            CapabilityEnum::imageGeneration(),
        ];
        $dalleImageOptions = [
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::image()]]),
            new SupportedOption(OptionEnum::candidateCount()),
            new SupportedOption(OptionEnum::outputMimeType(), ['image/png']),
            new SupportedOption(OptionEnum::outputFileType(), [FileTypeEnum::inline(), FileTypeEnum::remote()]),
            new SupportedOption(OptionEnum::outputMediaOrientation(), [
                MediaOrientationEnum::square(),
                MediaOrientationEnum::landscape(),
                MediaOrientationEnum::portrait(),
            ]),
            new SupportedOption(OptionEnum::outputMediaAspectRatio(), ['1:1', '7:4', '4:7']),
            new SupportedOption(OptionEnum::customOptions()),
        ];
        $gptImageOptions = [
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::image()]]),
            new SupportedOption(OptionEnum::candidateCount()),
            new SupportedOption(OptionEnum::outputMimeType(), ['image/png', 'image/jpeg', 'image/webp']),
            new SupportedOption(OptionEnum::outputFileType(), [FileTypeEnum::inline()]),
            new SupportedOption(OptionEnum::outputMediaOrientation(), [
                MediaOrientationEnum::square(),
                MediaOrientationEnum::landscape(),
                MediaOrientationEnum::portrait(),
            ]),
            new SupportedOption(OptionEnum::outputMediaAspectRatio(), ['1:1', '3:2', '2:3']),
            new SupportedOption(OptionEnum::customOptions()),
        ];
        $ttsCapabilities = [
            CapabilityEnum::textToSpeechConversion(),
        ];
        $ttsOptions = [
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::audio()]]),
            new SupportedOption(OptionEnum::outputMimeType(), [
                'audio/mpeg',
                'audio/ogg',
                'audio/wav',
                'audio/flac',
                'audio/aac',
            ]),
            new SupportedOption(OptionEnum::outputSpeechVoice()),
            new SupportedOption(OptionEnum::customOptions()),
        ];

        $modelsData = (array) $responseData['data'];

        $models = array_values(
            array_map(
                static function (array $modelData) use (
                    $gptCapabilities,
                    $gptOptions,
                    $gptMultimodalInputOptions,
                    $gptMultimodalSpeechOutputOptions,
                    $gptSearchOptions,
                    $imageCapabilities,
                    $dalleImageOptions,
                    $gptImageOptions,
                    $ttsCapabilities,
                    $ttsOptions
                ): ModelMetadata {
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
                            } elseif (str_contains($modelId, '-search')) {
                                $modelOptions = $gptSearchOptions;
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

        usort($models, [$this, 'modelSortCallback']);

        return $models;
    }

    /**
     * Callback function for sorting models by ID, to be used with `usort()`.
     *
     * This method expresses preferences for certain models or model families within the provider by putting them
     * earlier in the sorted list. The objective is not to be opinionated about which models are better, but to ensure
     * that more commonly used, more recent, or flagship models are presented first to users.
     *
     * @since 0.2.1
     *
     * @param ModelMetadata $a First model.
     * @param ModelMetadata $b Second model.
     * @return int Comparison result.
     */
    protected function modelSortCallback(ModelMetadata $a, ModelMetadata $b): int
    {
        $aId = $a->getId();
        $bId = $b->getId();

        // Prefer non-preview models over preview models.
        if (str_contains($aId, '-preview') && !str_contains($bId, '-preview')) {
            return 1;
        }
        if (str_contains($bId, '-preview') && !str_contains($aId, '-preview')) {
            return -1;
        }

        // Prefer GPT models over non-GPT models.
        if (str_starts_with($aId, 'gpt-') && !str_starts_with($bId, 'gpt-')) {
            return -1;
        }
        if (str_starts_with($bId, 'gpt-') && !str_starts_with($aId, 'gpt-')) {
            return 1;
        }

        // Prefer GPT models with version numbers (e.g. 'gpt-5.1', 'gpt-5') over those without.
        $aMatch = preg_match('/^gpt-([0-9.]+)(-[a-z0-9-]+)?$/', $aId, $aMatches);
        $bMatch = preg_match('/^gpt-([0-9.]+)(-[a-z0-9-]+)?$/', $bId, $bMatches);
        if ($aMatch && !$bMatch) {
            return -1;
        }
        if ($bMatch && !$aMatch) {
            return 1;
        }
        if ($aMatch && $bMatch) {
            // Prefer later model versions.
            $aVersion = $aMatches[1];
            $bVersion = $bMatches[1];
            if (version_compare($aVersion, $bVersion, '>')) {
                return -1;
            }
            if (version_compare($bVersion, $aVersion, '>')) {
                return 1;
            }

            // Prefer models without a suffix (i.e. base models) over those with a suffix.
            if (!isset($aMatches[2]) && isset($bMatches[2])) {
                return -1;
            }
            if (!isset($bMatches[2]) && isset($aMatches[2])) {
                return 1;
            }

            // Prefer '-mini' models over others with a suffix.
            if (isset($aMatches[2]) && isset($bMatches[2])) {
                if ($aMatches[2] === '-mini' && $bMatches[2] !== '-mini') {
                    return -1;
                }
                if ($bMatches[2] === '-mini' && $aMatches[2] !== '-mini') {
                    return 1;
                }
            }
        }

        // Fallback: Sort alphabetically.
        return strcmp($a->getId(), $b->getId());
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\XAi;

use RuntimeException;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

/**
 * Class for the xAI model metadata directory.
 *
 * @since n.e.x.t
 *
 * @phpstan-type ModelsResponseData array{
 *     data: list<array{id: string}>
 * }
 */
class XAiModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        return new Request(
            $method,
            XAiProvider::BASE_URI . '/' . ltrim($path, '/'),
            $headers,
            $data
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected function parseResponseToModelMetadataList(Response $response): array
    {
        /** @var ModelsResponseData $responseData */
        $responseData = $response->getData();
        if (!isset($responseData['data']) || !$responseData['data']) {
            throw new RuntimeException(
                'Unexpected API response: Missing the data key.'
            );
        }

        // Unfortunately, the xAI API does not return model capabilities, so we have to hardcode them here.
        $xaiCapabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
        ];
        $xaiBaseOptions = [
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
            new SupportedOption(OptionEnum::webSearch()),
            new SupportedOption(OptionEnum::customOptions()),
        ];
        $xaiOptions = array_merge($xaiBaseOptions, [
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
        ]);
        $xaiMultimodalInputOptions = array_merge($xaiBaseOptions, [
            new SupportedOption(
                OptionEnum::inputModalities(),
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                ]
            ),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
        ]);
        $imageCapabilities = [
            CapabilityEnum::imageGeneration(),
        ];
        $imageOptions = [
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::image()]]),
            new SupportedOption(OptionEnum::candidateCount()),
            new SupportedOption(OptionEnum::outputMimeType(), ['image/jpeg']),
            new SupportedOption(OptionEnum::outputFileType(), [FileTypeEnum::inline()]),
            new SupportedOption(OptionEnum::customOptions()),
        ];

        $modelsData = (array) $responseData['data'];

        return array_values(
            array_map(
                static function (array $modelData) use (
                    $xaiCapabilities,
                    $xaiOptions,
                    $xaiMultimodalInputOptions,
                    $imageCapabilities,
                    $imageOptions
                ): ModelMetadata {
                    $modelId = $modelData['id'];
                    if (str_contains($modelId, '-image-')) {
                        $modelCaps = $imageCapabilities;
                        $modelOptions = $imageOptions;
                    } elseif (str_contains($modelId, '-vision-')) {
                        $modelCaps = $xaiCapabilities;
                        $modelOptions = $xaiMultimodalInputOptions;
                    } elseif (
                        str_starts_with($modelId, 'grok-') &&
                        !str_contains($modelId, '-code')
                    ) {
                        $modelCaps = $xaiCapabilities;
                        $modelOptions = $xaiOptions;
                    } else {
                        $modelCaps = [];
                        $modelOptions = [];
                    }

                    return new ModelMetadata(
                        $modelId,
                        $modelId, // The xAI API does not return a display name.
                        $modelCaps,
                        $modelOptions
                    );
                },
                $modelsData
            )
        );
    }
}

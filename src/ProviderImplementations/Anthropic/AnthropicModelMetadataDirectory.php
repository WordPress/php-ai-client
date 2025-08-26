<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\Anthropic;

use RuntimeException;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\AbstractOpenAiCompatibleModelMetadataDirectory;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

/**
 * Class for the Anthropic model metadata directory.
 *
 * @since n.e.x.t
 */
class AnthropicModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
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
        return new AnthropicApiKeyRequestAuthentication($requestAuthentication->getApiKey());
    }

    /**
     * @inheritDoc
     */
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        return new Request(
            $method,
            AnthropicProvider::BASE_URI . '/' . ltrim($path, '/'),
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

        // Unfortunately, the Anthropic API does not return model capabilities, so we have to hardcode them here.
        $anthropicCapabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
        ];
        $anthropicOptions = [
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
            new SupportedOption(
                OptionEnum::inputModalities(),
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                ]
            ),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
        ];
        $anthropicWebSearchOptions = array_merge($anthropicOptions, [
            new SupportedOption(OptionEnum::webSearch()),
        ]);

        /** @var array<string, array<string, mixed>> $modelsData */
        $modelsData = (array) $responseData['data'];

        return array_values(
            array_map(
                static function (array $modelData) use (
                    $anthropicCapabilities,
                    $anthropicOptions,
                    $anthropicWebSearchOptions,
                ): ModelMetadata {
                    /** @var string $modelId */
                    $modelId = $modelData['id'];
                    $modelCaps = $anthropicCapabilities;
                    if (!preg_match('/^claude-3-[a-z]+/', $modelId)) {
                        // Only models newer than Claude 3 support web search.
                        $modelOptions = $anthropicWebSearchOptions;
                    } else {
                        $modelOptions = $anthropicOptions;
                    }

                    /** @var string $modelName */
                    $modelName = $modelData['display_name'] ?? $modelId;

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

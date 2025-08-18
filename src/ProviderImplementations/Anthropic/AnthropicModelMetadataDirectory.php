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
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

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
            new SupportedOption(
                ModelConfig::KEY_INPUT_MODALITIES,
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                ]
            ),
        ];
        $anthropicWebSearchOptions = $anthropicOptions + [
            new SupportedOption(ModelConfig::KEY_WEB_SEARCH),
        ];

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

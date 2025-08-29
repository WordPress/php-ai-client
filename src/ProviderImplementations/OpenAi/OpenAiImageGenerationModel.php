<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleImageGenerationModel;

/**
 * Class for an OpenAI image generation model.
 *
 * @since 0.1.0
 */
class OpenAiImageGenerationModel extends AbstractOpenAiCompatibleImageGenerationModel
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
    protected function prepareGenerateImageParams(array $prompt): array
    {
        $params = parent::prepareGenerateImageParams($prompt);

        /*
         * Only the newer 'gpt-image-' models support passing a MIME type ('output_format').
         * Conversely, they do not support 'response_format', but always return a base64 encoded image.
         */
        if (isset($params['model']) && is_string($params['model']) && str_starts_with($params['model'], 'gpt-image-')) {
            unset($params['response_format']);
        } else {
            unset($params['output_format']);
        }

        return $params;
    }
}

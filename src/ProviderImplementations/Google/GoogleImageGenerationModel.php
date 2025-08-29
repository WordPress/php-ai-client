<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\Google;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleImageGenerationModel;

/**
 * Class for a Google image generation model.
 *
 * @since 0.1.0
 */
class GoogleImageGenerationModel extends AbstractOpenAiCompatibleImageGenerationModel
{
    /**
     * @inheritDoc
     */
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        return new Request(
            $method,
            GoogleProvider::BASE_URI . '/openai/' . ltrim($path, '/'),
            $headers,
            $data
        );
    }
}

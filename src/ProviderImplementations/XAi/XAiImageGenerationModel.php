<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\XAi;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleImageGenerationModel;

/**
 * Class for a xAI image generation model.
 *
 * @since n.e.x.t
 */
class XAiImageGenerationModel extends AbstractOpenAiCompatibleImageGenerationModel
{
    /**
     * @inheritDoc
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
}

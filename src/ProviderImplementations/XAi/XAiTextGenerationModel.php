<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\XAi;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

/**
 * Class for a xAI text generation model.
 *
 * @since n.e.x.t
 */
class XAiTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
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
}

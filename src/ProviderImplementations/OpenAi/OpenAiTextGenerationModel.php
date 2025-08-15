<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\AbstractOpenAiCompatibleTextGenerationModel;

/**
 * Class for an OpenAI text generation model.
 *
 * @since n.e.x.t
 */
class OpenAiTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
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
}

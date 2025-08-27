<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\Google;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

/**
 * Class for a Google text generation model.
 *
 * @since n.e.x.t
 */
class GoogleTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
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
            GoogleProvider::BASE_URI . '/openai/' . ltrim($path, '/'),
            $headers,
            $data
        );
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\Anthropic;

use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;

/**
 * Class for HTTP request authentication using an API key in a Anthropic API compliant way.
 *
 * @since 0.1.0
 */
class AnthropicApiKeyRequestAuthentication extends ApiKeyRequestAuthentication
{
    public const ANTHROPIC_API_VERSION = '2023-06-01';

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    public function authenticateRequest(Request $request): Request
    {
        // Anthropic requires this header to be set for all requests.
        $request = $request->withHeader('anthropic-version', self::ANTHROPIC_API_VERSION);

        // Add the API key to the request headers.
        return $request->withHeader('x-api-key', $this->apiKey);
    }
}

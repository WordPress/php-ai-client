<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\Google;

use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;

/**
 * Class for HTTP request authentication using an API key in a Google Gemini API compliant way.
 *
 * This is only relevant when calling the primary Google Gemini API endpoints. It is not relevant when calling the
 * OpenAI-compatible endpoints.
 *
 * @since n.e.x.t
 */
class GoogleApiKeyRequestAuthentication extends ApiKeyRequestAuthentication
{
    /**
     * @inheritDoc
     */
    public function authenticateRequest(Request $request): Request
    {
        // Add the API key to the request headers.
        return $request->withHeader('X-Goog-Api-Key', $this->apiKey);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\AwsBedrock;

use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;

/**
 * Class for HTTP request authentication using an API key in an AWS Bedrock compliant way.
 *
 * @since n.e.x.t
 */
class AwsBedrockApiKeyRequestAuthentication extends ApiKeyRequestAuthentication
{
    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function authenticateRequest(Request $request): Request
    {
        // Add the API key to the request headers using Bearer token authentication.
        return $request->withHeader('Authorization', 'Bearer ' . $this->apiKey);
    }
}

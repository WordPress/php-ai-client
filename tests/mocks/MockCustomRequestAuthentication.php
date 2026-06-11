<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\Http\DTO\BearerTokenRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;

/**
 * Mock custom request authentication for testing purposes.
 */
class MockCustomRequestAuthentication extends BearerTokenRequestAuthentication
{
    /**
     * {@inheritDoc}
     */
    public function authenticateRequest(Request $request): Request
    {
        return parent::authenticateRequest($request)->withHeader('X-Mock-Auth', 'custom');
    }
}

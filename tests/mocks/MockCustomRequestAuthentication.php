<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;

/**
 * Mock custom request authentication for testing purposes.
 */
class MockCustomRequestAuthentication implements RequestAuthenticationInterface
{
    /**
     * {@inheritDoc}
     */
    public function authenticateRequest(Request $request): Request
    {
        return $request->withHeader('X-Mock-Auth', 'custom');
    }

    /**
     * {@inheritDoc}
     */
    public static function getJsonSchema(): array
    {
        return [];
    }
}

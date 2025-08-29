<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;

/**
 * Mock request authentication for testing.
 */
class MockRequestAuthentication implements RequestAuthenticationInterface, WithJsonSchemaInterface
{
    /**
     * @var string The authentication token to add.
     */
    private string $token;

    /**
     * Constructor.
     *
     * @param string $token The authentication token.
     */
    public function __construct(string $token = 'mock_token')
    {
        $this->token = $token;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticateRequest(Request $request): Request
    {
        return $request->withHeader('X-Mock-Auth', $this->token);
    }

    /**
     * {@inheritDoc}
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];
    }
}

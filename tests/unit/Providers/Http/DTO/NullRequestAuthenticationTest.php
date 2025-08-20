<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\DTO\NullRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;

/**
 * @covers \WordPress\AiClient\Providers\Http\DTO\NullRequestAuthentication
 */
class NullRequestAuthenticationTest extends TestCase
{
    /**
     * Tests authenticateRequest method.
     *
     * @return void
     */
    public function testAuthenticateRequest(): void
    {
        $auth = new NullRequestAuthentication();
        $request = new Request(HttpMethodEnum::get(), 'https://example.com/api');
        $authenticatedRequest = $auth->authenticateRequest($request);

        $this->assertSame($request, $authenticatedRequest);
    }

    /**
     * Tests toArray method.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $auth = new NullRequestAuthentication();
        $array = $auth->toArray();

        $this->assertIsArray($array);
        $this->assertEmpty($array);
    }

    /**
     * Tests fromArray method.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $auth = NullRequestAuthentication::fromArray([]);

        $this->assertInstanceOf(NullRequestAuthentication::class, $auth);
    }

    /**
     * Tests getJsonSchema method.
     *
     * @return void
     */
    public function testGetJsonSchema(): void
    {
        $schema = NullRequestAuthentication::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertEmpty($schema['properties']);
        $this->assertArrayHasKey('required', $schema);
        $this->assertEmpty($schema['required']);
    }
}

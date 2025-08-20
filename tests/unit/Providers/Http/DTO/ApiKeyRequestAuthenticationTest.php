<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\DTO;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;

/**
 * @covers \WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication
 */
class ApiKeyRequestAuthenticationTest extends TestCase
{
    /**
     * Tests constructor and getApiKey method.
     *
     * @return void
     */
    public function testConstructorAndGetApiKey(): void
    {
        $apiKey = 'test_api_key_123';
        $auth = new ApiKeyRequestAuthentication($apiKey);

        $this->assertEquals($apiKey, $auth->getApiKey());
    }

    /**
     * Tests authenticateRequest method.
     *
     * @return void
     */
    public function testAuthenticateRequest(): void
    {
        $apiKey = 'test_api_key_456';
        $auth = new ApiKeyRequestAuthentication($apiKey);

        $request = new Request(HttpMethodEnum::get(), 'https://example.com/api');
        $authenticatedRequest = $auth->authenticateRequest($request);

        $this->assertNotSame($request, $authenticatedRequest); // Ensure immutability
        $this->assertTrue($authenticatedRequest->hasHeader('Authorization'));
        $this->assertEquals('Bearer ' . $apiKey, $authenticatedRequest->getHeaderAsString('Authorization'));
    }

    /**
     * Tests toArray method.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $apiKey = 'test_api_key_789';
        $auth = new ApiKeyRequestAuthentication($apiKey);

        $array = $auth->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey(ApiKeyRequestAuthentication::KEY_API_KEY, $array);
        $this->assertEquals($apiKey, $array[ApiKeyRequestAuthentication::KEY_API_KEY]);
    }

    /**
     * Tests fromArray method.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $apiKey = 'test_api_key_abc';
        $array = [
            ApiKeyRequestAuthentication::KEY_API_KEY => $apiKey,
        ];

        $auth = ApiKeyRequestAuthentication::fromArray($array);

        $this->assertInstanceOf(ApiKeyRequestAuthentication::class, $auth);
        $this->assertEquals($apiKey, $auth->getApiKey());
    }

    /**
     * Tests fromArray method with missing API key.
     *
     * @return void
     */
    public function testFromArrayWithMissingApiKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            ApiKeyRequestAuthentication::class . '::fromArray() missing required keys: apiKey'
        );

        ApiKeyRequestAuthentication::fromArray([]);
    }

    /**
     * Tests getJsonSchema method.
     *
     * @return void
     */
    public function testGetJsonSchema(): void
    {
        $schema = ApiKeyRequestAuthentication::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey(ApiKeyRequestAuthentication::KEY_API_KEY, $schema['properties']);
        $this->assertEquals('string', $schema['properties'][ApiKeyRequestAuthentication::KEY_API_KEY]['type']);
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals([ApiKeyRequestAuthentication::KEY_API_KEY], $schema['required']);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Tools\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * @covers \WordPress\AiClient\Tools\DTO\FunctionResponse
 */
class FunctionResponseTest extends TestCase
{
    /**
     * Tests creating FunctionResponse with all properties.
     *
     * @return void
     */
    public function testCreateWithAllProperties(): void
    {
        $id = 'func_123';
        $name = 'getWeather';
        $response = [
            'temperature' => 22,
            'condition' => 'sunny',
            'humidity' => 65,
        ];
        
        $functionResponse = new FunctionResponse($id, $name, $response);
        
        $this->assertEquals($id, $functionResponse->getId());
        $this->assertEquals($name, $functionResponse->getName());
        $this->assertEquals($response, $functionResponse->getResponse());
    }

    /**
     * Tests with various response types.
     *
     * @dataProvider responseTypesProvider
     * @param mixed $response
     * @return void
     */
    public function testWithVariousResponseTypes($response): void
    {
        $functionResponse = new FunctionResponse('id', 'name', $response);
        
        $this->assertSame($response, $functionResponse->getResponse());
    }

    /**
     * Provides various response types.
     *
     * @return array
     */
    public function responseTypesProvider(): array
    {
        return [
            'null' => [null],
            'string' => ['success'],
            'number' => [42],
            'float' => [3.14159],
            'boolean true' => [true],
            'boolean false' => [false],
            'empty array' => [[]],
            'indexed array' => [[1, 2, 3]],
            'associative array' => [['key' => 'value', 'another' => 'test']],
            'nested array' => [[
                'level1' => [
                    'level2' => [
                        'level3' => 'deep value'
                    ]
                ]
            ]],
            'object' => [(object) ['property' => 'value']],
            'mixed array' => [[
                'string' => 'text',
                'number' => 123,
                'boolean' => true,
                'null' => null,
                'array' => [1, 2, 3]
            ]],
        ];
    }

    /**
     * Tests JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = FunctionResponse::getJsonSchema();
        
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        
        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('response', $schema['properties']);
        
        // Check id property
        $this->assertEquals('string', $schema['properties']['id']['type']);
        $this->assertArrayHasKey('description', $schema['properties']['id']);
        
        // Check name property
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertArrayHasKey('description', $schema['properties']['name']);
        
        // Check response property allows multiple types
        $responseTypes = $schema['properties']['response']['type'];
        $this->assertIsArray($responseTypes);
        $this->assertContains('string', $responseTypes);
        $this->assertContains('number', $responseTypes);
        $this->assertContains('boolean', $responseTypes);
        $this->assertContains('object', $responseTypes);
        $this->assertContains('array', $responseTypes);
        $this->assertContains('null', $responseTypes);
        
        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals(['id', 'name', 'response'], $schema['required']);
    }

    /**
     * Tests with empty string values.
     *
     * @return void
     */
    public function testWithEmptyStringValues(): void
    {
        $response = new FunctionResponse('', '', '');
        
        $this->assertEquals('', $response->getId());
        $this->assertEquals('', $response->getName());
        $this->assertEquals('', $response->getResponse());
    }

    /**
     * Tests with error response.
     *
     * @return void
     */
    public function testWithErrorResponse(): void
    {
        $errorResponse = [
            'error' => true,
            'message' => 'Function execution failed',
            'code' => 'EXEC_ERROR',
            'details' => [
                'timestamp' => '2024-01-01T00:00:00Z',
                'trace' => 'stack trace here'
            ]
        ];
        
        $response = new FunctionResponse('func_456', 'failingFunction', $errorResponse);
        
        $this->assertEquals('func_456', $response->getId());
        $this->assertEquals('failingFunction', $response->getName());
        $this->assertEquals($errorResponse, $response->getResponse());
    }

    /**
     * Tests with large response data.
     *
     * @return void
     */
    public function testWithLargeResponseData(): void
    {
        // Create a large array
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData["key_$i"] = "value_$i";
        }
        
        $response = new FunctionResponse('id', 'name', $largeData);
        
        $this->assertEquals($largeData, $response->getResponse());
        $this->assertCount(1000, $response->getResponse());
    }
}
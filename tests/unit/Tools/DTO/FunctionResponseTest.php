<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Tools\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Tests\traits\ArrayTransformationTestTrait;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * @covers \WordPress\AiClient\Tools\DTO\FunctionResponse
 */
class FunctionResponseTest extends TestCase
{
    use ArrayTransformationTestTrait;

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
        $this->assertArrayHasKey(FunctionResponse::KEY_ID, $schema['properties']);
        $this->assertArrayHasKey(FunctionResponse::KEY_NAME, $schema['properties']);
        $this->assertArrayHasKey(FunctionResponse::KEY_RESPONSE, $schema['properties']);

        // Check id property
        $this->assertEquals('string', $schema['properties'][FunctionResponse::KEY_ID]['type']);
        $this->assertArrayHasKey('description', $schema['properties'][FunctionResponse::KEY_ID]);

        // Check name property
        $this->assertEquals('string', $schema['properties'][FunctionResponse::KEY_NAME]['type']);
        $this->assertArrayHasKey('description', $schema['properties'][FunctionResponse::KEY_NAME]);

        // Check response property allows multiple types
        $responseTypes = $schema['properties'][FunctionResponse::KEY_RESPONSE]['type'];
        $this->assertIsArray($responseTypes);
        $this->assertContains('string', $responseTypes);
        $this->assertContains('number', $responseTypes);
        $this->assertContains('boolean', $responseTypes);
        $this->assertContains('object', $responseTypes);
        $this->assertContains('array', $responseTypes);
        $this->assertContains('null', $responseTypes);

        // Check oneOf for required fields
        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertCount(2, $schema['oneOf']);

        // First option: response and id required
        $this->assertEquals(
            [FunctionResponse::KEY_RESPONSE, FunctionResponse::KEY_ID],
            $schema['oneOf'][0]['required']
        );

        // Second option: response and name required
        $this->assertEquals(
            [FunctionResponse::KEY_RESPONSE, FunctionResponse::KEY_NAME],
            $schema['oneOf'][1]['required']
        );
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

    /**
     * Tests array transformation.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $response = new FunctionResponse('func_123', 'calculate', ['result' => 42]);
        $json = $this->assertToArrayReturnsArray($response);

        $this->assertArrayHasKeys(
            $json,
            [FunctionResponse::KEY_ID, FunctionResponse::KEY_NAME, FunctionResponse::KEY_RESPONSE]
        );
        $this->assertEquals('func_123', $json[FunctionResponse::KEY_ID]);
        $this->assertEquals('calculate', $json[FunctionResponse::KEY_NAME]);
        $this->assertEquals(['result' => 42], $json[FunctionResponse::KEY_RESPONSE]);
    }

    /**
     * Tests fromJson method.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $json = [
            FunctionResponse::KEY_ID => 'func_456',
            FunctionResponse::KEY_NAME => 'search',
            FunctionResponse::KEY_RESPONSE => ['found' => true, 'count' => 5]
        ];

        $response = FunctionResponse::fromArray($json);

        $this->assertInstanceOf(FunctionResponse::class, $response);
        $this->assertEquals('func_456', $response->getId());
        $this->assertEquals('search', $response->getName());
        $this->assertEquals(['found' => true, 'count' => 5], $response->getResponse());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $this->assertArrayRoundTrip(
            new FunctionResponse('id_789', 'process', ['status' => 'complete']),
            function ($original, $restored) {
                $this->assertEquals($original->getId(), $restored->getId());
                $this->assertEquals($original->getName(), $restored->getName());
                $this->assertEquals($original->getResponse(), $restored->getResponse());
            }
        );
    }

    /**
     * Tests FunctionResponse implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $response = new FunctionResponse('id', 'name', 'result');
        $this->assertImplementsArrayTransformation($response);
    }
}

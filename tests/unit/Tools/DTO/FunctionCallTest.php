<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Tools\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Tools\DTO\FunctionCall;

/**
 * @covers \WordPress\AiClient\Tools\DTO\FunctionCall
 */
class FunctionCallTest extends TestCase
{
    /**
     * Tests creating FunctionCall with both ID and name.
     *
     * @return void
     */
    public function testCreateWithIdAndName(): void
    {
        $id = 'func_123';
        $name = 'getWeather';
        $args = ['city' => 'New York', 'units' => 'celsius'];
        
        $functionCall = new FunctionCall($id, $name, $args);
        
        $this->assertEquals($id, $functionCall->getId());
        $this->assertEquals($name, $functionCall->getName());
        $this->assertEquals($args, $functionCall->getArgs());
    }

    /**
     * Tests creating FunctionCall with only ID.
     *
     * @return void
     */
    public function testCreateWithOnlyId(): void
    {
        $id = 'func_123';
        $args = ['param' => 'value'];
        
        $functionCall = new FunctionCall($id, null, $args);
        
        $this->assertEquals($id, $functionCall->getId());
        $this->assertNull($functionCall->getName());
        $this->assertEquals($args, $functionCall->getArgs());
    }

    /**
     * Tests creating FunctionCall with only name.
     *
     * @return void
     */
    public function testCreateWithOnlyName(): void
    {
        $name = 'calculateTotal';
        $args = ['items' => [1, 2, 3]];
        
        $functionCall = new FunctionCall(null, $name, $args);
        
        $this->assertNull($functionCall->getId());
        $this->assertEquals($name, $functionCall->getName());
        $this->assertEquals($args, $functionCall->getArgs());
    }

    /**
     * Tests creating FunctionCall without args.
     *
     * @return void
     */
    public function testCreateWithoutArgs(): void
    {
        $functionCall = new FunctionCall('func_123', 'getTime');
        
        $this->assertEquals('func_123', $functionCall->getId());
        $this->assertEquals('getTime', $functionCall->getName());
        $this->assertEquals([], $functionCall->getArgs());
    }

    /**
     * Tests that creating without ID or name throws exception.
     *
     * @return void
     */
    public function testCreateWithoutIdOrNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one of id or name must be provided.');
        
        new FunctionCall(null, null, []);
    }

    /**
     * Tests JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = FunctionCall::getJsonSchema();
        
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        
        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('args', $schema['properties']);
        
        // Check id property
        $this->assertEquals('string', $schema['properties']['id']['type']);
        $this->assertArrayHasKey('description', $schema['properties']['id']);
        
        // Check name property
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertArrayHasKey('description', $schema['properties']['name']);
        
        // Check args property
        $this->assertEquals('object', $schema['properties']['args']['type']);
        $this->assertTrue($schema['properties']['args']['additionalProperties']);
        
        // Check oneOf for required fields
        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertCount(3, $schema['oneOf']);
        
        // First option: only id required
        $this->assertEquals(['id'], $schema['oneOf'][0]['required']);
        
        // Second option: only name required
        $this->assertEquals(['name'], $schema['oneOf'][1]['required']);
        
        // Third option: both id and name required
        $this->assertEquals(['id', 'name'], $schema['oneOf'][2]['required']);
    }

    /**
     * Tests with complex args.
     *
     * @return void
     */
    public function testWithComplexArgs(): void
    {
        $args = [
            'string' => 'value',
            'number' => 42,
            'float' => 3.14,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'object' => ['key' => 'value'],
            'nested' => [
                'deep' => [
                    'value' => 'test'
                ]
            ]
        ];
        
        $functionCall = new FunctionCall('id', 'name', $args);
        
        $this->assertEquals($args, $functionCall->getArgs());
    }
}
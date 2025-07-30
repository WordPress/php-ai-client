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
        $this->assertArrayHasKey(FunctionCall::KEY_ID, $schema['properties']);
        $this->assertArrayHasKey(FunctionCall::KEY_NAME, $schema['properties']);
        $this->assertArrayHasKey(FunctionCall::KEY_ARGS, $schema['properties']);
        
        // Check id property
        $this->assertEquals('string', $schema['properties'][FunctionCall::KEY_ID]['type']);
        $this->assertArrayHasKey('description', $schema['properties'][FunctionCall::KEY_ID]);
        
        // Check name property
        $this->assertEquals('string', $schema['properties'][FunctionCall::KEY_NAME]['type']);
        $this->assertArrayHasKey('description', $schema['properties'][FunctionCall::KEY_NAME]);
        
        // Check args property
        $this->assertEquals('object', $schema['properties'][FunctionCall::KEY_ARGS]['type']);
        $this->assertTrue($schema['properties'][FunctionCall::KEY_ARGS]['additionalProperties']);
        
        // Check oneOf for required fields
        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertCount(2, $schema['oneOf']);
        
        // First option: only id required
        $this->assertEquals([FunctionCall::KEY_ID], $schema['oneOf'][0]['required']);
        
        // Second option: only name required
        $this->assertEquals([FunctionCall::KEY_NAME], $schema['oneOf'][1]['required']);
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

    /**
     * Tests array transformation with all fields.
     *
     * @return void
     */
    public function testToArrayAllFields(): void
    {
        $functionCall = new FunctionCall('func_123', 'calculate', ['x' => 10, 'y' => 20]);
        $json = $functionCall->toArray();
        
        $this->assertIsArray($json);
        $this->assertEquals('func_123', $json[FunctionCall::KEY_ID]);
        $this->assertEquals('calculate', $json[FunctionCall::KEY_NAME]);
        $this->assertEquals(['x' => 10, 'y' => 20], $json[FunctionCall::KEY_ARGS]);
    }

    /**
     * Tests array transformation with only ID.
     *
     * @return void
     */
    public function testToArrayOnlyId(): void
    {
        $functionCall = new FunctionCall('func_456', null);
        $json = $functionCall->toArray();
        
        $this->assertIsArray($json);
        $this->assertEquals('func_456', $json[FunctionCall::KEY_ID]);
        $this->assertArrayNotHasKey(FunctionCall::KEY_NAME, $json);
        $this->assertArrayNotHasKey(FunctionCall::KEY_ARGS, $json);
    }

    /**
     * Tests array transformation with only name.
     *
     * @return void
     */
    public function testToArrayOnlyName(): void
    {
        $functionCall = new FunctionCall(null, 'search');
        $json = $functionCall->toArray();
        
        $this->assertIsArray($json);
        $this->assertEquals('search', $json[FunctionCall::KEY_NAME]);
        $this->assertArrayNotHasKey(FunctionCall::KEY_ID, $json);
        $this->assertArrayNotHasKey(FunctionCall::KEY_ARGS, $json);
    }

    /**
     * Tests fromJson with all fields.
     *
     * @return void
     */
    public function testFromArrayAllFields(): void
    {
        $json = [
            FunctionCall::KEY_ID => 'func_789',
            FunctionCall::KEY_NAME => 'process',
            FunctionCall::KEY_ARGS => ['input' => 'data', 'format' => 'json']
        ];
        
        $functionCall = FunctionCall::fromArray($json);
        
        $this->assertInstanceOf(FunctionCall::class, $functionCall);
        $this->assertEquals('func_789', $functionCall->getId());
        $this->assertEquals('process', $functionCall->getName());
        $this->assertEquals(['input' => 'data', 'format' => 'json'], $functionCall->getArgs());
    }

    /**
     * Tests fromJson with minimal fields.
     *
     * @return void
     */
    public function testFromArrayMinimalFields(): void
    {
        $json = [FunctionCall::KEY_NAME => 'minimal'];
        
        $functionCall = FunctionCall::fromArray($json);
        
        $this->assertInstanceOf(FunctionCall::class, $functionCall);
        $this->assertNull($functionCall->getId());
        $this->assertEquals('minimal', $functionCall->getName());
        $this->assertEquals([], $functionCall->getArgs());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $original = new FunctionCall('id_123', 'execute', ['param' => 'value', 'count' => 5]);
        $json = $original->toArray();
        $restored = FunctionCall::fromArray($json);
        
        $this->assertEquals($original->getId(), $restored->getId());
        $this->assertEquals($original->getName(), $restored->getName());
        $this->assertEquals($original->getArgs(), $restored->getArgs());
    }

    /**
     * Tests FunctionCall implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $functionCall = new FunctionCall('id', 'name');
        
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface::class,
            $functionCall
        );
        
    }
}
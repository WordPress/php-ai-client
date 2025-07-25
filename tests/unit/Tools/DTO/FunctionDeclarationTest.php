<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Tools\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;

/**
 * @covers \WordPress\AiClient\Tools\DTO\FunctionDeclaration
 */
class FunctionDeclarationTest extends TestCase
{
    /**
     * Tests creating FunctionDeclaration with all properties.
     *
     * @return void
     */
    public function testCreateWithAllProperties(): void
    {
        $name = 'calculateSum';
        $description = 'Calculates the sum of two numbers';
        $parameters = [
            'type' => 'object',
            'properties' => [
                'a' => ['type' => 'number', 'description' => 'First number'],
                'b' => ['type' => 'number', 'description' => 'Second number'],
            ],
            'required' => ['a', 'b'],
        ];
        
        $declaration = new FunctionDeclaration($name, $description, $parameters);
        
        $this->assertEquals($name, $declaration->getName());
        $this->assertEquals($description, $declaration->getDescription());
        $this->assertEquals($parameters, $declaration->getParameters());
    }

    /**
     * Tests creating FunctionDeclaration without parameters.
     *
     * @return void
     */
    public function testCreateWithoutParameters(): void
    {
        $name = 'getCurrentTime';
        $description = 'Gets the current system time';
        
        $declaration = new FunctionDeclaration($name, $description);
        
        $this->assertEquals($name, $declaration->getName());
        $this->assertEquals($description, $declaration->getDescription());
        $this->assertNull($declaration->getParameters());
    }

    /**
     * Tests with various parameter types.
     *
     * @dataProvider parameterTypesProvider
     * @param mixed $parameters
     * @return void
     */
    public function testWithVariousParameterTypes($parameters): void
    {
        $declaration = new FunctionDeclaration('test', 'test function', $parameters);
        
        $this->assertSame($parameters, $declaration->getParameters());
    }

    /**
     * Provides various parameter types.
     *
     * @return array
     */
    public function parameterTypesProvider(): array
    {
        return [
            'null' => [null],
            'string' => ['simple string parameter'],
            'number' => [42],
            'float' => [3.14],
            'boolean' => [true],
            'array' => [['key' => 'value']],
            'object' => [(object) ['property' => 'value']],
            'complex schema' => [[
                'type' => 'object',
                'properties' => [
                    'nested' => [
                        'type' => 'object',
                        'properties' => [
                            'value' => ['type' => 'string']
                        ]
                    ]
                ]
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
        $schema = FunctionDeclaration::getJsonSchema();
        
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        
        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('description', $schema['properties']);
        $this->assertArrayHasKey('parameters', $schema['properties']);
        
        // Check name property
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertArrayHasKey('description', $schema['properties']['name']);
        
        // Check description property
        $this->assertEquals('string', $schema['properties']['description']['type']);
        $this->assertArrayHasKey('description', $schema['properties']['description']);
        
        // Check parameters property allows multiple types
        $paramTypes = $schema['properties']['parameters']['type'];
        $this->assertIsArray($paramTypes);
        $this->assertContains('string', $paramTypes);
        $this->assertContains('number', $paramTypes);
        $this->assertContains('boolean', $paramTypes);
        $this->assertContains('object', $paramTypes);
        $this->assertContains('array', $paramTypes);
        $this->assertContains('null', $paramTypes);
        
        // Check required fields - parameters should NOT be required
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals(['name', 'description'], $schema['required']);
        $this->assertNotContains('parameters', $schema['required']);
    }

    /**
     * Tests empty string values.
     *
     * @return void
     */
    public function testEmptyStringValues(): void
    {
        $declaration = new FunctionDeclaration('', '');
        
        $this->assertEquals('', $declaration->getName());
        $this->assertEquals('', $declaration->getDescription());
        $this->assertNull($declaration->getParameters());
    }

    /**
     * Tests with OpenAPI-style parameter schema.
     *
     * @return void
     */
    public function testWithOpenApiStyleSchema(): void
    {
        $parameters = [
            'type' => 'object',
            'properties' => [
                'location' => [
                    'type' => 'string',
                    'description' => 'The city and state, e.g. San Francisco, CA'
                ],
                'unit' => [
                    'type' => 'string',
                    'enum' => ['celsius', 'fahrenheit'],
                    'default' => 'fahrenheit'
                ]
            ],
            'required' => ['location'],
            'additionalProperties' => false
        ];
        
        $declaration = new FunctionDeclaration(
            'get_weather',
            'Get the current weather in a given location',
            $parameters
        );
        
        $this->assertEquals($parameters, $declaration->getParameters());
    }
}
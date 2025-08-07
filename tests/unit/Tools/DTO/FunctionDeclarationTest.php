<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Tools\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Tests\traits\ArrayTransformationTestTrait;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;

/**
 * @covers \WordPress\AiClient\Tools\DTO\FunctionDeclaration
 */
class FunctionDeclarationTest extends TestCase
{
    use ArrayTransformationTestTrait;

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
        $this->assertArrayHasKey(FunctionDeclaration::KEY_NAME, $schema['properties']);
        $this->assertArrayHasKey(FunctionDeclaration::KEY_DESCRIPTION, $schema['properties']);
        $this->assertArrayHasKey(FunctionDeclaration::KEY_PARAMETERS, $schema['properties']);

        // Check name property
        $this->assertEquals('string', $schema['properties'][FunctionDeclaration::KEY_NAME]['type']);
        $this->assertArrayHasKey('description', $schema['properties'][FunctionDeclaration::KEY_NAME]);

        // Check description property
        $this->assertEquals('string', $schema['properties'][FunctionDeclaration::KEY_DESCRIPTION]['type']);
        $this->assertArrayHasKey('description', $schema['properties'][FunctionDeclaration::KEY_DESCRIPTION]);

        // Check parameters property allows multiple types
        $paramTypes = $schema['properties'][FunctionDeclaration::KEY_PARAMETERS]['type'];
        $this->assertIsArray($paramTypes);
        $this->assertContains('string', $paramTypes);
        $this->assertContains('number', $paramTypes);
        $this->assertContains('boolean', $paramTypes);
        $this->assertContains('object', $paramTypes);
        $this->assertContains('array', $paramTypes);
        $this->assertContains('null', $paramTypes);

        // Check required fields - parameters should NOT be required
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals([FunctionDeclaration::KEY_NAME, FunctionDeclaration::KEY_DESCRIPTION], $schema['required']);
        $this->assertNotContains(FunctionDeclaration::KEY_PARAMETERS, $schema['required']);
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

    /**
     * Tests array transformation with parameters.
     *
     * @return void
     */
    public function testToArrayWithParameters(): void
    {
        $declaration = new FunctionDeclaration(
            'searchWeb',
            'Searches the web for information',
            ['type' => 'object', 'properties' => ['query' => ['type' => 'string']]]
        );

        $json = $this->assertToArrayReturnsArray($declaration);

        $this->assertArrayHasKeys($json, [FunctionDeclaration::KEY_NAME, FunctionDeclaration::KEY_DESCRIPTION, FunctionDeclaration::KEY_PARAMETERS]);
        $this->assertEquals('searchWeb', $json[FunctionDeclaration::KEY_NAME]);
        $this->assertEquals('Searches the web for information', $json[FunctionDeclaration::KEY_DESCRIPTION]);
        $this->assertEquals(['type' => 'object', 'properties' => ['query' => ['type' => 'string']]], $json[FunctionDeclaration::KEY_PARAMETERS]);
    }

    /**
     * Tests array transformation without parameters.
     *
     * @return void
     */
    public function testToArrayWithoutParameters(): void
    {
        $declaration = new FunctionDeclaration(
            'getTimestamp',
            'Returns the current Unix timestamp'
        );

        $json = $this->assertToArrayReturnsArray($declaration);

        $this->assertArrayHasKeys($json, [FunctionDeclaration::KEY_NAME, FunctionDeclaration::KEY_DESCRIPTION]);
        $this->assertArrayNotHasKey(FunctionDeclaration::KEY_PARAMETERS, $json);
        $this->assertEquals('getTimestamp', $json[FunctionDeclaration::KEY_NAME]);
        $this->assertEquals('Returns the current Unix timestamp', $json[FunctionDeclaration::KEY_DESCRIPTION]);
    }

    /**
     * Tests fromJson method with parameters.
     *
     * @return void
     */
    public function testFromArrayWithParameters(): void
    {
        $json = [
            FunctionDeclaration::KEY_NAME => 'calculateArea',
            FunctionDeclaration::KEY_DESCRIPTION => 'Calculates the area of a rectangle',
            FunctionDeclaration::KEY_PARAMETERS => [
                'type' => 'object',
                'properties' => [
                    'width' => ['type' => 'number'],
                    'height' => ['type' => 'number']
                ],
                'required' => ['width', 'height']
            ]
        ];

        $declaration = FunctionDeclaration::fromArray($json);

        $this->assertInstanceOf(FunctionDeclaration::class, $declaration);
        $this->assertEquals('calculateArea', $declaration->getName());
        $this->assertEquals('Calculates the area of a rectangle', $declaration->getDescription());
        $this->assertEquals($json[FunctionDeclaration::KEY_PARAMETERS], $declaration->getParameters());
    }

    /**
     * Tests fromJson method without parameters.
     *
     * @return void
     */
    public function testFromArrayWithoutParameters(): void
    {
        $json = [
            FunctionDeclaration::KEY_NAME => 'ping',
            FunctionDeclaration::KEY_DESCRIPTION => 'Simple ping function'
        ];

        $declaration = FunctionDeclaration::fromArray($json);

        $this->assertInstanceOf(FunctionDeclaration::class, $declaration);
        $this->assertEquals('ping', $declaration->getName());
        $this->assertEquals('Simple ping function', $declaration->getDescription());
        $this->assertNull($declaration->getParameters());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $this->assertArrayRoundTrip(
            new FunctionDeclaration(
                'complexFunction',
                'A complex function with nested parameters',
                [
                    'type' => 'object',
                    'properties' => [
                        'user' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'age' => ['type' => 'integer', 'minimum' => 0]
                            ]
                        ],
                        'options' => [
                            'type' => 'array',
                            'items' => ['type' => 'string']
                        ]
                    ]
                ]
            ),
            function ($original, $restored) {
                $this->assertEquals($original->getName(), $restored->getName());
                $this->assertEquals($original->getDescription(), $restored->getDescription());
                $this->assertEquals($original->getParameters(), $restored->getParameters());
            }
        );
    }

    /**
     * Tests FunctionDeclaration implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $declaration = new FunctionDeclaration('test', 'test function');
        $this->assertImplementsArrayTransformation($declaration);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\DTO\RequiredOption;

/**
 * @covers \WordPress\AiClient\Providers\Models\DTO\RequiredOption
 */
class RequiredOptionTest extends TestCase
{
    /**
     * Tests constructor and getter methods with string value.
     *
     * @return void
     */
    public function testConstructorAndGettersWithStringValue(): void
    {
        $name = 'api_key';
        $value = 'secret-key-123';

        $option = new RequiredOption($name, $value);

        $this->assertEquals($name, $option->getName());
        $this->assertEquals($value, $option->getValue());
    }

    /**
     * Tests with integer value.
     *
     * @return void
     */
    public function testWithIntegerValue(): void
    {
        $option = new RequiredOption('max_tokens', 1000);

        $this->assertEquals('max_tokens', $option->getName());
        $this->assertEquals(1000, $option->getValue());
        $this->assertIsInt($option->getValue());
    }

    /**
     * Tests with float value.
     *
     * @return void
     */
    public function testWithFloatValue(): void
    {
        $option = new RequiredOption('temperature', 0.7);

        $this->assertEquals('temperature', $option->getName());
        $this->assertEquals(0.7, $option->getValue());
        $this->assertIsFloat($option->getValue());
    }

    /**
     * Tests with boolean value.
     *
     * @return void
     */
    public function testWithBooleanValue(): void
    {
        $optionTrue = new RequiredOption('stream', true);
        $optionFalse = new RequiredOption('logprobs', false);

        $this->assertEquals('stream', $optionTrue->getName());
        $this->assertTrue($optionTrue->getValue());
        $this->assertIsBool($optionTrue->getValue());

        $this->assertEquals('logprobs', $optionFalse->getName());
        $this->assertFalse($optionFalse->getValue());
        $this->assertIsBool($optionFalse->getValue());
    }

    /**
     * Tests with null value.
     *
     * @return void
     */
    public function testWithNullValue(): void
    {
        $option = new RequiredOption('optional_field', null);

        $this->assertEquals('optional_field', $option->getName());
        $this->assertNull($option->getValue());
    }

    /**
     * Tests with array value.
     *
     * @return void
     */
    public function testWithArrayValue(): void
    {
        $arrayValue = ['option1', 'option2', 'option3'];
        $option = new RequiredOption('allowed_values', $arrayValue);

        $this->assertEquals('allowed_values', $option->getName());
        $this->assertEquals($arrayValue, $option->getValue());
        $this->assertIsArray($option->getValue());
    }

    /**
     * Tests with object/associative array value.
     *
     * @return void
     */
    public function testWithObjectValue(): void
    {
        $objectValue = [
            'type' => 'json_object',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'age' => ['type' => 'integer']
                ]
            ]
        ];
        $option = new RequiredOption('response_format', $objectValue);

        $this->assertEquals('response_format', $option->getName());
        $this->assertEquals($objectValue, $option->getValue());
        $this->assertIsArray($option->getValue());
    }

    /**
     * Tests JSON schema generation.
     *
     * @return void
     */
    public function testGetJsonSchema(): void
    {
        $schema = RequiredOption::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);

        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey(RequiredOption::KEY_NAME, $schema['properties']);
        $this->assertArrayHasKey(RequiredOption::KEY_VALUE, $schema['properties']);

        // Check name property
        $this->assertEquals('string', $schema['properties'][RequiredOption::KEY_NAME]['type']);
        $this->assertEquals('The option name.', $schema['properties'][RequiredOption::KEY_NAME]['description']);

        // Check value property with oneOf
        $this->assertArrayHasKey('oneOf', $schema['properties'][RequiredOption::KEY_VALUE]);
        $this->assertIsArray($schema['properties'][RequiredOption::KEY_VALUE]['oneOf']);
        $this->assertCount(6, $schema['properties'][RequiredOption::KEY_VALUE]['oneOf']);

        // Verify all allowed types
        $types = array_map(function ($item) {
            return $item['type'];
        }, $schema['properties'][RequiredOption::KEY_VALUE]['oneOf']);
        $this->assertContains('string', $types);
        $this->assertContains('number', $types);
        $this->assertContains('boolean', $types);
        $this->assertContains('null', $types);
        $this->assertContains('array', $types);
        $this->assertContains('object', $types);

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals([RequiredOption::KEY_NAME, RequiredOption::KEY_VALUE], $schema['required']);
    }

    /**
     * Tests array conversion with different value types.
     *
     * @return void
     */
    public function testToArrayWithDifferentValueTypes(): void
    {
        // String value
        $stringOption = new RequiredOption('string_opt', 'value');
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'string_opt', RequiredOption::KEY_VALUE => 'value'],
            $stringOption->toArray()
        );

        // Number values
        $intOption = new RequiredOption('int_opt', 42);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'int_opt', RequiredOption::KEY_VALUE => 42],
            $intOption->toArray()
        );

        $floatOption = new RequiredOption('float_opt', 3.14);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'float_opt', RequiredOption::KEY_VALUE => 3.14],
            $floatOption->toArray()
        );

        // Boolean value
        $boolOption = new RequiredOption('bool_opt', true);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'bool_opt', RequiredOption::KEY_VALUE => true],
            $boolOption->toArray()
        );

        // Null value
        $nullOption = new RequiredOption('null_opt', null);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'null_opt', RequiredOption::KEY_VALUE => null],
            $nullOption->toArray()
        );

        // Array value
        $arrayOption = new RequiredOption('array_opt', [1, 2, 3]);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'array_opt', RequiredOption::KEY_VALUE => [1, 2, 3]],
            $arrayOption->toArray()
        );

        // Object value
        $objectOption = new RequiredOption('object_opt', ['key' => 'value']);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'object_opt', RequiredOption::KEY_VALUE => ['key' => 'value']],
            $objectOption->toArray()
        );
    }

    /**
     * Tests creating from array with different value types.
     *
     * @return void
     */
    public function testFromArrayWithDifferentValueTypes(): void
    {
        // String value
        $stringOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'str', RequiredOption::KEY_VALUE => 'test']
        );
        $this->assertEquals('str', $stringOption->getName());
        $this->assertEquals('test', $stringOption->getValue());

        // Integer value
        $intOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'num', RequiredOption::KEY_VALUE => 100]
        );
        $this->assertEquals('num', $intOption->getName());
        $this->assertEquals(100, $intOption->getValue());

        // Float value
        $floatOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'float', RequiredOption::KEY_VALUE => 1.5]
        );
        $this->assertEquals('float', $floatOption->getName());
        $this->assertEquals(1.5, $floatOption->getValue());

        // Boolean value
        $boolOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'bool', RequiredOption::KEY_VALUE => false]
        );
        $this->assertEquals('bool', $boolOption->getName());
        $this->assertFalse($boolOption->getValue());

        // Null value
        $nullOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'nullable', RequiredOption::KEY_VALUE => null]
        );
        $this->assertEquals('nullable', $nullOption->getName());
        $this->assertNull($nullOption->getValue());

        // Array value
        $arrayOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'arr', RequiredOption::KEY_VALUE => ['a', 'b', 'c']]
        );
        $this->assertEquals('arr', $arrayOption->getName());
        $this->assertEquals(['a', 'b', 'c'], $arrayOption->getValue());

        // Object value
        $objectOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'obj', RequiredOption::KEY_VALUE => ['nested' => ['deep' => true]]]
        );
        $this->assertEquals('obj', $objectOption->getName());
        $this->assertEquals(['nested' => ['deep' => true]], $objectOption->getValue());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $testCases = [
            new RequiredOption('string', 'hello world'),
            new RequiredOption('integer', 42),
            new RequiredOption('float', 99.99),
            new RequiredOption('boolean', true),
            new RequiredOption('null', null),
            new RequiredOption('array', ['one', 'two', 'three']),
            new RequiredOption('object', ['type' => 'config', 'enabled' => true, 'settings' => ['a' => 1, 'b' => 2]])
        ];

        foreach ($testCases as $original) {
            $array = $original->toArray();
            $restored = RequiredOption::fromArray($array);

            $this->assertEquals($original->getName(), $restored->getName());
            $this->assertEquals($original->getValue(), $restored->getValue());
        }
    }

    /**
     * Tests JSON serialization.
     *
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $option = new RequiredOption('json_test', ['enabled' => true, 'count' => 5]);

        $json = json_encode($option);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals('json_test', $decoded[RequiredOption::KEY_NAME]);
        $this->assertEquals(['enabled' => true, 'count' => 5], $decoded[RequiredOption::KEY_VALUE]);
    }

    /**
     * Tests with empty string name.
     *
     * @return void
     */
    public function testWithEmptyStringName(): void
    {
        $option = new RequiredOption('', 'value');

        $this->assertEquals('', $option->getName());
        $this->assertEquals('value', $option->getValue());
    }

    /**
     * Tests with special characters in name.
     *
     * @return void
     */
    public function testWithSpecialCharactersInName(): void
    {
        $option = new RequiredOption('option-with_special.chars', 'value');

        $this->assertEquals('option-with_special.chars', $option->getName());
        $this->assertEquals('value', $option->getValue());
    }

    /**
     * Tests with deeply nested array value.
     *
     * @return void
     */
    public function testWithDeeplyNestedArrayValue(): void
    {
        $deeplyNested = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'value' => 'deep',
                            'array' => [1, 2, 3]
                        ]
                    ]
                ]
            ]
        ];

        $option = new RequiredOption('nested_config', $deeplyNested);
        $array = $option->toArray();

        $this->assertEquals($deeplyNested, $array['value']);

        // Test round trip
        $restored = RequiredOption::fromArray($array);
        $this->assertEquals($deeplyNested, $restored->getValue());
    }

    /**
     * Tests with mixed array containing different types.
     *
     * @return void
     */
    public function testWithMixedArrayValue(): void
    {
        $mixedArray = [
            'string',
            123,
            45.67,
            true,
            false,
            null,
            ['nested' => 'array'],
            ['another', 'array']
        ];

        $option = new RequiredOption('mixed_types', $mixedArray);
        $this->assertEquals($mixedArray, $option->getValue());

        // Verify exact types are preserved
        $value = $option->getValue();
        $this->assertIsString($value[0]);
        $this->assertIsInt($value[1]);
        $this->assertIsFloat($value[2]);
        $this->assertTrue($value[3]);
        $this->assertFalse($value[4]);
        $this->assertNull($value[5]);
        $this->assertIsArray($value[6]);
        $this->assertIsArray($value[7]);
    }

    /**
     * Tests implements correct interfaces.
     *
     * @return void
     */
    public function testImplementsCorrectInterfaces(): void
    {
        $option = new RequiredOption('test', 'value');

        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface::class,
            $option
        );
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface::class,
            $option
        );
        $this->assertInstanceOf(
            JsonSerializable::class,
            $option
        );
    }
}

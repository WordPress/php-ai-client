<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\DTO\RequiredOption;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

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
        $name = OptionEnum::maxTokens();
        $value = 'secret-key-123';

        $option = new RequiredOption($name, $value);

        $this->assertSame($name, $option->getName());
        $this->assertEquals($value, $option->getValue());
    }

    /**
     * Tests with integer value.
     *
     * @return void
     */
    public function testWithIntegerValue(): void
    {
        $option = new RequiredOption(OptionEnum::maxTokens(), 1000);

        $this->assertSame(OptionEnum::maxTokens(), $option->getName());
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
        $option = new RequiredOption(OptionEnum::temperature(), 0.7);

        $this->assertSame(OptionEnum::temperature(), $option->getName());
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
        $optionTrue = new RequiredOption(OptionEnum::webSearch(), true);
        $optionFalse = new RequiredOption(OptionEnum::logprobs(), false);

        $this->assertSame(OptionEnum::webSearch(), $optionTrue->getName());
        $this->assertTrue($optionTrue->getValue());
        $this->assertIsBool($optionTrue->getValue());

        $this->assertSame(OptionEnum::logprobs(), $optionFalse->getName());
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
        $option = new RequiredOption(OptionEnum::outputSchema(), null);

        $this->assertSame(OptionEnum::outputSchema(), $option->getName());
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
        $option = new RequiredOption(OptionEnum::stopSequences(), $arrayValue);

        $this->assertSame(OptionEnum::stopSequences(), $option->getName());
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
        $option = new RequiredOption(OptionEnum::outputSchema(), $objectValue);

        $this->assertSame(OptionEnum::outputSchema(), $option->getName());
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
        $this->assertArrayHasKey('enum', $schema['properties'][RequiredOption::KEY_NAME]);
        $this->assertIsArray($schema['properties'][RequiredOption::KEY_NAME]['enum']);
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
        $stringOption = new RequiredOption(OptionEnum::maxTokens(), 'value');
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'maxTokens', RequiredOption::KEY_VALUE => 'value'],
            $stringOption->toArray()
        );

        // Number values
        $intOption = new RequiredOption(OptionEnum::candidateCount(), 42);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'candidateCount', RequiredOption::KEY_VALUE => 42],
            $intOption->toArray()
        );

        $floatOption = new RequiredOption(OptionEnum::temperature(), 3.14);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'temperature', RequiredOption::KEY_VALUE => 3.14],
            $floatOption->toArray()
        );

        // Boolean value
        $boolOption = new RequiredOption(OptionEnum::webSearch(), true);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'webSearch', RequiredOption::KEY_VALUE => true],
            $boolOption->toArray()
        );

        // Null value
        $nullOption = new RequiredOption(OptionEnum::outputSchema(), null);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'outputSchema', RequiredOption::KEY_VALUE => null],
            $nullOption->toArray()
        );

        // Array value
        $arrayOption = new RequiredOption(OptionEnum::stopSequences(), [1, 2, 3]);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'stopSequences', RequiredOption::KEY_VALUE => [1, 2, 3]],
            $arrayOption->toArray()
        );

        // Object value
        $objectOption = new RequiredOption(OptionEnum::outputSchema(), ['key' => 'value']);
        $this->assertEquals(
            [RequiredOption::KEY_NAME => 'outputSchema', RequiredOption::KEY_VALUE => ['key' => 'value']],
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
            [RequiredOption::KEY_NAME => 'maxTokens', RequiredOption::KEY_VALUE => 'test']
        );
        $this->assertEquals(OptionEnum::maxTokens(), $stringOption->getName());
        $this->assertEquals('test', $stringOption->getValue());

        // Integer value
        $intOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'candidateCount', RequiredOption::KEY_VALUE => 100]
        );
        $this->assertEquals(OptionEnum::candidateCount(), $intOption->getName());
        $this->assertEquals(100, $intOption->getValue());

        // Float value
        $floatOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'temperature', RequiredOption::KEY_VALUE => 1.5]
        );
        $this->assertEquals(OptionEnum::temperature(), $floatOption->getName());
        $this->assertEquals(1.5, $floatOption->getValue());

        // Boolean value
        $boolOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'logprobs', RequiredOption::KEY_VALUE => false]
        );
        $this->assertEquals(OptionEnum::logprobs(), $boolOption->getName());
        $this->assertFalse($boolOption->getValue());

        // Null value
        $nullOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'outputSchema', RequiredOption::KEY_VALUE => null]
        );
        $this->assertEquals(OptionEnum::outputSchema(), $nullOption->getName());
        $this->assertNull($nullOption->getValue());

        // Array value
        $arrayOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'stopSequences', RequiredOption::KEY_VALUE => ['a', 'b', 'c']]
        );
        $this->assertEquals(OptionEnum::stopSequences(), $arrayOption->getName());
        $this->assertEquals(['a', 'b', 'c'], $arrayOption->getValue());

        // Object value
        $objectOption = RequiredOption::fromArray(
            [RequiredOption::KEY_NAME => 'outputSchema', RequiredOption::KEY_VALUE => ['nested' => ['deep' => true]]]
        );
        $this->assertEquals(OptionEnum::outputSchema(), $objectOption->getName());
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
            new RequiredOption(OptionEnum::maxTokens(), 'hello world'),
            new RequiredOption(OptionEnum::candidateCount(), 42),
            new RequiredOption(OptionEnum::temperature(), 99.99),
            new RequiredOption(OptionEnum::webSearch(), true),
            new RequiredOption(OptionEnum::outputSchema(), null),
            new RequiredOption(OptionEnum::stopSequences(), ['one', 'two', 'three']),
            new RequiredOption(OptionEnum::customOptions(), ['type' => 'config', 'enabled' => true, 'settings' => ['a' => 1, 'b' => 2]])
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
        $option = new RequiredOption(OptionEnum::outputSchema(), ['enabled' => true, 'count' => 5]);

        $json = json_encode($option);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals('outputSchema', $decoded[RequiredOption::KEY_NAME]);
        $this->assertEquals(['enabled' => true, 'count' => 5], $decoded[RequiredOption::KEY_VALUE]);
    }

    /**
     * Tests with custom options enum.
     *
     * @return void
     */
    public function testWithCustomOptions(): void
    {
        $option = new RequiredOption(OptionEnum::customOptions(), ['key' => 'value']);

        $this->assertEquals(OptionEnum::customOptions(), $option->getName());
        $this->assertEquals(['key' => 'value'], $option->getValue());
    }

    /**
     * Tests with input modalities enum.
     *
     * @return void
     */
    public function testWithInputModalitiesEnum(): void
    {
        $option = new RequiredOption(OptionEnum::inputModalities(), ['text', 'image']);

        $this->assertEquals(OptionEnum::inputModalities(), $option->getName());
        $this->assertEquals(['text', 'image'], $option->getValue());
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

        $option = new RequiredOption(OptionEnum::outputSchema(), $deeplyNested);
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

        $option = new RequiredOption(OptionEnum::customOptions(), $mixedArray);
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
        $option = new RequiredOption(OptionEnum::maxTokens(), 'value');

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

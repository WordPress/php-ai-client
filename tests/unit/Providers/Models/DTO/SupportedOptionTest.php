<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;

/**
 * @covers \WordPress\AiClient\Providers\Models\DTO\SupportedOption
 */
class SupportedOptionTest extends TestCase
{
    /**
     * Tests constructor and getter methods with string values.
     *
     * @return void
     */
    public function testConstructorAndGettersWithStringValues(): void
    {
        $name = 'model';
        $values = ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo'];

        $option = new SupportedOption($name, $values);

        $this->assertEquals($name, $option->getName());
        $this->assertEquals($values, $option->getSupportedValues());
    }

    /**
     * Tests isSupportedValue method.
     *
     * @return void
     */
    public function testIsSupportedValue(): void
    {
        $option = new SupportedOption('temperature', [0.0, 0.5, 1.0, 1.5, 2.0]);

        $this->assertTrue($option->isSupportedValue(0.0));
        $this->assertTrue($option->isSupportedValue(0.5));
        $this->assertTrue($option->isSupportedValue(1.0));
        $this->assertTrue($option->isSupportedValue(1.5));
        $this->assertTrue($option->isSupportedValue(2.0));

        $this->assertFalse($option->isSupportedValue(0.7));
        $this->assertFalse($option->isSupportedValue(2.5));
        $this->assertFalse($option->isSupportedValue('1.0')); // String vs float
        $this->assertFalse($option->isSupportedValue(null));
    }

    /**
     * Tests with integer values.
     *
     * @return void
     */
    public function testWithIntegerValues(): void
    {
        $option = new SupportedOption('max_tokens', [100, 500, 1000, 2000, 4000]);

        $this->assertEquals('max_tokens', $option->getName());
        $this->assertEquals([100, 500, 1000, 2000, 4000], $option->getSupportedValues());

        $this->assertTrue($option->isSupportedValue(100));
        $this->assertTrue($option->isSupportedValue(4000));
        $this->assertFalse($option->isSupportedValue(150));
        $this->assertFalse($option->isSupportedValue(100.0)); // Float vs int
    }

    /**
     * Tests with boolean values.
     *
     * @return void
     */
    public function testWithBooleanValues(): void
    {
        $option = new SupportedOption('stream', [true, false]);

        $this->assertEquals('stream', $option->getName());
        $this->assertEquals([true, false], $option->getSupportedValues());

        $this->assertTrue($option->isSupportedValue(true));
        $this->assertTrue($option->isSupportedValue(false));
        $this->assertFalse($option->isSupportedValue(1)); // Int vs bool
        $this->assertFalse($option->isSupportedValue('true')); // String vs bool
    }

    /**
     * Tests with null in supported values.
     *
     * @return void
     */
    public function testWithNullValue(): void
    {
        $option = new SupportedOption('optional_param', ['value1', 'value2', null]);

        $this->assertTrue($option->isSupportedValue('value1'));
        $this->assertTrue($option->isSupportedValue('value2'));
        $this->assertTrue($option->isSupportedValue(null));
        $this->assertFalse($option->isSupportedValue('value3'));
    }

    /**
     * Tests with array values.
     *
     * @return void
     */
    public function testWithArrayValues(): void
    {
        $option = new SupportedOption('dimensions', [[256, 256], [512, 512], [1024, 1024]]);

        $this->assertEquals('dimensions', $option->getName());
        $supportedValues = $option->getSupportedValues();
        $this->assertCount(3, $supportedValues);

        $this->assertTrue($option->isSupportedValue([256, 256]));
        $this->assertTrue($option->isSupportedValue([512, 512]));
        $this->assertFalse($option->isSupportedValue([256, 512])); // Different combination
        $this->assertFalse($option->isSupportedValue([256])); // Different length
    }

    /**
     * Tests with object/associative array values.
     *
     * @return void
     */
    public function testWithObjectValues(): void
    {
        $format1 = ['type' => 'json_object'];
        $format2 = ['type' => 'text'];
        $option = new SupportedOption('response_format', [$format1, $format2]);

        $this->assertTrue($option->isSupportedValue(['type' => 'json_object']));
        $this->assertTrue($option->isSupportedValue(['type' => 'text']));
        $this->assertFalse($option->isSupportedValue(['type' => 'xml']));
        $this->assertFalse($option->isSupportedValue(['type' => 'json_object', 'extra' => 'field']));
    }

    /**
     * Tests JSON schema generation.
     *
     * @return void
     */
    public function testGetJsonSchema(): void
    {
        $schema = SupportedOption::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);

        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('supportedValues', $schema['properties']);

        // Check name property
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertEquals('The option name.', $schema['properties']['name']['description']);

        // Check supportedValues property
        $this->assertEquals('array', $schema['properties']['supportedValues']['type']);
        $this->assertArrayHasKey('items', $schema['properties']['supportedValues']);
        $this->assertArrayHasKey('oneOf', $schema['properties']['supportedValues']['items']);

        // Verify all allowed types in items
        $types = array_map(function ($item) {
            return $item['type'];
        }, $schema['properties']['supportedValues']['items']['oneOf']);
        $this->assertContains('string', $types);
        $this->assertContains('number', $types);
        $this->assertContains('boolean', $types);
        $this->assertContains('null', $types);
        $this->assertContains('array', $types);
        $this->assertContains('object', $types);

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals(['name', 'supportedValues'], $schema['required']);
    }

    /**
     * Tests array conversion.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $option = new SupportedOption('style', ['realistic', 'artistic', 'cartoon', 'abstract']);
        $array = $option->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('style', $array['name']);
        $this->assertEquals(['realistic', 'artistic', 'cartoon', 'abstract'], $array['supportedValues']);
        $this->assertCount(2, $array);
    }

    /**
     * Tests creating from array.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $data = [
            'name' => 'voice',
            'supportedValues' => ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer']
        ];

        $option = SupportedOption::fromArray($data);

        $this->assertInstanceOf(SupportedOption::class, $option);
        $this->assertEquals('voice', $option->getName());
        $this->assertEquals(['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'], $option->getSupportedValues());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $original = new SupportedOption(
            'complex_option',
            [
                'string',
                123,
                45.67,
                true,
                false,
                null,
                ['array', 'values'],
                ['key' => 'value']
            ]
        );

        $array = $original->toArray();
        $restored = SupportedOption::fromArray($array);

        $this->assertEquals($original->getName(), $restored->getName());
        $this->assertEquals($original->getSupportedValues(), $restored->getSupportedValues());

        // Verify each value type is preserved
        $values = $restored->getSupportedValues();
        $this->assertIsString($values[0]);
        $this->assertIsInt($values[1]);
        $this->assertIsFloat($values[2]);
        $this->assertTrue($values[3]);
        $this->assertFalse($values[4]);
        $this->assertNull($values[5]);
        $this->assertIsArray($values[6]);
        $this->assertIsArray($values[7]);
    }

    /**
     * Tests JSON serialization.
     *
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $option = new SupportedOption('quality', ['low', 'medium', 'high', 'ultra']);

        $json = json_encode($option);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals('quality', $decoded['name']);
        $this->assertEquals(['low', 'medium', 'high', 'ultra'], $decoded['supportedValues']);
    }

    /**
     * Tests with empty supported values array.
     *
     * @return void
     */
    public function testWithEmptySupportedValues(): void
    {
        $option = new SupportedOption('empty_option', []);

        $this->assertEquals('empty_option', $option->getName());
        $this->assertEquals([], $option->getSupportedValues());
        $this->assertFalse($option->isSupportedValue('anything'));
        $this->assertFalse($option->isSupportedValue(null));
    }

    /**
     * Tests with duplicate values in array.
     *
     * @return void
     */
    public function testWithDuplicateValues(): void
    {
        $option = new SupportedOption('duplicates', ['a', 'b', 'a', 'c', 'b']);

        $this->assertEquals(['a', 'b', 'a', 'c', 'b'], $option->getSupportedValues());
        $this->assertTrue($option->isSupportedValue('a'));
        $this->assertTrue($option->isSupportedValue('b'));
        $this->assertTrue($option->isSupportedValue('c'));
    }

    /**
     * Tests with special characters in name.
     *
     * @return void
     */
    public function testWithSpecialCharactersInName(): void
    {
        $option = new SupportedOption('option-with_special.chars:test', ['value1', 'value2']);

        $this->assertEquals('option-with_special.chars:test', $option->getName());
        $array = $option->toArray();
        $this->assertEquals('option-with_special.chars:test', $array['name']);
    }

    /**
     * Tests strict type checking in isSupportedValue.
     *
     * @return void
     */
    public function testStrictTypeCheckingInIsSupportedValue(): void
    {
        $option = new SupportedOption('mixed', [0, '0', false, '', null]);

        // Each value should only match itself exactly
        $this->assertTrue($option->isSupportedValue(0));
        $this->assertTrue($option->isSupportedValue('0'));
        $this->assertTrue($option->isSupportedValue(false));
        $this->assertTrue($option->isSupportedValue(''));
        $this->assertTrue($option->isSupportedValue(null));

        // These should not match due to strict comparison
        $this->assertFalse($option->isSupportedValue(0.0)); // float vs int
        $this->assertFalse($option->isSupportedValue('false')); // string vs bool
        $this->assertFalse($option->isSupportedValue([])); // empty array vs empty string
    }

    /**
     * Tests array values are properly indexed.
     *
     * @return void
     */
    public function testArrayValuesProperlyIndexed(): void
    {
        $option = new SupportedOption('indexed', ['first', 'second', 'third']);
        $array = $option->toArray();

        // Ensure supportedValues array has numeric keys starting from 0
        $this->assertEquals([0, 1, 2], array_keys($array['supportedValues']));
    }

    /**
     * Tests with deeply nested structures.
     *
     * @return void
     */
    public function testWithDeeplyNestedStructures(): void
    {
        $deeplyNested = [
            [
                'level1' => [
                    'level2' => [
                        'level3' => ['value' => 'deep']
                    ]
                ]
            ],
            [
                'level1' => [
                    'level2' => [
                        'level3' => ['value' => 'also deep']
                    ]
                ]
            ]
        ];

        $option = new SupportedOption('nested_configs', $deeplyNested);

        $this->assertTrue($option->isSupportedValue($deeplyNested[0]));
        $this->assertTrue($option->isSupportedValue($deeplyNested[1]));

        // Test round trip
        $array = $option->toArray();
        $restored = SupportedOption::fromArray($array);
        $this->assertEquals($deeplyNested, $restored->getSupportedValues());
    }

    /**
     * Tests implements correct interfaces.
     *
     * @return void
     */
    public function testImplementsCorrectInterfaces(): void
    {
        $option = new SupportedOption('test', ['value']);

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


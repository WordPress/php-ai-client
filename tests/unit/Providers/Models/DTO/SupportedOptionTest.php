<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface;
use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

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
        $name = OptionEnum::outputModalities();
        $values = ['text', 'image', 'audio'];

        $option = new SupportedOption($name, $values);

        $this->assertSame($name, $option->getName());
        $this->assertEquals($values, $option->getSupportedValues());
    }

    /**
     * Tests isSupportedValue method.
     *
     * @return void
     */
    public function testIsSupportedValue(): void
    {
        $option = new SupportedOption(OptionEnum::temperature(), [0.0, 0.5, 1.0, 1.5, 2.0]);

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
        $option = new SupportedOption(OptionEnum::maxTokens(), [100, 500, 1000, 2000, 4000]);

        $this->assertSame(OptionEnum::maxTokens(), $option->getName());
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
        $option = new SupportedOption(OptionEnum::webSearch(), [true, false]);

        $this->assertSame(OptionEnum::webSearch(), $option->getName());
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
        $option = new SupportedOption(OptionEnum::outputSchema(), ['value1', 'value2', null]);

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
        $option = new SupportedOption(OptionEnum::outputMediaAspectRatio(), [[256, 256], [512, 512], [1024, 1024]]);

        $this->assertSame(OptionEnum::outputMediaAspectRatio(), $option->getName());
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
        $option = new SupportedOption(OptionEnum::outputSchema(), [$format1, $format2]);

        $this->assertTrue($option->isSupportedValue(['type' => 'json_object']));
        $this->assertTrue($option->isSupportedValue(['type' => 'text']));
        $this->assertFalse($option->isSupportedValue(['type' => 'xml']));
        $this->assertFalse($option->isSupportedValue(['type' => 'json_object', 'extra' => 'field']));
    }

    /**
     * Tests that isSupportedValue correctly handles unordered array values.
     *
     * @return void
     */
    public function testIsSupportedValueWithUnorderedArray(): void
    {
        // Just use any option enum value for the name.
        $option = new SupportedOption(
            OptionEnum::outputSpeechVoice(),
            [['red', 'green', 'blue'], ['yellow', 'orange']]
        );

        // Test with an array that has the same elements but in a different order
        $this->assertTrue($option->isSupportedValue(['blue', 'red', 'green']));
        $this->assertTrue($option->isSupportedValue(['orange', 'yellow']));

        // Test with an array that has different elements or missing elements
        $this->assertFalse($option->isSupportedValue(['red', 'green']));
        $this->assertFalse($option->isSupportedValue(['red', 'green', 'blue', 'purple']));
        $this->assertFalse($option->isSupportedValue(['red', 'yellow', 'blue']));
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
        $this->assertArrayHasKey(SupportedOption::KEY_NAME, $schema['properties']);
        $this->assertArrayHasKey(SupportedOption::KEY_SUPPORTED_VALUES, $schema['properties']);

        // Check name property
        $this->assertEquals('string', $schema['properties'][SupportedOption::KEY_NAME]['type']);
        $this->assertEquals('The option name.', $schema['properties'][SupportedOption::KEY_NAME]['description']);

        // Check supportedValues property
        $this->assertEquals('array', $schema['properties'][SupportedOption::KEY_SUPPORTED_VALUES]['type']);
        $this->assertArrayHasKey('items', $schema['properties'][SupportedOption::KEY_SUPPORTED_VALUES]);
        $this->assertArrayHasKey('oneOf', $schema['properties'][SupportedOption::KEY_SUPPORTED_VALUES]['items']);

        // Verify all allowed types in items
        $types = array_map(function ($item) {
            return $item['type'];
        }, $schema['properties'][SupportedOption::KEY_SUPPORTED_VALUES]['items']['oneOf']);
        $this->assertContains('string', $types);
        $this->assertContains('number', $types);
        $this->assertContains('boolean', $types);
        $this->assertContains('null', $types);
        $this->assertContains('array', $types);
        $this->assertContains('object', $types);

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals([SupportedOption::KEY_NAME], $schema['required']);
    }

    /**
     * Tests array conversion.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $option = new SupportedOption(OptionEnum::outputFileType(), ['realistic', 'artistic', 'cartoon', 'abstract']);
        $array = $option->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(OptionEnum::outputFileType()->value, $array[SupportedOption::KEY_NAME]);
        $this->assertEquals(
            ['realistic', 'artistic', 'cartoon', 'abstract'],
            $array[SupportedOption::KEY_SUPPORTED_VALUES]
        );
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
            SupportedOption::KEY_NAME => OptionEnum::outputFileType()->value,
            SupportedOption::KEY_SUPPORTED_VALUES => ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer']
        ];

        $option = SupportedOption::fromArray($data);

        $this->assertInstanceOf(SupportedOption::class, $option);
        $this->assertEquals(OptionEnum::outputFileType()->value, $option->getName()->value);
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
            OptionEnum::customOptions(),
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

        $this->assertSame($original->getName(), $restored->getName());
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
        $option = new SupportedOption(OptionEnum::candidateCount(), ['low', 'medium', 'high', 'ultra']);

        $json = json_encode($option);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals(OptionEnum::candidateCount()->value, $decoded[SupportedOption::KEY_NAME]);
        $this->assertEquals(['low', 'medium', 'high', 'ultra'], $decoded[SupportedOption::KEY_SUPPORTED_VALUES]);
    }

    /**
     * Tests with empty supported values array.
     *
     * @return void
     */
    public function testWithEmptySupportedValues(): void
    {
        $option = new SupportedOption(OptionEnum::stopSequences(), []);

        $this->assertSame(OptionEnum::stopSequences(), $option->getName());
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
        $option = new SupportedOption(OptionEnum::stopSequences(), ['a', 'b', 'a', 'c', 'b']);

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
        $option = new SupportedOption(OptionEnum::customOptions(), ['value1', 'value2']);

        $this->assertSame(OptionEnum::customOptions(), $option->getName());
        $array = $option->toArray();
        $this->assertEquals(OptionEnum::customOptions()->value, $array[SupportedOption::KEY_NAME]);
    }

    /**
     * Tests strict type checking in isSupportedValue.
     *
     * @return void
     */
    public function testStrictTypeCheckingInIsSupportedValue(): void
    {
        $option = new SupportedOption(OptionEnum::customOptions(), [0, '0', false, '', null]);

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
        $option = new SupportedOption(OptionEnum::stopSequences(), ['first', 'second', 'third']);
        $array = $option->toArray();

        // Ensure supportedValues array has numeric keys starting from 0
        $this->assertEquals([0, 1, 2], array_keys($array[SupportedOption::KEY_SUPPORTED_VALUES]));
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

        $option = new SupportedOption(OptionEnum::outputSchema(), $deeplyNested);

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
        $option = new SupportedOption(OptionEnum::maxTokens(), ['value']);

        $this->assertInstanceOf(
            WithArrayTransformationInterface::class,
            $option
        );
        $this->assertInstanceOf(
            WithJsonSchemaInterface::class,
            $option
        );
        $this->assertInstanceOf(
            JsonSerializable::class,
            $option
        );
    }

    /**
     * Tests with null supportedValues (any value is supported).
     *
     * @return void
     */
    public function testWithNullSupportedValues(): void
    {
        $option = new SupportedOption(OptionEnum::temperature());

        $this->assertSame(OptionEnum::temperature(), $option->getName());
        $this->assertNull($option->getSupportedValues());

        // Any value should be supported when supportedValues is null
        $this->assertTrue($option->isSupportedValue('string'));
        $this->assertTrue($option->isSupportedValue(123));
        $this->assertTrue($option->isSupportedValue(45.67));
        $this->assertTrue($option->isSupportedValue(true));
        $this->assertTrue($option->isSupportedValue(false));
        $this->assertTrue($option->isSupportedValue(null));
        $this->assertTrue($option->isSupportedValue(['array']));
        $this->assertTrue($option->isSupportedValue(['key' => 'value']));
        $this->assertTrue($option->isSupportedValue(new \stdClass()));
    }

    /**
     * Tests toArray with null supportedValues.
     *
     * @return void
     */
    public function testToArrayWithNullSupportedValues(): void
    {
        $option = new SupportedOption(OptionEnum::topP());
        $array = $option->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(OptionEnum::topP()->value, $array[SupportedOption::KEY_NAME]);
        $this->assertArrayNotHasKey(SupportedOption::KEY_SUPPORTED_VALUES, $array);
        $this->assertCount(1, $array);
    }

    /**
     * Tests fromArray with missing supportedValues.
     *
     * @return void
     */
    public function testFromArrayWithMissingSupportedValues(): void
    {
        $data = [
            SupportedOption::KEY_NAME => OptionEnum::temperature()->value
        ];

        $option = SupportedOption::fromArray($data);

        $this->assertInstanceOf(SupportedOption::class, $option);
        $this->assertEquals(OptionEnum::temperature()->value, $option->getName()->value);
        $this->assertNull($option->getSupportedValues());
        $this->assertTrue($option->isSupportedValue('anything'));
    }

    /**
     * Tests round-trip with null supportedValues.
     *
     * @return void
     */
    public function testRoundTripWithNullSupportedValues(): void
    {
        $original = new SupportedOption(OptionEnum::topK());

        $array = $original->toArray();
        $restored = SupportedOption::fromArray($array);

        $this->assertSame($original->getName(), $restored->getName());
        $this->assertEquals($original->getSupportedValues(), $restored->getSupportedValues());
        $this->assertNull($restored->getSupportedValues());
    }

    /**
     * Tests JSON serialization with null supportedValues.
     *
     * @return void
     */
    public function testJsonSerializationWithNullSupportedValues(): void
    {
        $option = new SupportedOption(OptionEnum::customOptions());

        $json = json_encode($option);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals(OptionEnum::customOptions()->value, $decoded[SupportedOption::KEY_NAME]);
        $this->assertArrayNotHasKey(SupportedOption::KEY_SUPPORTED_VALUES, $decoded);
    }

    /**
     * Tests JSON schema reflects optional supportedValues.
     *
     * @return void
     */
    public function testJsonSchemaReflectsOptionalSupportedValues(): void
    {
        $schema = SupportedOption::getJsonSchema();

        // Check that supportedValues is not in required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals([SupportedOption::KEY_NAME], $schema['required']);
        $this->assertNotContains(SupportedOption::KEY_SUPPORTED_VALUES, $schema['required']);
    }
}

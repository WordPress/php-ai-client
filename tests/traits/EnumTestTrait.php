<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\traits;

use WordPress\AiClient\Common\AbstractEnum;

/**
 * Trait for testing enum classes.
 */
trait EnumTestTrait
{
    /**
     * Gets the enum class name to test.
     *
     * @return class-string<AbstractEnum> The enum class name.
     */
    abstract protected function getEnumClass(): string;

    /**
     * Gets expected enum values and their constant names.
     *
     * @return array<string, string> Array of CONSTANT_NAME => value.
     */
    abstract protected function getExpectedValues(): array;

    /**
     * Tests that the enum has expected values.
     */
    public function testEnumHasExpectedValues(): void
    {
        $enumClass = $this->getEnumClass();
        $expectedValues = $this->getExpectedValues();

        $actualValues = $enumClass::getValues();

        // Since getValues() now returns just the values, we need to extract values from expected
        $expectedValuesList = array_values($expectedValues);

        $this->assertEquals($expectedValuesList, $actualValues);
    }

    /**
     * Tests that enum cases return correct instances.
     */
    public function testEnumCasesReturnCorrectInstances(): void
    {
        $enumClass = $this->getEnumClass();
        $expectedValues = $this->getExpectedValues();

        $cases = $enumClass::cases();

        $this->assertCount(count($expectedValues), $cases);

        foreach ($cases as $case) {
            $this->assertInstanceOf($enumClass, $case);
            $this->assertContains($case->value, $expectedValues);
            $this->assertArrayHasKey($case->name, $expectedValues);
            $this->assertEquals($expectedValues[$case->name], $case->value);
        }
    }

    /**
     * Tests that the from() method works correctly.
     */
    public function testEnumFromMethodWorks(): void
    {
        $enumClass = $this->getEnumClass();
        $expectedValues = $this->getExpectedValues();

        foreach ($expectedValues as $name => $value) {
            $enum = $enumClass::from($value);
            $this->assertInstanceOf($enumClass, $enum);
            $this->assertEquals($value, $enum->value);
            $this->assertEquals($name, $enum->name);
        }
    }

    /**
     * Tests that the tryFrom() method works correctly.
     */
    public function testEnumTryFromMethodWorks(): void
    {
        $enumClass = $this->getEnumClass();
        $expectedValues = $this->getExpectedValues();

        foreach ($expectedValues as $value) {
            $enum = $enumClass::tryFrom($value);
            $this->assertInstanceOf($enumClass, $enum);
        }

        // Test invalid value
        $invalidEnum = $enumClass::tryFrom('definitely_not_a_valid_value_12345');
        $this->assertNull($invalidEnum);
    }

    /**
     * Tests enum singleton behavior.
     */
    public function testEnumSingletonBehavior(): void
    {
        $enumClass = $this->getEnumClass();
        $expectedValues = $this->getExpectedValues();

        if (empty($expectedValues)) {
            $this->markTestSkipped('No enum values to test');
        }

        $firstValue = reset($expectedValues);

        $enum1 = $enumClass::from($firstValue);
        $enum2 = $enumClass::from($firstValue);

        $this->assertSame($enum1, $enum2);
    }

    /**
     * Tests that enum properties are read-only.
     */
    public function testEnumPropertiesAreReadOnly(): void
    {
        $enumClass = $this->getEnumClass();
        $expectedValues = $this->getExpectedValues();

        if (empty($expectedValues)) {
            $this->markTestSkipped('No enum values to test');
        }

        $firstValue = reset($expectedValues);
        $enum = $enumClass::from($firstValue);

        $this->expectException(\BadMethodCallException::class);
        $enum->value = 'modified';
    }
}

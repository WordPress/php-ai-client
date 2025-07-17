<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit;

use WordPress\AiClient\Common\AbstractEnum;

/**
 * Trait for testing enum classes
 */
trait EnumTestTrait
{
    /**
     * Get the enum class name to test
     *
     * @return class-string<AbstractEnum>
     */
    abstract protected function getEnumClass(): string;

    /**
     * Get expected enum values and their constant names
     *
     * @return array<string, string|int> Array of CONSTANT_NAME => value
     */
    abstract protected function getExpectedValues(): array;

    public function testEnumHasExpectedValues(): void
    {
        $enumClass = $this->getEnumClass();
        $expectedValues = $this->getExpectedValues();

        $actualValues = $enumClass::getValues();

        $this->assertEquals($expectedValues, $actualValues);
    }

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

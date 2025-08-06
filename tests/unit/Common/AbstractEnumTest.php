<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Common;

use BadMethodCallException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\Tests\mocks\Enums\InvalidNameTestEnum;
use WordPress\AiClient\Tests\mocks\Enums\InvalidTypeTestEnum;
use WordPress\AiClient\Tests\mocks\Enums\ValidTestEnum;

/**
 * @covers \WordPress\AiClient\Common\AbstractEnum
 */
class AbstractEnumTest extends TestCase
{
    /**
     * Tests that from() creates an enum instance with a valid value.
     */
    public function testFromWithValidValue(): void
    {
        $enum = ValidTestEnum::from('first');
        $this->assertInstanceOf(ValidTestEnum::class, $enum);
        $this->assertSame('first', $enum->value);
        $this->assertSame('FIRST_NAME', $enum->name);
    }


    /**
     * Tests that from() throws an exception for invalid values.
     */
    public function testFromWithInvalidValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'invalid is not a valid backing value for enum WordPress\AiClient\Tests\mocks\Enums\ValidTestEnum'
        );
        ValidTestEnum::from('invalid');
    }

    /**
     * Tests that tryFrom() returns an enum instance for valid values.
     */
    public function testTryFromWithValidValue(): void
    {
        $enum = ValidTestEnum::tryFrom('first');
        $this->assertInstanceOf(ValidTestEnum::class, $enum);
        $this->assertSame('first', $enum->value);
    }

    /**
     * Tests that tryFrom() returns null for invalid values.
     */
    public function testTryFromWithInvalidValueReturnsNull(): void
    {
        $enum = ValidTestEnum::tryFrom('invalid');
        $this->assertNull($enum);
    }

    /**
     * Tests that cases() returns all enum instances.
     */
    public function testCasesReturnsAllEnumInstances(): void
    {
        $cases = ValidTestEnum::cases();
        $this->assertCount(2, $cases);

        $values = array_map(fn($case) => $case->value, $cases);
        $this->assertContains('first', $values);
        $this->assertContains('last', $values);

        $names = array_map(fn($case) => $case->name, $cases);
        $this->assertContains('FIRST_NAME', $names);
        $this->assertContains('LAST_NAME', $names);
    }

    /**
     * Tests that enum instances are singletons.
     */
    public function testSingletonBehavior(): void
    {
        $enum1 = ValidTestEnum::from('first');
        $enum2 = ValidTestEnum::from('first');
        $enum3 = ValidTestEnum::firstName();

        $this->assertSame($enum1, $enum2);
        $this->assertSame($enum1, $enum3);
    }

    /**
     * Tests static factory methods for creating enum instances.
     */
    public function testStaticFactoryMethods(): void
    {
        $firstName = ValidTestEnum::firstName();
        $this->assertSame('first', $firstName->value);
        $this->assertSame('FIRST_NAME', $firstName->name);

        $lastName = ValidTestEnum::lastName();
        $this->assertSame('last', $lastName->value);
        $this->assertSame('LAST_NAME', $lastName->name);
    }

    /**
     * Tests that invalid static methods throw exceptions.
     */
    public function testInvalidStaticMethodThrowsException(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method WordPress\AiClient\Tests\mocks\Enums\ValidTestEnum::invalidMethod does not exist'
        );
        ValidTestEnum::invalidMethod();
    }

    /**
     * Tests the is* check methods.
     */
    public function testIsCheckMethods(): void
    {
        $enum = ValidTestEnum::firstName();

        $this->assertTrue($enum->isFirstName());
        $this->assertFalse($enum->isLastName());
    }

    /**
     * Tests that invalid is* methods throw exceptions.
     */
    public function testInvalidIsMethodThrowsException(): void
    {
        $enum = ValidTestEnum::firstName();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method WordPress\AiClient\Tests\mocks\Enums\ValidTestEnum::isInvalidMethod does not exist'
        );
        $enum->isInvalidMethod();
    }

    /**
     * Tests the equals() method with various values.
     */
    public function testEqualsWithSameValue(): void
    {
        $enum = ValidTestEnum::firstName();

        $this->assertTrue($enum->equals('first'));
        $this->assertTrue($enum->equals(ValidTestEnum::firstName()));
        $this->assertFalse($enum->equals('last'));
        $this->assertFalse($enum->equals(ValidTestEnum::lastName()));
    }


    /**
     * Tests the is() method for identity comparison.
     */
    public function testIsMethodForIdentityComparison(): void
    {
        $enum1 = ValidTestEnum::firstName();
        $enum2 = ValidTestEnum::firstName();
        $enum3 = ValidTestEnum::lastName();

        $this->assertTrue($enum1->is($enum2)); // Same instance
        $this->assertFalse($enum1->is($enum3)); // Different instance
    }

    /**
     * Tests that getValues() returns all valid enum values.
     */
    public function testGetValuesReturnsAllValidValues(): void
    {
        $values = ValidTestEnum::getValues();

        $this->assertSame(['first', 'last'], $values);
    }

    /**
     * Tests the isValidValue() method.
     */
    public function testIsValidValue(): void
    {
        $this->assertTrue(ValidTestEnum::isValidValue('first'));
        $this->assertTrue(ValidTestEnum::isValidValue('last'));

        $this->assertFalse(ValidTestEnum::isValidValue('invalid'));
    }

    /**
     * Tests that enum properties are read-only.
     */
    public function testPropertiesAreReadOnly(): void
    {
        $enum = ValidTestEnum::firstName();
        $className = ValidTestEnum::class;

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Cannot modify property ' . $className . '::value - enum properties are read-only'
        );
        $enum->value = 'modified';
    }

    /**
     * Tests that accessing invalid properties throws exceptions.
     */
    public function testInvalidPropertyAccessThrowsException(): void
    {
        $enum = ValidTestEnum::firstName();
        $className = ValidTestEnum::class;

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Property ' . $className . '::invalid does not exist'
        );
        $enum->invalid;
    }

    /**
     * Tests the __toString() method.
     */
    public function testToString(): void
    {
        $stringEnum = ValidTestEnum::firstName();

        $this->assertSame('first', (string) $stringEnum);
    }

    /**
     * Tests that invalid constant names throw exceptions.
     */
    public function testInvalidConstantNameThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Invalid enum constant name "invalid_name" in ' .
            'WordPress\AiClient\Tests\mocks\Enums\InvalidNameTestEnum. Constants must be UPPER_SNAKE_CASE.'
        );

        InvalidNameTestEnum::cases();
    }

    /**
     * Tests that invalid constant types throw exceptions.
     */
    public function testInvalidConstantTypeThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Invalid enum value type for constant ' .
            'WordPress\AiClient\Tests\mocks\Enums\InvalidTypeTestEnum::INT_VALUE. ' .
            'Only string values are allowed, integer given.'
        );

        InvalidTypeTestEnum::cases();
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Common;

use BadMethodCallException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \WordPress\AiClient\Common\AbstractEnum
 */
class AbstractEnumTest extends TestCase
{
    public function testFromWithValidValue(): void
    {
        $enum = ValidTestEnum::from('first');
        $this->assertInstanceOf(ValidTestEnum::class, $enum);
        $this->assertSame('first', $enum->value);
        $this->assertSame('FIRST_NAME', $enum->name);
    }

    public function testFromWithValidIntValue(): void
    {
        $enum = ValidTestEnum::from(42);
        $this->assertInstanceOf(ValidTestEnum::class, $enum);
        $this->assertSame(42, $enum->value);
        $this->assertSame('AGE', $enum->name);
    }

    public function testFromWithInvalidValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid is not a valid backing value for enum WordPress\AiClient\Tests\unit\Common\ValidTestEnum');
        ValidTestEnum::from('invalid');
    }

    public function testTryFromWithValidValue(): void
    {
        $enum = ValidTestEnum::tryFrom('first');
        $this->assertInstanceOf(ValidTestEnum::class, $enum);
        $this->assertSame('first', $enum->value);
    }

    public function testTryFromWithInvalidValueReturnsNull(): void
    {
        $enum = ValidTestEnum::tryFrom('invalid');
        $this->assertNull($enum);
    }

    public function testCasesReturnsAllEnumInstances(): void
    {
        $cases = ValidTestEnum::cases();
        $this->assertCount(3, $cases);

        $values = array_map(fn($case) => $case->value, $cases);
        $this->assertContains('first', $values);
        $this->assertContains('last', $values);
        $this->assertContains(42, $values);

        $names = array_map(fn($case) => $case->name, $cases);
        $this->assertContains('FIRST_NAME', $names);
        $this->assertContains('LAST_NAME', $names);
        $this->assertContains('AGE', $names);
    }

    public function testSingletonBehavior(): void
    {
        $enum1 = ValidTestEnum::from('first');
        $enum2 = ValidTestEnum::from('first');
        $enum3 = ValidTestEnum::firstName();

        $this->assertSame($enum1, $enum2);
        $this->assertSame($enum1, $enum3);
    }

    public function testStaticFactoryMethods(): void
    {
        $firstName = ValidTestEnum::firstName();
        $this->assertSame('first', $firstName->value);
        $this->assertSame('FIRST_NAME', $firstName->name);

        $lastName = ValidTestEnum::lastName();
        $this->assertSame('last', $lastName->value);
        $this->assertSame('LAST_NAME', $lastName->name);

        $age = ValidTestEnum::age();
        $this->assertSame(42, $age->value);
        $this->assertSame('AGE', $age->name);
    }

    public function testInvalidStaticMethodThrowsException(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method WordPress\AiClient\Tests\unit\Common\ValidTestEnum::invalidMethod does not exist'
        );
        ValidTestEnum::invalidMethod();
    }

    public function testIsCheckMethods(): void
    {
        $enum = ValidTestEnum::firstName();

        $this->assertTrue($enum->isFirstName());
        $this->assertFalse($enum->isLastName());
        $this->assertFalse($enum->isAge());
    }

    public function testInvalidIsMethodThrowsException(): void
    {
        $enum = ValidTestEnum::firstName();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Method WordPress\AiClient\Tests\unit\Common\ValidTestEnum::isInvalidMethod does not exist'
        );
        $enum->isInvalidMethod();
    }

    public function testEqualsWithSameValue(): void
    {
        $enum = ValidTestEnum::firstName();

        $this->assertTrue($enum->equals('first'));
        $this->assertTrue($enum->equals(ValidTestEnum::firstName()));
        $this->assertFalse($enum->equals('last'));
        $this->assertFalse($enum->equals(ValidTestEnum::lastName()));
    }

    public function testEqualsWithIntValue(): void
    {
        $enum = ValidTestEnum::age();

        $this->assertTrue($enum->equals(42));
        $this->assertFalse($enum->equals('42')); // Strict comparison
        $this->assertFalse($enum->equals(43));
    }

    public function testIsMethodForIdentityComparison(): void
    {
        $enum1 = ValidTestEnum::firstName();
        $enum2 = ValidTestEnum::firstName();
        $enum3 = ValidTestEnum::lastName();

        $this->assertTrue($enum1->is($enum2)); // Same instance
        $this->assertFalse($enum1->is($enum3)); // Different instance
    }

    public function testGetValuesReturnsAllValidValues(): void
    {
        $values = ValidTestEnum::getValues();

        $this->assertSame([
            'FIRST_NAME' => 'first',
            'LAST_NAME' => 'last',
            'AGE' => 42,
        ], $values);
    }

    public function testIsValidValue(): void
    {
        $this->assertTrue(ValidTestEnum::isValidValue('first'));
        $this->assertTrue(ValidTestEnum::isValidValue('last'));
        $this->assertTrue(ValidTestEnum::isValidValue(42));

        $this->assertFalse(ValidTestEnum::isValidValue('invalid'));
        $this->assertFalse(ValidTestEnum::isValidValue(43));
    }

    public function testFromValueDeprecatedMethod(): void
    {
        $enum = ValidTestEnum::fromValue('first');
        $this->assertInstanceOf(ValidTestEnum::class, $enum);
        $this->assertSame('first', $enum->value);
    }

    public function testPropertiesAreReadOnly(): void
    {
        $enum = ValidTestEnum::firstName();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Cannot modify property WordPress\AiClient\Tests\unit\Common\ValidTestEnum::value - enum properties are read-only'
        );
        $enum->value = 'modified';
    }

    public function testInvalidPropertyAccessThrowsException(): void
    {
        $enum = ValidTestEnum::firstName();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Property WordPress\AiClient\Tests\unit\Common\ValidTestEnum::invalid does not exist'
        );
        $enum->invalid;
    }

    public function testToString(): void
    {
        $stringEnum = ValidTestEnum::firstName();
        $intEnum = ValidTestEnum::age();

        $this->assertSame('first', (string) $stringEnum);
        $this->assertSame('42', (string) $intEnum);
    }

    public function testInvalidConstantNameThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Invalid enum constant name "invalid_name" in ' .
            'WordPress\AiClient\Tests\unit\Common\InvalidNameTestEnum. Constants must be UPPER_SNAKE_CASE.'
        );

        InvalidNameTestEnum::cases();
    }

    public function testInvalidConstantTypeThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Invalid enum value type for constant ' .
            'WordPress\AiClient\Tests\unit\Common\InvalidTypeTestEnum::FLOAT_VALUE. ' .
            'Only string and int values are allowed, double given.'
        );

        InvalidTypeTestEnum::cases();
    }
}

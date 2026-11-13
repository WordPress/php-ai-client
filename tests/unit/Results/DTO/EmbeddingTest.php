<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
use stdClass;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Results\DTO\Embedding;

/**
 * @covers \WordPress\AiClient\Results\DTO\Embedding
 */
class EmbeddingTest extends TestCase
{
    public function testGettersAndArrayConversion(): void
    {
        $embedding = new Embedding([0.1, 1, 0.3], 3);

        $this->assertSame([0.1, 1, 0.3], $embedding->getValues());
        $this->assertSame(3, $embedding->getDimensions());
        $this->assertSame(3, count($embedding));
        $this->assertSame([0.1, 1, 0.3], $embedding->toArray());
        $this->assertSame([0.1, 1, 0.3], $embedding->jsonSerialize());
    }

    public function testIsIterable(): void
    {
        $values = [];

        foreach (new Embedding([0.1, 0.2, 0.3], 3) as $value) {
            $values[] = $value;
        }

        $this->assertSame([0.1, 0.2, 0.3], $values);
    }

    public function testPreservesFiniteBoundaryValues(): void
    {
        $values = [PHP_FLOAT_MAX, -PHP_FLOAT_MAX, -0.0, 0.0, 0, 1, -1, PHP_INT_MAX, PHP_INT_MIN];
        $embedding = new Embedding($values, 9);

        $this->assertSame($values, $embedding->getValues());
        $this->assertSame(9, $embedding->getDimensions());
        $this->assertSame(pack('d', -0.0), pack('d', $embedding->getValues()[2]));
        $this->assertSame(pack('d', 0.0), pack('d', $embedding->getValues()[3]));
        $this->assertIsString(json_encode($embedding, JSON_THROW_ON_ERROR));
    }

    public function testFromArray(): void
    {
        $embedding = Embedding::fromArray([0.1, 0.2, 0.3], 3);

        $this->assertSame([0.1, 0.2, 0.3], $embedding->getValues());
    }

    public function testDimensionsMustBeGreaterThanZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Embedding dimensions must be greater than zero');

        new Embedding([], 0);
    }

    public function testValuesMustBeAList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Embedding values must be a list array.');

        new Embedding([1 => 0.1], 1);
    }

    public function testValuesMustMatchDimensions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Embedding vector length must match dimensions.');

        new Embedding([0.1, 0.2], 3);
    }

    /**
     * @dataProvider invalidValueProvider
     *
     * @param mixed $value The invalid component.
     * @param string $type The safely reported type.
     */
    public function testValuesMustBeNumeric($value, string $type): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value at index 1: ' . $type . '.');

        new Embedding([0.1, $value], 2);
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function invalidValueProvider(): array
    {
        return [
            'numeric string' => ['0.2', 'string'],
            'boolean' => [true, 'boolean'],
            'null' => [null, 'NULL'],
            'array' => [[0.2], 'array'],
            'object' => [new stdClass(), 'object'],
        ];
    }

    /**
     * @dataProvider nonFiniteValueProvider
     */
    public function testValuesMustBeFinite(float $value, string $label): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Embedding values must be integers or finite floats. Invalid value at index 1: ' . $label . '.'
        );

        new Embedding([0.1, $value], 2);
    }

    /**
     * @return array<string, array{float, string}>
     */
    public static function nonFiniteValueProvider(): array
    {
        return [
            'NAN'  => [NAN, 'NAN'],
            'INF'  => [INF, 'INF'],
            '-INF' => [-INF, '-INF'],
        ];
    }
}

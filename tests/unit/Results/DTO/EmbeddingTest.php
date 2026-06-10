<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
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

    public function testValuesMustBeNumeric(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Embedding values must be integers or floats.');

        new Embedding([0.1, '0.2'], 2);
    }
}

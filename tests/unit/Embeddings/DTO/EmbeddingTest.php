<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Embeddings\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Embeddings\DTO\Embedding;

/**
 * @covers \WordPress\AiClient\Embeddings\DTO\Embedding
 */
class EmbeddingTest extends TestCase
{
    /**
     * Tests creating an embedding with valid data.
     */
    public function testCreateEmbedding(): void
    {
        $vector = [0.1, 0.2, 0.3];
        $embedding = new Embedding($vector, 3);

        $this->assertSame($vector, $embedding->getVector());
        $this->assertSame(3, $embedding->getDimension());
    }

    /**
     * Tests constructor normalizes integer values to floats.
     */
    public function testConstructorNormalizesNumericValues(): void
    {
        $embedding = new Embedding([1, 2, 3], 3);

        $this->assertSame([1.0, 2.0, 3.0], $embedding->getVector());
    }

    /**
     * Tests constructor validates dimension length.
     */
    public function testConstructorValidatesDimension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Embedding dimension mismatch');

        new Embedding([0.1, 0.2], 3);
    }

    /**
     * Tests constructor rejects non-numeric values.
     */
    public function testConstructorRejectsNonNumericValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Embedding vector values must be numeric.');

        new Embedding([0.1, 'invalid'], 2);
    }

    /**
     * Tests array transformation produces the expected payload.
     */
    public function testArrayTransformation(): void
    {
        $embedding = new Embedding([0.5, 0.25], 2);

        $this->assertSame(
            [
                Embedding::KEY_VECTOR => [0.5, 0.25],
                Embedding::KEY_DIMENSION => 2,
            ],
            $embedding->toArray()
        );
    }

    /**
     * Tests fromArray creates a matching embedding instance.
     */
    public function testFromArrayCreatesEmbedding(): void
    {
        $data = [
            Embedding::KEY_VECTOR => [0.1, 0.2, 0.3],
            Embedding::KEY_DIMENSION => 3,
        ];

        $embedding = Embedding::fromArray($data);

        $this->assertSame($data[Embedding::KEY_VECTOR], $embedding->getVector());
        $this->assertSame($data[Embedding::KEY_DIMENSION], $embedding->getDimension());
    }

    /**
     * Tests JSON schema definition.
     */
    public function testJsonSchema(): void
    {
        $schema = Embedding::getJsonSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey(Embedding::KEY_VECTOR, $schema['properties']);
        $this->assertArrayHasKey(Embedding::KEY_DIMENSION, $schema['properties']);
    }
}

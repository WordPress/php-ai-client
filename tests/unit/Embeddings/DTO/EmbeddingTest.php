<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Embeddings\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Embeddings\DTO\Embedding;

/**
 * @covers \WordPress\AiClient\Embeddings\DTO\Embedding
 */
class EmbeddingTest extends TestCase
{
    /**
     * Tests creating Embedding with valid vector.
     */
    public function testCreateWithValidVector(): void
    {
        $vector = [0.1, 0.2, 0.3, 0.4, 0.5];
        $embedding = new Embedding($vector);

        $this->assertEquals($vector, $embedding->getVector());
        $this->assertEquals(5, $embedding->getDimension());
    }

    /**
     * Tests creating Embedding with empty vector.
     */
    public function testCreateWithEmptyVector(): void
    {
        $vector = [];
        $embedding = new Embedding($vector);

        $this->assertEquals($vector, $embedding->getVector());
        $this->assertEquals(0, $embedding->getDimension());
    }

    /**
     * Tests creating Embedding with large vector.
     */
    public function testCreateWithLargeVector(): void
    {
        $vector = array_fill(0, 1536, 0.1); // Common OpenAI embedding size
        $embedding = new Embedding($vector);

        $this->assertEquals($vector, $embedding->getVector());
        $this->assertEquals(1536, $embedding->getDimension());
    }

    /**
     * Tests creating Embedding with negative values.
     */
    public function testCreateWithNegativeValues(): void
    {
        $vector = [-0.5, -0.3, 0.0, 0.3, 0.5];
        $embedding = new Embedding($vector);

        $this->assertEquals($vector, $embedding->getVector());
        $this->assertEquals(5, $embedding->getDimension());
    }

    /**
     * Tests toArray conversion.
     */
    public function testToArray(): void
    {
        $vector = [0.1, 0.2, 0.3];
        $embedding = new Embedding($vector);

        $expected = [
            'vector' => $vector,
            'dimension' => 3,
        ];

        $this->assertEquals($expected, $embedding->toArray());
    }

    /**
     * Tests fromArray creation.
     */
    public function testFromArray(): void
    {
        $vector = [0.4, 0.5, 0.6];
        $array = [
            'vector' => $vector,
            'dimension' => 3,
        ];

        $embedding = Embedding::fromArray($array);

        $this->assertEquals($vector, $embedding->getVector());
        $this->assertEquals(3, $embedding->getDimension());
    }

    /**
     * Tests fromArray with missing vector throws exception.
     */
    public function testFromArrayWithMissingVectorThrowsException(): void
    {
        $array = [
            'dimension' => 3,
        ];

        $this->expectException(\InvalidArgumentException::class);
        Embedding::fromArray($array);
    }

    /**
     * Tests JSON schema generation.
     */
    public function testGetJsonSchema(): void
    {
        $schema = Embedding::getJsonSchema();

        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('vector', $schema['properties']);
        $this->assertArrayHasKey('dimension', $schema['properties']);

        $this->assertArrayHasKey('required', $schema);
        $this->assertContains('vector', $schema['required']);
        $this->assertContains('dimension', $schema['required']);
    }

    /**
     * Tests that dimension calculation is accurate.
     */
    public function testDimensionCalculation(): void
    {
        // Test various vector sizes
        $testCases = [
            'empty' => [[], 0],
            'single' => [[1.0], 1],
            'double' => [[1.0, 2.0], 2],
            'hundred' => [array_fill(0, 100, 0.1), 100],
            'large' => [array_fill(0, 1024, 0.1), 1024],
        ];

        foreach ($testCases as $testName => [$vector, $expectedDimension]) {
            $embedding = new Embedding($vector);
            $this->assertEquals($expectedDimension, $embedding->getDimension(), "Failed for test case: $testName");
        }
    }
}

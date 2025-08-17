<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Embeddings\DTO\Embedding;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * @covers \WordPress\AiClient\Results\DTO\EmbeddingResult
 */
class EmbeddingResultTest extends TestCase
{
    private TokenUsage $tokenUsage;
    private Embedding $embedding1;
    private Embedding $embedding2;

    protected function setUp(): void
    {
        $this->tokenUsage = new TokenUsage(10, 0, 10);
        $this->embedding1 = new Embedding([0.1, 0.2, 0.3]);
        $this->embedding2 = new Embedding([0.4, 0.5, 0.6]);
    }

    /**
     * Tests creating EmbeddingResult with valid data.
     */
    public function testCreateWithValidData(): void
    {
        $embeddings = [$this->embedding1, $this->embedding2];
        $metadata = ['model' => 'text-embedding-ada-002'];

        $result = new EmbeddingResult('test-id', $embeddings, $this->tokenUsage, $metadata);

        $this->assertEquals('test-id', $result->getId());
        $this->assertEquals($embeddings, $result->getEmbeddings());
        $this->assertEquals($this->tokenUsage, $result->getTokenUsage());
        $this->assertEquals($metadata, $result->getProviderMetadata());
    }

    /**
     * Tests creating EmbeddingResult with empty metadata.
     */
    public function testCreateWithEmptyMetadata(): void
    {
        $embeddings = [$this->embedding1];

        $result = new EmbeddingResult('test-id', $embeddings, $this->tokenUsage);

        $this->assertEquals('test-id', $result->getId());
        $this->assertEquals($embeddings, $result->getEmbeddings());
        $this->assertEquals($this->tokenUsage, $result->getTokenUsage());
        $this->assertEquals([], $result->getProviderMetadata());
    }

    /**
     * Tests creating EmbeddingResult with empty embeddings throws exception.
     */
    public function testCreateWithEmptyEmbeddingsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one embedding must be provided');

        new EmbeddingResult('test-id', [], $this->tokenUsage);
    }

    /**
     * Tests creating EmbeddingResult with single embedding.
     */
    public function testCreateWithSingleEmbedding(): void
    {
        $embeddings = [$this->embedding1];

        $result = new EmbeddingResult('test-id', $embeddings, $this->tokenUsage);

        $this->assertCount(1, $result->getEmbeddings());
        $this->assertEquals($this->embedding1, $result->getEmbeddings()[0]);
    }

    /**
     * Tests creating EmbeddingResult with multiple embeddings.
     */
    public function testCreateWithMultipleEmbeddings(): void
    {
        $embeddings = [$this->embedding1, $this->embedding2];

        $result = new EmbeddingResult('test-id', $embeddings, $this->tokenUsage);

        $this->assertCount(2, $result->getEmbeddings());
        $this->assertEquals($this->embedding1, $result->getEmbeddings()[0]);
        $this->assertEquals($this->embedding2, $result->getEmbeddings()[1]);
    }

    /**
     * Tests toArray conversion.
     */
    public function testToArray(): void
    {
        $embeddings = [$this->embedding1, $this->embedding2];
        $metadata = ['model' => 'text-embedding-ada-002'];

        $result = new EmbeddingResult('test-id', $embeddings, $this->tokenUsage, $metadata);

        $expected = [
            'id' => 'test-id',
            'embeddings' => [
                $this->embedding1->toArray(),
                $this->embedding2->toArray(),
            ],
            'tokenUsage' => $this->tokenUsage->toArray(),
            'providerMetadata' => $metadata,
        ];

        $this->assertEquals($expected, $result->toArray());
    }

    /**
     * Tests fromArray creation.
     */
    public function testFromArray(): void
    {
        $array = [
            'id' => 'test-id',
            'embeddings' => [
                $this->embedding1->toArray(),
                $this->embedding2->toArray(),
            ],
            'tokenUsage' => $this->tokenUsage->toArray(),
            'providerMetadata' => ['model' => 'test-model'],
        ];

        $result = EmbeddingResult::fromArray($array);

        $this->assertEquals('test-id', $result->getId());
        $this->assertCount(2, $result->getEmbeddings());
        $this->assertEquals($this->tokenUsage->toArray(), $result->getTokenUsage()->toArray());
        $this->assertEquals(['model' => 'test-model'], $result->getProviderMetadata());
    }

    /**
     * Tests fromArray with missing metadata uses empty array.
     */
    public function testFromArrayWithMissingMetadata(): void
    {
        $array = [
            'id' => 'test-id',
            'embeddings' => [$this->embedding1->toArray()],
            'tokenUsage' => $this->tokenUsage->toArray(),
        ];

        $result = EmbeddingResult::fromArray($array);

        $this->assertEquals([], $result->getProviderMetadata());
    }

    /**
     * Tests fromArray with missing required field throws exception.
     */
    public function testFromArrayWithMissingRequiredFieldThrowsException(): void
    {
        $array = [
            'id' => 'test-id',
            'embeddings' => [$this->embedding1->toArray()],
            // Missing tokenUsage
        ];

        $this->expectException(\InvalidArgumentException::class);
        EmbeddingResult::fromArray($array);
    }

    /**
     * Tests JSON schema generation.
     */
    public function testGetJsonSchema(): void
    {
        $schema = EmbeddingResult::getJsonSchema();

        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);

        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('embeddings', $schema['properties']);
        $this->assertArrayHasKey('tokenUsage', $schema['properties']);
        $this->assertArrayHasKey('providerMetadata', $schema['properties']);

        $this->assertArrayHasKey('required', $schema);
        $this->assertContains('id', $schema['required']);
        $this->assertContains('embeddings', $schema['required']);
        $this->assertContains('tokenUsage', $schema['required']);
    }

    /**
     * Tests that EmbeddingResult implements ResultInterface.
     */
    public function testImplementsResultInterface(): void
    {
        $embeddings = [$this->embedding1];
        $result = new EmbeddingResult('test-id', $embeddings, $this->tokenUsage);

        $this->assertInstanceOf(\WordPress\AiClient\Results\Contracts\ResultInterface::class, $result);
    }

    /**
     * Tests embedding result with complex metadata.
     */
    public function testWithComplexMetadata(): void
    {
        $embeddings = [$this->embedding1];
        $metadata = [
            'model' => 'text-embedding-ada-002',
            'usage' => ['prompt_tokens' => 5],
            'provider' => 'openai',
            'version' => '1.0',
        ];

        $result = new EmbeddingResult('test-id', $embeddings, $this->tokenUsage, $metadata);

        $this->assertEquals($metadata, $result->getProviderMetadata());
        $this->assertEquals('openai', $result->getProviderMetadata()['provider']);
    }
}

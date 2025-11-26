<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Embeddings\DTO\Embedding;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\Contracts\ResultInterface;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * @covers \WordPress\AiClient\Results\DTO\EmbeddingResult
 */
class EmbeddingResultTest extends TestCase
{
    /**
     * Creates provider metadata for tests.
     */
    private function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata('provider', 'Provider', ProviderTypeEnum::cloud());
    }

    /**
     * Creates model metadata for tests.
     */
    private function createModelMetadata(): ModelMetadata
    {
        return new ModelMetadata('model', 'Model', [CapabilityEnum::embeddingGeneration()], []);
    }

    /**
     * Creates embeddings for tests.
     *
     * @return list<Embedding>
     */
    private function createEmbeddings(): array
    {
        return [
            new Embedding([0.1, 0.2], 2),
            new Embedding([0.3, 0.4], 2),
        ];
    }

    /**
     * Tests creating an embedding result with valid data.
     */
    public function testCreateEmbeddingResult(): void
    {
        $tokenUsage = new TokenUsage(10, 0, 10);
        $result = new EmbeddingResult(
            'embedding-result',
            $this->createEmbeddings(),
            $tokenUsage,
            $this->createProviderMetadata(),
            $this->createModelMetadata()
        );

        $this->assertSame('embedding-result', $result->getId());
        $this->assertCount(2, $result->getEmbeddings());
        $this->assertSame($tokenUsage, $result->getTokenUsage());
        $this->assertInstanceOf(ResultInterface::class, $result);
    }

    /**
     * Tests constructor enforces at least one embedding.
     */
    public function testConstructorRejectsEmptyEmbeddings(): void
    {
        $tokenUsage = new TokenUsage(5, 0, 5);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one embedding must be provided.');

        new EmbeddingResult(
            'empty',
            [],
            $tokenUsage,
            $this->createProviderMetadata(),
            $this->createModelMetadata()
        );
    }

    /**
     * Tests helper methods for embedding vectors.
     */
    public function testVectorHelpers(): void
    {
        $result = new EmbeddingResult(
            'vectors',
            $this->createEmbeddings(),
            new TokenUsage(3, 0, 3),
            $this->createProviderMetadata(),
            $this->createModelMetadata()
        );

        $this->assertSame([0.1, 0.2], $result->toVector());
        $this->assertSame([[0.1, 0.2], [0.3, 0.4]], $result->toVectors());
        $this->assertTrue($result->hasMultipleEmbeddings());
        $this->assertSame(2, $result->getEmbeddingCount());
    }

    /**
     * Tests additional data handling.
     */
    public function testAdditionalDataExposure(): void
    {
        $metadata = ['dimension' => 1536];
        $result = new EmbeddingResult(
            'with-metadata',
            $this->createEmbeddings(),
            new TokenUsage(1536, 0, 1536),
            $this->createProviderMetadata(),
            $this->createModelMetadata(),
            $metadata
        );

        $this->assertSame($metadata, $result->getAdditionalData());
    }

    /**
     * Tests JSON schema definition.
     */
    public function testJsonSchema(): void
    {
        $schema = EmbeddingResult::getJsonSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey(EmbeddingResult::KEY_EMBEDDINGS, $schema['properties']);
        $this->assertArrayHasKey(EmbeddingResult::KEY_TOKEN_USAGE, $schema['properties']);
        $this->assertContains(
            EmbeddingResult::KEY_ID,
            $schema['required']
        );
    }

    /**
     * Tests array conversion round-trips correctly.
     */
    public function testArrayTransformation(): void
    {
        $result = new EmbeddingResult(
            'to-array',
            $this->createEmbeddings(),
            new TokenUsage(42, 0, 42),
            $this->createProviderMetadata(),
            $this->createModelMetadata()
        );

        $array = $result->toArray();
        $hydrated = EmbeddingResult::fromArray($array);

        $this->assertSame($result->getId(), $hydrated->getId());
        $this->assertSame($result->toVectors(), $hydrated->toVectors());
        $this->assertSame(
            $result->getTokenUsage()->getTotalTokens(),
            $hydrated->getTokenUsage()->getTotalTokens()
        );
    }
}

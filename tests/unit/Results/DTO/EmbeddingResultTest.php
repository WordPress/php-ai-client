<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
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
    public function testCreateWithSingleEmbedding(): void
    {
        $result = new EmbeddingResult(
            'emb-123',
            [[0.1, 0.2, 0.3]],
            new TokenUsage(5, 0, 5),
            $this->createProviderMetadata(),
            $this->createModelMetadata()
        );

        $this->assertInstanceOf(ResultInterface::class, $result);
        $this->assertEquals('emb-123', $result->getId());
        $this->assertEquals([[0.1, 0.2, 0.3]], $result->getEmbeddings());
        $this->assertEquals([0.1, 0.2, 0.3], $result->getEmbedding());
        $this->assertEquals(3, $result->getDimensions());
        $this->assertEquals(5, $result->getTokenUsage()->getPromptTokens());
    }

    public function testCreateWithoutEmbeddingsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one embedding must be provided');

        new EmbeddingResult(
            'emb-empty',
            [],
            new TokenUsage(0, 0, 0),
            $this->createProviderMetadata(),
            $this->createModelMetadata()
        );
    }

    public function testArrayRoundTrip(): void
    {
        $result = new EmbeddingResult(
            'emb-456',
            [[1.0, 2.0], [3.0, 4.0]],
            new TokenUsage(7, 0, 7),
            $this->createProviderMetadata(),
            $this->createModelMetadata(),
            ['object' => 'list']
        );

        $restored = EmbeddingResult::fromArray($result->toArray());

        $this->assertEquals($result->toArray(), $restored->toArray());
    }

    private function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata('test-provider', 'Test Provider', ProviderTypeEnum::cloud());
    }

    private function createModelMetadata(): ModelMetadata
    {
        return new ModelMetadata(
            'test-embedding-model',
            'Test Embedding Model',
            [CapabilityEnum::embeddingGeneration()],
            []
        );
    }
}

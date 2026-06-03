<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * @covers \WordPress\AiClient\Results\DTO\EmbeddingResult
 */
class EmbeddingResultTest extends TestCase
{
    private function createEmbeddingResult(): EmbeddingResult
    {
        return new EmbeddingResult(
            'embedding-result-id',
            [[0.1, 0.2, 0.3], [0.4, 0.5, 0.6]],
            3,
            new TokenUsage(4, 0, 4),
            new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud()),
            new ModelMetadata(
                'mock-embedding-model',
                'Mock Embedding Model',
                [CapabilityEnum::embeddingGeneration()],
                []
            ),
            ['providerResultId' => 'provider-123']
        );
    }

    public function testGetters(): void
    {
        $result = $this->createEmbeddingResult();

        $this->assertSame('embedding-result-id', $result->getId());
        $this->assertSame([[0.1, 0.2, 0.3], [0.4, 0.5, 0.6]], $result->getEmbeddings());
        $this->assertSame([0.1, 0.2, 0.3], $result->getEmbedding());
        $this->assertSame(3, $result->getDimensions());
        $this->assertSame(4, $result->getTokenUsage()->getPromptTokens());
        $this->assertSame('mock', $result->getProviderMetadata()->getId());
        $this->assertSame('mock-embedding-model', $result->getModelMetadata()->getId());
        $this->assertSame(['providerResultId' => 'provider-123'], $result->getAdditionalData());
    }

    public function testArrayRoundTrip(): void
    {
        $result = $this->createEmbeddingResult();

        $this->assertEquals($result, EmbeddingResult::fromArray($result->toArray()));
    }

    public function testRequiresAtLeastOneEmbedding(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one embedding must be provided');

        new EmbeddingResult(
            'embedding-result-id',
            [],
            3,
            new TokenUsage(4, 0, 4),
            new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud()),
            new ModelMetadata(
                'mock-embedding-model',
                'Mock Embedding Model',
                [CapabilityEnum::embeddingGeneration()],
                []
            )
        );
    }

    public function testEmbeddingLengthMustMatchDimensions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Embedding vector length must match dimensions.');

        new EmbeddingResult(
            'embedding-result-id',
            [[0.1, 0.2]],
            3,
            new TokenUsage(4, 0, 4),
            new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud()),
            new ModelMetadata(
                'mock-embedding-model',
                'Mock Embedding Model',
                [CapabilityEnum::embeddingGeneration()],
                []
            )
        );
    }
}

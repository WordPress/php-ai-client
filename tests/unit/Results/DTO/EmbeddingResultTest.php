<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\Embedding;
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
        $this->assertContainsOnlyInstancesOf(Embedding::class, $result->getEmbeddings());
        $this->assertSame([[0.1, 0.2, 0.3], [0.4, 0.5, 0.6]], $result->toArray()[EmbeddingResult::KEY_EMBEDDINGS]);
        $this->assertSame([0.1, 0.2, 0.3], $result->getEmbedding()->getValues());
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

    /**
     * @dataProvider nonFiniteValueProvider
     */
    public function testFromArrayRejectsNonFiniteValuesInLaterVectors(float $value): void
    {
        $array = $this->createEmbeddingResult()->toArray();
        $array[EmbeddingResult::KEY_EMBEDDINGS][1][2] = $value;

        $this->expectException(InvalidArgumentException::class);

        EmbeddingResult::fromArray($array);
    }

    public function testConstructorRejectsNonFiniteValuesInRawVectors(): void
    {
        $result = $this->createEmbeddingResult();

        $this->expectException(InvalidArgumentException::class);

        new EmbeddingResult(
            $result->getId(),
            [[0.1, 0.2, 0.3], [0.4, INF, 0.6]],
            3,
            $result->getTokenUsage(),
            $result->getProviderMetadata(),
            $result->getModelMetadata()
        );
    }

    /**
     * @dataProvider nonFiniteValueProvider
     */
    public function testIsArrayShapeRejectsNonFiniteValues(float $value): void
    {
        $array = $this->createEmbeddingResult()->toArray();
        $this->assertTrue(EmbeddingResult::isArrayShape($array));

        $array[EmbeddingResult::KEY_EMBEDDINGS][1][2] = $value;

        $this->assertFalse(EmbeddingResult::isArrayShape($array));
    }

    /**
     * @return array<string, array{float}>
     */
    public static function nonFiniteValueProvider(): array
    {
        return [
            'NAN' => [NAN],
            'INF' => [INF],
            '-INF' => [-INF],
        ];
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

    public function testAcceptsEmbeddingValueObjects(): void
    {
        $result = new EmbeddingResult(
            'embedding-result-id',
            [new Embedding([0.1, 0.2, 0.3], 3)],
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

        $this->assertSame([0.1, 0.2, 0.3], $result->getEmbedding()->getValues());
    }
}

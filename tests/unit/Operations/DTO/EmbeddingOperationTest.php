<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Operations\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Embeddings\DTO\Embedding;
use WordPress\AiClient\Operations\Contracts\OperationInterface;
use WordPress\AiClient\Operations\DTO\EmbeddingOperation;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * @covers \WordPress\AiClient\Operations\DTO\EmbeddingOperation
 */
class EmbeddingOperationTest extends TestCase
{
    /**
     * Creates a sample embedding result.
     */
    private function createEmbeddingResult(): EmbeddingResult
    {
        return new EmbeddingResult(
            'result-id',
            [
                new Embedding([0.1, 0.2], 2),
            ],
            new TokenUsage(128, 0, 128),
            new ProviderMetadata('provider', 'Provider', ProviderTypeEnum::cloud()),
            new ModelMetadata('model', 'Model', [CapabilityEnum::embeddingGeneration()], [])
        );
    }

    /**
     * Tests creating an operation without a result.
     */
    public function testCreateWithoutResult(): void
    {
        $operation = new EmbeddingOperation('op-1', OperationStateEnum::processing());

        $this->assertSame('op-1', $operation->getId());
        $this->assertTrue($operation->getState()->isProcessing());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests creating an operation with a completed result.
     */
    public function testCreateWithResult(): void
    {
        $result = $this->createEmbeddingResult();
        $operation = new EmbeddingOperation('op-2', OperationStateEnum::succeeded(), $result);

        $this->assertSame('op-2', $operation->getId());
        $this->assertTrue($operation->getState()->isSucceeded());
        $this->assertSame($result, $operation->getResult());
    }

    /**
     * Tests operation implements the interface.
     */
    public function testImplementsInterface(): void
    {
        $operation = new EmbeddingOperation('op-3', OperationStateEnum::starting());

        $this->assertInstanceOf(OperationInterface::class, $operation);
    }

    /**
     * Tests array transformation round-trip.
     */
    public function testArrayTransformation(): void
    {
        $operation = new EmbeddingOperation(
            'op-4',
            OperationStateEnum::succeeded(),
            $this->createEmbeddingResult()
        );

        $array = $operation->toArray();
        $rehydrated = EmbeddingOperation::fromArray($array);

        $this->assertSame($operation->getId(), $rehydrated->getId());
        $this->assertTrue($rehydrated->getState()->isSucceeded());
        $this->assertSame(
            $operation->getResult()->toVectors(),
            $rehydrated->getResult()->toVectors()
        );
    }

    /**
     * Tests JSON schema definition.
     */
    public function testJsonSchema(): void
    {
        $schema = EmbeddingOperation::getJsonSchema();

        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertCount(2, $schema['oneOf']);
    }
}

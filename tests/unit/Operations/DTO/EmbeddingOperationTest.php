<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Operations\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Embeddings\DTO\Embedding;
use WordPress\AiClient\Operations\DTO\EmbeddingOperation;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * @covers \WordPress\AiClient\Operations\DTO\EmbeddingOperation
 */
class EmbeddingOperationTest extends TestCase
{
    private EmbeddingResult $embeddingResult;
    private TokenUsage $tokenUsage;

    protected function setUp(): void
    {
        $this->tokenUsage = new TokenUsage(10, 0, 10);
        $embedding = new Embedding([0.1, 0.2, 0.3]);
        $this->embeddingResult = new EmbeddingResult('result-id', [$embedding], $this->tokenUsage);
    }

    /**
     * Tests creating EmbeddingOperation with starting state.
     */
    public function testCreateWithStartingState(): void
    {
        $operation = new EmbeddingOperation('op-id', OperationStateEnum::starting());

        $this->assertEquals('op-id', $operation->getId());
        $this->assertEquals(OperationStateEnum::starting(), $operation->getState());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests creating EmbeddingOperation with processing state.
     */
    public function testCreateWithProcessingState(): void
    {
        $operation = new EmbeddingOperation('op-id', OperationStateEnum::processing());

        $this->assertEquals('op-id', $operation->getId());
        $this->assertEquals(OperationStateEnum::processing(), $operation->getState());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests creating EmbeddingOperation with succeeded state and result.
     */
    public function testCreateWithSucceededStateAndResult(): void
    {
        $operation = new EmbeddingOperation('op-id', OperationStateEnum::succeeded(), $this->embeddingResult);

        $this->assertEquals('op-id', $operation->getId());
        $this->assertEquals(OperationStateEnum::succeeded(), $operation->getState());
        $this->assertEquals($this->embeddingResult, $operation->getResult());
    }

    /**
     * Tests creating EmbeddingOperation with failed state.
     */
    public function testCreateWithFailedState(): void
    {
        $operation = new EmbeddingOperation('op-id', OperationStateEnum::failed());

        $this->assertEquals('op-id', $operation->getId());
        $this->assertEquals(OperationStateEnum::failed(), $operation->getState());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests creating EmbeddingOperation with canceled state.
     */
    public function testCreateWithCanceledState(): void
    {
        $operation = new EmbeddingOperation('op-id', OperationStateEnum::canceled());

        $this->assertEquals('op-id', $operation->getId());
        $this->assertEquals(OperationStateEnum::canceled(), $operation->getState());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests toArray conversion without result.
     */
    public function testToArrayWithoutResult(): void
    {
        $operation = new EmbeddingOperation('op-id', OperationStateEnum::starting());

        $expected = [
            'id' => 'op-id',
            'state' => 'starting',
        ];

        $this->assertEquals($expected, $operation->toArray());
    }

    /**
     * Tests toArray conversion with result.
     */
    public function testToArrayWithResult(): void
    {
        $operation = new EmbeddingOperation('op-id', OperationStateEnum::succeeded(), $this->embeddingResult);

        $expected = [
            'id' => 'op-id',
            'state' => 'succeeded',
            'result' => $this->embeddingResult->toArray(),
        ];

        $this->assertEquals($expected, $operation->toArray());
    }

    /**
     * Tests fromArray creation without result.
     */
    public function testFromArrayWithoutResult(): void
    {
        $array = [
            'id' => 'op-id',
            'state' => 'processing',
        ];

        $operation = EmbeddingOperation::fromArray($array);

        $this->assertEquals('op-id', $operation->getId());
        $this->assertEquals(OperationStateEnum::processing(), $operation->getState());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests fromArray creation with result.
     */
    public function testFromArrayWithResult(): void
    {
        $array = [
            'id' => 'op-id',
            'state' => 'succeeded',
            'result' => $this->embeddingResult->toArray(),
        ];

        $operation = EmbeddingOperation::fromArray($array);

        $this->assertEquals('op-id', $operation->getId());
        $this->assertEquals(OperationStateEnum::succeeded(), $operation->getState());
        $this->assertNotNull($operation->getResult());
        $this->assertEquals($this->embeddingResult->getId(), $operation->getResult()->getId());
    }

    /**
     * Tests fromArray with missing required field throws exception.
     */
    public function testFromArrayWithMissingRequiredFieldThrowsException(): void
    {
        $array = [
            'id' => 'op-id',
            // Missing state
        ];

        $this->expectException(\InvalidArgumentException::class);
        EmbeddingOperation::fromArray($array);
    }

    /**
     * Tests JSON schema generation.
     */
    public function testGetJsonSchema(): void
    {
        $schema = EmbeddingOperation::getJsonSchema();

        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertCount(2, $schema['oneOf']);

        // Test succeeded state schema (with result)
        $succeededSchema = $schema['oneOf'][0];
        $this->assertEquals('object', $succeededSchema['type']);
        $this->assertArrayHasKey('properties', $succeededSchema);
        $this->assertArrayHasKey('id', $succeededSchema['properties']);
        $this->assertArrayHasKey('state', $succeededSchema['properties']);
        $this->assertArrayHasKey('result', $succeededSchema['properties']);
        $this->assertContains('result', $succeededSchema['required']);

        // Test other states schema (without result)
        $otherStatesSchema = $schema['oneOf'][1];
        $this->assertEquals('object', $otherStatesSchema['type']);
        $this->assertArrayHasKey('properties', $otherStatesSchema);
        $this->assertArrayHasKey('id', $otherStatesSchema['properties']);
        $this->assertArrayHasKey('state', $otherStatesSchema['properties']);
        $this->assertArrayNotHasKey('result', $otherStatesSchema['properties']);
        $this->assertNotContains('result', $otherStatesSchema['required']);
    }

    /**
     * Tests that EmbeddingOperation implements OperationInterface.
     */
    public function testImplementsOperationInterface(): void
    {
        $operation = new EmbeddingOperation('op-id', OperationStateEnum::starting());

        $this->assertInstanceOf(\WordPress\AiClient\Operations\Contracts\OperationInterface::class, $operation);
    }

    /**
     * Tests operation state transitions.
     */
    public function testOperationStateTransitions(): void
    {
        // Test typical operation lifecycle
        $startingOp = new EmbeddingOperation('op-id', OperationStateEnum::starting());
        $this->assertTrue($startingOp->getState()->isStarting());

        $processingOp = new EmbeddingOperation('op-id', OperationStateEnum::processing());
        $this->assertTrue($processingOp->getState()->isProcessing());

        $succeededOp = new EmbeddingOperation('op-id', OperationStateEnum::succeeded(), $this->embeddingResult);
        $this->assertTrue($succeededOp->getState()->isSucceeded());
        $this->assertNotNull($succeededOp->getResult());

        $failedOp = new EmbeddingOperation('op-id', OperationStateEnum::failed());
        $this->assertTrue($failedOp->getState()->isFailed());
        $this->assertNull($failedOp->getResult());
    }

    /**
     * Tests round-trip conversion (toArray -> fromArray).
     */
    public function testRoundTripConversion(): void
    {
        $originalOperation = new EmbeddingOperation('op-id', OperationStateEnum::succeeded(), $this->embeddingResult);

        $array = $originalOperation->toArray();
        $reconstructedOperation = EmbeddingOperation::fromArray($array);

        $this->assertEquals($originalOperation->getId(), $reconstructedOperation->getId());
        $this->assertEquals($originalOperation->getState()->value, $reconstructedOperation->getState()->value);
        $this->assertEquals($originalOperation->getResult()->getId(), $reconstructedOperation->getResult()->getId());
    }
}

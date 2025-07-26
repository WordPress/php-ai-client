<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Operations\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * @covers \WordPress\AiClient\Operations\DTO\GenerativeAiOperation
 */
class GenerativeAiOperationTest extends TestCase
{
    /**
     * Tests creating operation in starting state.
     *
     * @return void
     */
    public function testCreateInStartingState(): void
    {
        $operation = new GenerativeAiOperation(
            'op_123',
            OperationStateEnum::starting()
        );
        
        $this->assertEquals('op_123', $operation->getId());
        $this->assertEquals(OperationStateEnum::starting(), $operation->getState());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests creating operation in processing state.
     *
     * @return void
     */
    public function testCreateInProcessingState(): void
    {
        $operation = new GenerativeAiOperation(
            'op_456',
            OperationStateEnum::processing()
        );
        
        $this->assertEquals('op_456', $operation->getId());
        $this->assertTrue($operation->getState()->isProcessing());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests creating operation in succeeded state with result.
     *
     * @return void
     */
    public function testCreateInSucceededStateWithResult(): void
    {
        $modelMessage = new ModelMessage([
            new MessagePart('Generated content')
        ]);
        $candidate = new Candidate(
            $modelMessage,
            FinishReasonEnum::stop(),
            42
        );
        $tokenUsage = new TokenUsage(10, 42, 52);
        $result = new GenerativeAiResult(
            'result_123',
            [$candidate],
            $tokenUsage,
            ['provider' => 'test']
        );
        
        $operation = new GenerativeAiOperation(
            'op_789',
            OperationStateEnum::succeeded(),
            $result
        );
        
        $this->assertEquals('op_789', $operation->getId());
        $this->assertTrue($operation->getState()->isSucceeded());
        $this->assertSame($result, $operation->getResult());
    }

    /**
     * Tests creating operation in failed state.
     *
     * @return void
     */
    public function testCreateInFailedState(): void
    {
        $operation = new GenerativeAiOperation(
            'op_failed',
            OperationStateEnum::failed()
        );
        
        $this->assertEquals('op_failed', $operation->getId());
        $this->assertTrue($operation->getState()->isFailed());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests creating operation in canceled state.
     *
     * @return void
     */
    public function testCreateInCanceledState(): void
    {
        $operation = new GenerativeAiOperation(
            'op_canceled',
            OperationStateEnum::canceled()
        );
        
        $this->assertEquals('op_canceled', $operation->getId());
        $this->assertTrue($operation->getState()->isCanceled());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests operation implements OperationInterface.
     *
     * @return void
     */
    public function testImplementsOperationInterface(): void
    {
        $operation = new GenerativeAiOperation(
            'op_test',
            OperationStateEnum::starting()
        );
        
        $this->assertInstanceOf(
            \WordPress\AiClient\Operations\Contracts\OperationInterface::class,
            $operation
        );
    }

    /**
     * Tests operation with different ID formats.
     *
     * @dataProvider idProvider
     * @param string $id
     * @return void
     */
    public function testWithDifferentIdFormats(string $id): void
    {
        $operation = new GenerativeAiOperation(
            $id,
            OperationStateEnum::processing()
        );
        
        $this->assertEquals($id, $operation->getId());
    }

    /**
     * Provides different ID formats.
     *
     * @return array
     */
    public function idProvider(): array
    {
        return [
            'uuid' => ['550e8400-e29b-41d4-a716-446655440000'],
            'alphanumeric' => ['op_abc123xyz'],
            'numeric' => ['123456789'],
            'with_special_chars' => ['op-2024-01-15_15:30:45'],
            'short' => ['op1'],
            'long' => ['operation_very_long_identifier_with_many_parts_12345'],
        ];
    }

    /**
     * Tests operation state transitions.
     *
     * @return void
     */
    public function testStateTransitions(): void
    {
        // Starting -> Processing
        $operation1 = new GenerativeAiOperation(
            'op_transition_1',
            OperationStateEnum::starting()
        );
        $this->assertTrue($operation1->getState()->isStarting());

        // Processing -> Succeeded with result
        $modelMessage = new ModelMessage([
            new MessagePart('Result')
        ]);
        $tokenUsage = new TokenUsage(5, 10, 15);
        $result = new GenerativeAiResult(
            'result_transition',
            [new Candidate($modelMessage, FinishReasonEnum::stop(), 10)],
            $tokenUsage
        );
        $operation2 = new GenerativeAiOperation(
            'op_transition_2',
            OperationStateEnum::succeeded(),
            $result
        );
        $this->assertTrue($operation2->getState()->isSucceeded());
        $this->assertNotNull($operation2->getResult());

        // Processing -> Failed
        $operation3 = new GenerativeAiOperation(
            'op_transition_3',
            OperationStateEnum::failed()
        );
        $this->assertTrue($operation3->getState()->isFailed());
        $this->assertNull($operation3->getResult());
    }

    /**
     * Tests JSON schema for succeeded state.
     *
     * @return void
     */
    public function testJsonSchemaForSucceededState(): void
    {
        $schema = GenerativeAiOperation::getJsonSchema();
        
        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertCount(2, $schema['oneOf']);
        
        // First schema is for succeeded state with result
        $succeededSchema = $schema['oneOf'][0];
        $this->assertEquals('object', $succeededSchema['type']);
        $this->assertArrayHasKey('properties', $succeededSchema);
        $this->assertArrayHasKey('id', $succeededSchema['properties']);
        $this->assertArrayHasKey('state', $succeededSchema['properties']);
        $this->assertArrayHasKey('result', $succeededSchema['properties']);
        
        // State should be const for succeeded
        $this->assertEquals(
            OperationStateEnum::succeeded()->value,
            $succeededSchema['properties']['state']['const']
        );
        
        // Required fields
        $this->assertEquals(['id', 'state', 'result'], $succeededSchema['required']);
    }

    /**
     * Tests JSON schema for non-succeeded states.
     *
     * @return void
     */
    public function testJsonSchemaForNonSucceededStates(): void
    {
        $schema = GenerativeAiOperation::getJsonSchema();
        
        // Second schema is for all other states without result
        $otherStatesSchema = $schema['oneOf'][1];
        $this->assertEquals('object', $otherStatesSchema['type']);
        $this->assertArrayHasKey('properties', $otherStatesSchema);
        $this->assertArrayHasKey('id', $otherStatesSchema['properties']);
        $this->assertArrayHasKey('state', $otherStatesSchema['properties']);
        $this->assertArrayNotHasKey('result', $otherStatesSchema['properties']);
        
        // State should be enum for other states
        $stateEnum = $otherStatesSchema['properties']['state']['enum'];
        $this->assertContains(OperationStateEnum::starting()->value, $stateEnum);
        $this->assertContains(OperationStateEnum::processing()->value, $stateEnum);
        $this->assertContains(OperationStateEnum::failed()->value, $stateEnum);
        $this->assertContains(OperationStateEnum::canceled()->value, $stateEnum);
        
        // Required fields
        $this->assertEquals(['id', 'state'], $otherStatesSchema['required']);
    }

    /**
     * Tests operation with empty string ID.
     *
     * @return void
     */
    public function testWithEmptyStringId(): void
    {
        $operation = new GenerativeAiOperation(
            '',
            OperationStateEnum::starting()
        );
        
        $this->assertEquals('', $operation->getId());
    }
}
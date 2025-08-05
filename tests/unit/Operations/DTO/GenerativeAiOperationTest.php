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
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\ValueObjects\TextContent;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tests\traits\ArrayTransformationTestTrait;

/**
 * @covers \WordPress\AiClient\Operations\DTO\GenerativeAiOperation
 */
class GenerativeAiOperationTest extends TestCase
{
    use ArrayTransformationTestTrait;
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
            new MessagePart(new TextContent('Generated content'))
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
            new MessagePart(new TextContent('Result'))
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
        $this->assertArrayHasKey(GenerativeAiOperation::KEY_ID, $succeededSchema['properties']);
        $this->assertArrayHasKey(GenerativeAiOperation::KEY_STATE, $succeededSchema['properties']);
        $this->assertArrayHasKey(GenerativeAiOperation::KEY_RESULT, $succeededSchema['properties']);
        
        // State should be const for succeeded
        $this->assertEquals(
            OperationStateEnum::succeeded()->value,
            $succeededSchema['properties'][GenerativeAiOperation::KEY_STATE]['const']
        );
        
        // Required fields
        $this->assertEquals([GenerativeAiOperation::KEY_ID, GenerativeAiOperation::KEY_STATE, GenerativeAiOperation::KEY_RESULT], $succeededSchema['required']);
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
        $this->assertArrayHasKey(GenerativeAiOperation::KEY_ID, $otherStatesSchema['properties']);
        $this->assertArrayHasKey(GenerativeAiOperation::KEY_STATE, $otherStatesSchema['properties']);
        $this->assertArrayNotHasKey(GenerativeAiOperation::KEY_RESULT, $otherStatesSchema['properties']);
        
        // State should be enum for other states
        $stateEnum = $otherStatesSchema['properties'][GenerativeAiOperation::KEY_STATE]['enum'];
        $this->assertContains(OperationStateEnum::starting()->value, $stateEnum);
        $this->assertContains(OperationStateEnum::processing()->value, $stateEnum);
        $this->assertContains(OperationStateEnum::failed()->value, $stateEnum);
        $this->assertContains(OperationStateEnum::canceled()->value, $stateEnum);
        
        // Required fields
        $this->assertEquals([GenerativeAiOperation::KEY_ID, GenerativeAiOperation::KEY_STATE], $otherStatesSchema['required']);
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

    /**
     * Tests array transformation for operation in starting state.
     *
     * @return void
     */
    public function testToArrayStartingState(): void
    {
        $operation = new GenerativeAiOperation(
            'op_start_123',
            OperationStateEnum::starting()
        );
        
        $json = $this->assertToArrayReturnsArray($operation);
        
        $this->assertArrayHasKeys($json, [GenerativeAiOperation::KEY_ID, GenerativeAiOperation::KEY_STATE]);
        $this->assertArrayNotHasKeys($json, [GenerativeAiOperation::KEY_RESULT]);
        $this->assertEquals('op_start_123', $json[GenerativeAiOperation::KEY_ID]);
        $this->assertEquals(OperationStateEnum::starting()->value, $json[GenerativeAiOperation::KEY_STATE]);
    }

    /**
     * Tests array transformation for operation in succeeded state.
     *
     * @return void
     */
    public function testToArraySucceededState(): void
    {
        $modelMessage = new ModelMessage([
            new MessagePart(new TextContent('Success response'))
        ]);
        $candidate = new Candidate(
            $modelMessage,
            FinishReasonEnum::stop(),
            50
        );
        $tokenUsage = new TokenUsage(15, 50, 65);
        $result = new GenerativeAiResult(
            'result_success',
            [$candidate],
            $tokenUsage
        );
        
        $operation = new GenerativeAiOperation(
            'op_success_456',
            OperationStateEnum::succeeded(),
            $result
        );
        
        $json = $this->assertToArrayReturnsArray($operation);
        
        $this->assertArrayHasKeys($json, [GenerativeAiOperation::KEY_ID, GenerativeAiOperation::KEY_STATE, GenerativeAiOperation::KEY_RESULT]);
        $this->assertEquals('op_success_456', $json[GenerativeAiOperation::KEY_ID]);
        $this->assertEquals(OperationStateEnum::succeeded()->value, $json[GenerativeAiOperation::KEY_STATE]);
        $this->assertIsArray($json[GenerativeAiOperation::KEY_RESULT]);
        $this->assertEquals('result_success', $json[GenerativeAiOperation::KEY_RESULT][GenerativeAiResult::KEY_ID]);
    }

    /**
     * Tests fromJson method for starting state.
     *
     * @return void
     */
    public function testFromArrayStartingState(): void
    {
        $json = [
            GenerativeAiOperation::KEY_ID => 'op_from_json_start',
            GenerativeAiOperation::KEY_STATE => OperationStateEnum::starting()->value
        ];
        
        $operation = GenerativeAiOperation::fromArray($json);
        
        $this->assertInstanceOf(GenerativeAiOperation::class, $operation);
        $this->assertEquals('op_from_json_start', $operation->getId());
        $this->assertEquals(OperationStateEnum::starting(), $operation->getState());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests fromJson method for succeeded state with result.
     *
     * @return void
     */
    public function testFromArraySucceededState(): void
    {
        $json = [
            GenerativeAiOperation::KEY_ID => 'op_from_json_success',
            GenerativeAiOperation::KEY_STATE => OperationStateEnum::succeeded()->value,
            GenerativeAiOperation::KEY_RESULT => [
                GenerativeAiResult::KEY_ID => 'result_from_json',
                GenerativeAiResult::KEY_CANDIDATES => [
                    [
                        Candidate::KEY_MESSAGE => [
                            Message::KEY_ROLE => MessageRoleEnum::model()->value,
                            Message::KEY_PARTS => [[MessagePart::KEY_TYPE => 'text', MessagePart::KEY_TEXT => 'Response text']]
                        ],
                        Candidate::KEY_FINISH_REASON => FinishReasonEnum::stop()->value,
                        Candidate::KEY_TOKEN_COUNT => 30
                    ]
                ],
                GenerativeAiResult::KEY_TOKEN_USAGE => [
                    TokenUsage::KEY_PROMPT_TOKENS => 10,
                    TokenUsage::KEY_COMPLETION_TOKENS => 30,
                    TokenUsage::KEY_TOTAL_TOKENS => 40
                ]
            ]
        ];
        
        $operation = GenerativeAiOperation::fromArray($json);
        
        $this->assertInstanceOf(GenerativeAiOperation::class, $operation);
        $this->assertEquals('op_from_json_success', $operation->getId());
        $this->assertEquals(OperationStateEnum::succeeded(), $operation->getState());
        $this->assertNotNull($operation->getResult());
        $this->assertEquals('result_from_json', $operation->getResult()->getId());
    }

    /**
     * Tests round-trip array transformation for processing state.
     *
     * @return void
     */
    public function testArrayRoundTripProcessingState(): void
    {
        $this->assertArrayRoundTrip(
            new GenerativeAiOperation(
                'op_roundtrip_process',
                OperationStateEnum::processing()
            ),
            function ($original, $restored) {
                $this->assertEquals($original->getId(), $restored->getId());
                $this->assertEquals($original->getState()->value, $restored->getState()->value);
                $this->assertNull($restored->getResult());
            }
        );
    }

    /**
     * Tests round-trip array transformation for succeeded state.
     *
     * @return void
     */
    public function testArrayRoundTripSucceededState(): void
    {
        $modelMessage = new ModelMessage([
            new MessagePart(new TextContent('Roundtrip test response'))
        ]);
        $candidate = new Candidate(
            $modelMessage,
            FinishReasonEnum::stop(),
            25
        );
        $tokenUsage = new TokenUsage(5, 25, 30);
        $result = new GenerativeAiResult(
            'result_roundtrip',
            [$candidate],
            $tokenUsage
        );
        
        $this->assertArrayRoundTrip(
            new GenerativeAiOperation(
                'op_roundtrip_success',
                OperationStateEnum::succeeded(),
                $result
            ),
            function ($original, $restored) {
                $this->assertEquals($original->getId(), $restored->getId());
                $this->assertEquals($original->getState()->value, $restored->getState()->value);
                $this->assertNotNull($restored->getResult());
                $this->assertEquals($original->getResult()->getId(), $restored->getResult()->getId());
            }
        );
    }

    /**
     * Tests GenerativeAiOperation implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $operation = new GenerativeAiOperation(
            'op_test',
            OperationStateEnum::starting()
        );
        $this->assertImplementsArrayTransformation($operation);
    }
}
<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tests\traits\JsonSerializationTestTrait;
use WordPress\AiClient\Tools\DTO\FunctionCall;

/**
 * @covers \WordPress\AiClient\Results\DTO\Candidate
 */
class CandidateTest extends TestCase
{
    use JsonSerializationTestTrait;
    /**
     * Tests creating candidate with basic properties.
     *
     * @return void
     */
    public function testCreateWithBasicProperties(): void
    {
        $message = new ModelMessage([
            new MessagePart('This is the generated response.')
        ]);
        
        $candidate = new Candidate(
            $message,
            FinishReasonEnum::stop(),
            25
        );
        
        $this->assertSame($message, $candidate->getMessage());
        $this->assertEquals(FinishReasonEnum::stop(), $candidate->getFinishReason());
        $this->assertEquals(25, $candidate->getTokenCount());
    }

    /**
     * Tests candidate with different finish reasons.
     *
     * @dataProvider finishReasonProvider
     * @param FinishReasonEnum $finishReason
     * @return void
     */
    public function testWithDifferentFinishReasons(FinishReasonEnum $finishReason): void
    {
        $message = new ModelMessage([new MessagePart('Response')]);
        
        $candidate = new Candidate($message, $finishReason, 10);
        
        $this->assertEquals($finishReason, $candidate->getFinishReason());
    }

    /**
     * Provides different finish reasons.
     *
     * @return array
     */
    public function finishReasonProvider(): array
    {
        return [
            'stop' => [FinishReasonEnum::stop()],
            'length' => [FinishReasonEnum::length()],
            'content_filter' => [FinishReasonEnum::contentFilter()],
            'tool_calls' => [FinishReasonEnum::toolCalls()],
            'error' => [FinishReasonEnum::error()],
        ];
    }

    /**
     * Tests candidate with complex message.
     *
     * @return void
     */
    public function testWithComplexMessage(): void
    {
        $functionCall = new FunctionCall(
            'func_123',
            'searchWeb',
            ['query' => 'PHP best practices']
        );
        
        $message = new ModelMessage([
            new MessagePart('Let me search for that information.'),
            new MessagePart($functionCall),
            new MessagePart('Based on my search, here are the PHP best practices:'),
            new MessagePart('1. Follow PSR standards'),
            new MessagePart('2. Use type declarations'),
            new MessagePart('3. Write unit tests'),
        ]);
        
        $candidate = new Candidate(
            $message,
            FinishReasonEnum::toolCalls(),
            150
        );
        
        $this->assertCount(6, $candidate->getMessage()->getParts());
        $this->assertTrue($candidate->getFinishReason()->isToolCalls());
        $this->assertEquals(150, $candidate->getTokenCount());
    }

    /**
     * Tests candidate with message containing files.
     *
     * @return void
     */
    public function testWithMessageContainingFiles(): void
    {
        $file = new File('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAACklEQVQI12P4DwABAQEAG7buVgAAAABJRU5ErkJggg==', 'image/png');
        
        $message = new ModelMessage([
            new MessagePart('I\'ve generated the requested image:'),
            new MessagePart($file),
            new MessagePart('The image shows a flowchart of the process.'),
        ]);
        
        $candidate = new Candidate(
            $message,
            FinishReasonEnum::stop(),
            85
        );
        
        $parts = $candidate->getMessage()->getParts();
        $this->assertEquals('I\'ve generated the requested image:', $parts[0]->getText());
        $this->assertSame($file, $parts[1]->getFile());
        $this->assertEquals('The image shows a flowchart of the process.', $parts[2]->getText());
    }

    /**
     * Tests candidate with different token counts.
     *
     * @dataProvider tokenCountProvider
     * @param int $tokenCount
     * @return void
     */
    public function testWithDifferentTokenCounts(int $tokenCount): void
    {
        $message = new ModelMessage([new MessagePart('Response')]);
        
        $candidate = new Candidate(
            $message,
            FinishReasonEnum::stop(),
            $tokenCount
        );
        
        $this->assertEquals($tokenCount, $candidate->getTokenCount());
    }

    /**
     * Provides different token counts.
     *
     * @return array
     */
    public function tokenCountProvider(): array
    {
        return [
            'zero' => [0],
            'small' => [10],
            'medium' => [500],
            'large' => [4000],
            'very_large' => [100000],
        ];
    }

    /**
     * Tests candidate rejects non-model message.
     *
     * @return void
     */
    public function testRejectsNonModelMessage(): void
    {
        $userMessage = new UserMessage([
            new MessagePart('This is a user message.')
        ]);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message must be a model message.');
        
        new Candidate(
            $userMessage,
            FinishReasonEnum::stop(),
            10
        );
    }

    /**
     * Tests candidate with message using different role.
     *
     * @return void
     */
    public function testRejectsMessageWithDifferentRole(): void
    {
        $message = new Message(
            MessageRoleEnum::user(),
            [new MessagePart('User message')]
        );
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Message must be a model message.');
        
        new Candidate(
            $message,
            FinishReasonEnum::stop(),
            10
        );
    }

    /**
     * Tests JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = Candidate::getJsonSchema();
        
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        
        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('message', $schema['properties']);
        $this->assertArrayHasKey('finishReason', $schema['properties']);
        $this->assertArrayHasKey('tokenCount', $schema['properties']);
        
        // Check finishReason property
        $finishReasonSchema = $schema['properties']['finishReason'];
        $this->assertEquals('string', $finishReasonSchema['type']);
        $this->assertArrayHasKey('enum', $finishReasonSchema);
        $this->assertContains('stop', $finishReasonSchema['enum']);
        $this->assertContains('length', $finishReasonSchema['enum']);
        $this->assertContains('content_filter', $finishReasonSchema['enum']);
        $this->assertContains('tool_calls', $finishReasonSchema['enum']);
        $this->assertContains('error', $finishReasonSchema['enum']);
        
        // Check tokenCount property
        $tokenCountSchema = $schema['properties']['tokenCount'];
        $this->assertEquals('integer', $tokenCountSchema['type']);
        
        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals(['message', 'finishReason', 'tokenCount'], $schema['required']);
    }

    /**
     * Tests candidate with empty message parts.
     *
     * @return void
     */
    public function testWithEmptyMessageParts(): void
    {
        $message = new ModelMessage([]);
        
        $candidate = new Candidate(
            $message,
            FinishReasonEnum::stop(),
            0
        );
        
        $this->assertCount(0, $candidate->getMessage()->getParts());
        $this->assertEquals(0, $candidate->getTokenCount());
    }

    /**
     * Tests candidate with max length finish reason.
     *
     * @return void
     */
    public function testWithMaxLengthFinishReason(): void
    {
        $message = new ModelMessage([
            new MessagePart('This is a long response that was cut off due to reaching the maximum token limit...')
        ]);
        
        $candidate = new Candidate(
            $message,
            FinishReasonEnum::length(),
            4096
        );
        
        $this->assertTrue($candidate->getFinishReason()->isLength());
        $this->assertEquals(4096, $candidate->getTokenCount());
    }

    /**
     * Tests candidate with content filter finish reason.
     *
     * @return void
     */
    public function testWithContentFilterFinishReason(): void
    {
        $message = new ModelMessage([
            new MessagePart('I cannot provide that information.')
        ]);
        
        $candidate = new Candidate(
            $message,
            FinishReasonEnum::contentFilter(),
            8
        );
        
        $this->assertTrue($candidate->getFinishReason()->isContentFilter());
    }

    /**
     * Tests candidate with error finish reason.
     *
     * @return void
     */
    public function testWithErrorFinishReason(): void
    {
        $message = new ModelMessage([
            new MessagePart('An error occurred while generating the response.')
        ]);
        
        $candidate = new Candidate(
            $message,
            FinishReasonEnum::error(),
            9
        );
        
        $this->assertTrue($candidate->getFinishReason()->isError());
    }

    /**
     * Tests JSON serialization.
     *
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $message = new ModelMessage([
            new MessagePart('This is the AI response.'),
            new MessagePart('It contains multiple parts.')
        ]);
        
        $candidate = new Candidate(
            $message,
            FinishReasonEnum::stop(),
            45
        );
        
        $json = $this->assertJsonSerializeReturnsArray($candidate);
        
        $this->assertJsonHasKeys($json, ['message', 'finishReason', 'tokenCount']);
        $this->assertIsArray($json['message']);
        $this->assertEquals(FinishReasonEnum::stop()->value, $json['finishReason']);
        $this->assertEquals(45, $json['tokenCount']);
    }

    /**
     * Tests fromJson method.
     *
     * @return void
     */
    public function testFromJson(): void
    {
        $json = [
            'message' => [
                'role' => MessageRoleEnum::model()->value,
                'parts' => [
                    ['type' => MessagePartTypeEnum::text()->value, 'text' => 'Response text 1'],
                    ['type' => MessagePartTypeEnum::text()->value, 'text' => 'Response text 2']
                ]
            ],
            'finishReason' => FinishReasonEnum::stop()->value,
            'tokenCount' => 75
        ];
        
        $candidate = Candidate::fromJson($json);
        
        $this->assertInstanceOf(Candidate::class, $candidate);
        $this->assertEquals(FinishReasonEnum::stop(), $candidate->getFinishReason());
        $this->assertEquals(75, $candidate->getTokenCount());
        $this->assertCount(2, $candidate->getMessage()->getParts());
        $this->assertEquals('Response text 1', $candidate->getMessage()->getParts()[0]->getText());
        $this->assertEquals('Response text 2', $candidate->getMessage()->getParts()[1]->getText());
    }

    /**
     * Tests round-trip JSON serialization.
     *
     * @return void
     */
    public function testJsonRoundTrip(): void
    {
        $this->assertJsonRoundTrip(
            new Candidate(
                new ModelMessage([
                    new MessagePart('Generated response'),
                    new MessagePart(new FunctionCall('call_123', 'search', ['q' => 'test']))
                ]),
                FinishReasonEnum::toolCalls(),
                120
            ),
            function ($original, $restored) {
                $this->assertEquals($original->getFinishReason()->value, $restored->getFinishReason()->value);
                $this->assertEquals($original->getTokenCount(), $restored->getTokenCount());
                $this->assertCount(
                    count($original->getMessage()->getParts()),
                    $restored->getMessage()->getParts()
                );
                $this->assertEquals(
                    $original->getMessage()->getParts()[0]->getText(),
                    $restored->getMessage()->getParts()[0]->getText()
                );
                $this->assertEquals(
                    $original->getMessage()->getParts()[1]->getFunctionCall()->getId(),
                    $restored->getMessage()->getParts()[1]->getFunctionCall()->getId()
                );
            }
        );
    }

    /**
     * Tests Candidate implements WithJsonSerialization.
     *
     * @return void
     */
    public function testImplementsWithJsonSerialization(): void
    {
        $candidate = new Candidate(
            new ModelMessage([new MessagePart('test')]),
            FinishReasonEnum::stop(),
            10
        );
        $this->assertImplementsJsonSerialization($candidate);
    }
}
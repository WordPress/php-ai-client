<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use InvalidArgumentException;
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
use WordPress\AiClient\Tests\traits\ArrayTransformationTestTrait;
use WordPress\AiClient\Tools\DTO\FunctionCall;

/**
 * @covers \WordPress\AiClient\Results\DTO\Candidate
 */
class CandidateTest extends TestCase
{
    use ArrayTransformationTestTrait;

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
        );

        $this->assertSame($message, $candidate->getMessage());
        $this->assertEquals(FinishReasonEnum::stop(), $candidate->getFinishReason());
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

        $candidate = new Candidate($message, $finishReason);

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
            FinishReasonEnum::toolCalls()
        );

        $this->assertCount(6, $candidate->getMessage()->getParts());
        $this->assertTrue($candidate->getFinishReason()->isToolCalls());
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
        );

        $parts = $candidate->getMessage()->getParts();
        $this->assertEquals('I\'ve generated the requested image:', $parts[0]->getText());
        $this->assertSame($file, $parts[1]->getFile());
        $this->assertEquals('The image shows a flowchart of the process.', $parts[2]->getText());
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message must be a model message.');

        new Candidate(
            $userMessage,
            FinishReasonEnum::stop()
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message must be a model message.');

        new Candidate(
            $message,
            FinishReasonEnum::stop()
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
        $this->assertArrayHasKey(Candidate::KEY_MESSAGE, $schema['properties']);
        $this->assertArrayHasKey(Candidate::KEY_FINISH_REASON, $schema['properties']);

        // Check finishReason property
        $finishReasonSchema = $schema['properties'][Candidate::KEY_FINISH_REASON];
        $this->assertEquals('string', $finishReasonSchema['type']);
        $this->assertArrayHasKey('enum', $finishReasonSchema);
        $this->assertContains('stop', $finishReasonSchema['enum']);
        $this->assertContains('length', $finishReasonSchema['enum']);
        $this->assertContains('content_filter', $finishReasonSchema['enum']);
        $this->assertContains('tool_calls', $finishReasonSchema['enum']);
        $this->assertContains('error', $finishReasonSchema['enum']);

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals([Candidate::KEY_MESSAGE, Candidate::KEY_FINISH_REASON], $schema['required']);
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
            FinishReasonEnum::stop()
        );

        $this->assertCount(0, $candidate->getMessage()->getParts());
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
            FinishReasonEnum::length()
        );

        $this->assertTrue($candidate->getFinishReason()->isLength());
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
            FinishReasonEnum::contentFilter()
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
            FinishReasonEnum::error()
        );

        $this->assertTrue($candidate->getFinishReason()->isError());
    }

    /**
     * Tests array transformation.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $message = new ModelMessage([
            new MessagePart('This is the AI response.'),
            new MessagePart('It contains multiple parts.')
        ]);

        $candidate = new Candidate(
            $message,
            FinishReasonEnum::stop()
        );

        $json = $this->assertToArrayReturnsArray($candidate);

        $this->assertArrayHasKeys($json, [Candidate::KEY_MESSAGE, Candidate::KEY_FINISH_REASON]);
        $this->assertIsArray($json[Candidate::KEY_MESSAGE]);
        $this->assertEquals(FinishReasonEnum::stop()->value, $json[Candidate::KEY_FINISH_REASON]);
    }

    /**
     * Tests fromJson method.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $json = [
            Candidate::KEY_MESSAGE => [
                Message::KEY_ROLE => MessageRoleEnum::model()->value,
                Message::KEY_PARTS => [
                    [MessagePart::KEY_TYPE => MessagePartTypeEnum::text()->value, MessagePart::KEY_TEXT => 'Response text 1'],
                    [MessagePart::KEY_TYPE => MessagePartTypeEnum::text()->value, MessagePart::KEY_TEXT => 'Response text 2']
                ]
            ],
            Candidate::KEY_FINISH_REASON => FinishReasonEnum::stop()->value,
        ];

        $candidate = Candidate::fromArray($json);

        $this->assertInstanceOf(Candidate::class, $candidate);
        $this->assertEquals(FinishReasonEnum::stop(), $candidate->getFinishReason());
        $this->assertCount(2, $candidate->getMessage()->getParts());
        $this->assertEquals('Response text 1', $candidate->getMessage()->getParts()[0]->getText());
        $this->assertEquals('Response text 2', $candidate->getMessage()->getParts()[1]->getText());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $this->assertArrayRoundTrip(
            new Candidate(
                new ModelMessage([
                    new MessagePart('Generated response'),
                    new MessagePart(new FunctionCall('call_123', 'search', ['q' => 'test']))
                ]),
                FinishReasonEnum::toolCalls()
            ),
            function ($original, $restored) {
                $this->assertEquals($original->getFinishReason()->value, $restored->getFinishReason()->value);
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
     * Tests Candidate implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $candidate = new Candidate(
            new ModelMessage([new MessagePart('test')]),
            FinishReasonEnum::stop()
        );
        $this->assertImplementsArrayTransformation($candidate);
    }
}

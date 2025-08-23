<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\Util;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Messages\Util\MessageUtil;

/**
 * @covers \WordPress\AiClient\Messages\Util\MessageUtil
 */
class MessageUtilTest extends TestCase
{
    /**
     * Tests that parseMessageFromInput returns the same Message instance if passed.
     *
     * @return void
     */
    public function testParseMessageFromInputWithMessageInstance(): void
    {
        $message = new Message(MessageRoleEnum::user(), [new MessagePart('Hello')]);
        $result = MessageUtil::parseMessageFromInput($message);
        $this->assertSame($message, $result);
    }

    /**
     * Tests that parseMessageFromInput correctly parses a message from an array.
     *
     * @return void
     */
    public function testParseMessageFromInputWithMessageArray(): void
    {
        $input = [
            'role' => 'user',
            'parts' => [
                ['text' => 'Hello from array']
            ],
        ];
        $result = MessageUtil::parseMessageFromInput($input);
        $this->assertInstanceOf(Message::class, $result);
        $this->assertEquals(MessageRoleEnum::user(), $result->getRole());
        $this->assertCount(1, $result->getParts());
        $this->assertEquals('Hello from array', $result->getParts()[0]->getText());
    }

    /**
     * Tests that parseMessageFromInput correctly parses a message from various single part inputs.
     *
     * @dataProvider singlePartInputProvider
     * @param mixed $input The input to test.
     * @param string $expectedText The expected text in the message part.
     * @return void
     */
    public function testParseMessageFromInputWithSinglePartInput($input, string $expectedText): void
    {
        $result = MessageUtil::parseMessageFromInput($input);
        $this->assertInstanceOf(Message::class, $result);
        $this->assertEquals(MessageRoleEnum::user(), $result->getRole());
        $this->assertCount(1, $result->getParts());
        $this->assertEquals($expectedText, $result->getParts()[0]->getText());
    }

    /**
     * Provides various single part inputs for testing.
     *
     * @return array
     */
    public function singlePartInputProvider(): array
    {
        return [
            'string' => ['Just a string', 'Just a string'],
            'MessagePart instance' => [new MessagePart('A message part'), 'A message part'],
            'MessagePart array' => [['text' => 'Part from array'], 'Part from array'],
        ];
    }

    /**
     * Tests that parseMessageFromInput correctly parses a message from multiple part inputs.
     *
     * @return void
     */
    public function testParseMessageFromInputWithMultiplePartInputs(): void
    {
        $part = new MessagePart('A message part');
        $input = [
            'First part',
            $part,
            ['text' => 'Third part'],
        ];
        $result = MessageUtil::parseMessageFromInput($input);
        $this->assertInstanceOf(Message::class, $result);
        $this->assertEquals(MessageRoleEnum::user(), $result->getRole());
        $this->assertCount(3, $result->getParts());
        $this->assertEquals('First part', $result->getParts()[0]->getText());
        $this->assertSame($part, $result->getParts()[1]);
        $this->assertEquals('Third part', $result->getParts()[2]->getText());
    }

    /**
     * Tests that parseMessagesFromInput correctly parses an array of Message instances.
     *
     * @return void
     */
    public function testParseMessagesFromInputWithArrayOfMessageInstances(): void
    {
        $messages = [
            new Message(MessageRoleEnum::user(), [new MessagePart('Hello')]),
            new Message(MessageRoleEnum::model(), [new MessagePart('Hi there')]),
        ];
        $result = MessageUtil::parseMessagesFromInput($messages);
        $this->assertCount(2, $result);
        $this->assertSame($messages[0], $result[0]);
        $this->assertSame($messages[1], $result[1]);
    }

    /**
     * Tests that parseMessagesFromInput correctly parses an array of message arrays.
     *
     * @return void
     */
    public function testParseMessagesFromInputWithArrayOfMessageArrays(): void
    {
        $input = [
            [
                'role' => 'user',
                'parts' => [['text' => 'Message 1']],
            ],
            [
                'role' => 'model',
                'parts' => [['text' => 'Message 2']],
            ],
        ];
        $result = MessageUtil::parseMessagesFromInput($input);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Message::class, $result[0]);
        $this->assertEquals(MessageRoleEnum::user(), $result[0]->getRole());
        $this->assertEquals('Message 1', $result[0]->getParts()[0]->getText());
        $this->assertInstanceOf(Message::class, $result[1]);
        $this->assertEquals(MessageRoleEnum::model(), $result[1]->getRole());
        $this->assertEquals('Message 2', $result[1]->getParts()[0]->getText());
    }

    /**
     * Tests that parseMessagesFromInput correctly handles a single message input.
     *
     * @return void
     */
    public function testParseMessagesFromInputWithSingleMessageInput(): void
    {
        $input = 'A single message';
        $result = MessageUtil::parseMessagesFromInput($input);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Message::class, $result[0]);
        $this->assertEquals(MessageRoleEnum::user(), $result[0]->getRole());
        $this->assertEquals('A single message', $result[0]->getParts()[0]->getText());
    }

    /**
     * Tests that parseMessagesFromInput correctly handles a single message array input.
     *
     * @return void
     */
    public function testParseMessagesFromInputWithSingleMessageArrayInput(): void
    {
        $input = [
            'role' => 'user',
            'parts' => [['text' => 'Test prompt']],
        ];
        $result = MessageUtil::parseMessagesFromInput($input);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Message::class, $result[0]);
        $this->assertEquals(MessageRoleEnum::user(), $result[0]->getRole());
        $this->assertEquals('Test prompt', $result[0]->getParts()[0]->getText());
    }
}

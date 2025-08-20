<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Utils;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Utils\PromptNormalizer;

/**
 * @covers \WordPress\AiClient\Utils\PromptNormalizer
 */
class PromptNormalizerTest extends TestCase
{
    /**
     * Tests normalizing string input.
     */
    public function testNormalizeString(): void
    {
        $prompt = 'Test prompt';
        $result = PromptNormalizer::normalize($prompt);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(UserMessage::class, $result[0]);
        $this->assertCount(1, $result[0]->getParts());
        $this->assertEquals('Test prompt', $result[0]->getParts()[0]->getText());
    }

    /**
     * Tests normalizing MessagePart input.
     */
    public function testNormalizeMessagePart(): void
    {
        $messagePart = new MessagePart('Test message part');
        $result = PromptNormalizer::normalize($messagePart);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(UserMessage::class, $result[0]);
        $this->assertCount(1, $result[0]->getParts());
        $this->assertSame($messagePart, $result[0]->getParts()[0]);
    }

    /**
     * Tests normalizing single Message input.
     */
    public function testNormalizeSingleMessage(): void
    {
        $messagePart = new MessagePart('Test message');
        $message = new UserMessage([$messagePart]);
        $result = PromptNormalizer::normalize($message);

        $this->assertCount(1, $result);
        $this->assertSame($message, $result[0]);
    }

    /**
     * Tests normalizing array of Messages.
     */
    public function testNormalizeMessageArray(): void
    {
        $message1 = new UserMessage([new MessagePart('First message')]);
        $message2 = new UserMessage([new MessagePart('Second message')]);
        $messages = [$message1, $message2];

        $result = PromptNormalizer::normalize($messages);

        $this->assertCount(2, $result);
        $this->assertSame($message1, $result[0]);
        $this->assertSame($message2, $result[1]);
    }

    /**
     * Tests normalizing array of MessageParts.
     */
    public function testNormalizeMessagePartArray(): void
    {
        $part1 = new MessagePart('First part');
        $part2 = new MessagePart('Second part');
        $parts = [$part1, $part2];

        $result = PromptNormalizer::normalize($parts);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(UserMessage::class, $result[0]);
        $this->assertInstanceOf(UserMessage::class, $result[1]);
        $this->assertSame($part1, $result[0]->getParts()[0]);
        $this->assertSame($part2, $result[1]->getParts()[0]);
    }

    /**
     * Tests empty array throws exception.
     */
    public function testNormalizeEmptyArrayThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prompt array cannot be empty');

        PromptNormalizer::normalize([]);
    }

    /**
     * Tests mixed array content throws exception.
     */
    public function testNormalizeMixedArrayThrowsException(): void
    {
        $part = new MessagePart('Test');
        $invalidArray = [$part, 'string', 123];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array element at index 2 must be a string, MessagePart, or Message, integer given');

        PromptNormalizer::normalize($invalidArray);
    }

    /**
     * Tests invalid input type throws exception.
     */
    public function testNormalizeInvalidInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array element at index 0 must be a string, MessagePart, or Message, integer given');

        PromptNormalizer::normalize(123);
    }

    /**
     * Tests array with invalid object types throws exception.
     */
    public function testNormalizeArrayWithInvalidObjectsThrowsException(): void
    {
        $invalidArray = [new \stdClass(), new \DateTime()];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array element at index 0 must be a string, MessagePart, or Message, object given');

        PromptNormalizer::normalize($invalidArray);
    }
}

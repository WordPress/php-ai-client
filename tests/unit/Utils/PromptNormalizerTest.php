<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Utils;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
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

        $this->assertInstanceOf(UserMessage::class, $result);
        $this->assertCount(1, $result->getParts());
        $this->assertEquals('Test prompt', $result->getParts()[0]->getText());
    }

    /**
     * Tests normalizing MessagePart input.
     */
    public function testNormalizeMessagePart(): void
    {
        $messagePart = new MessagePart('Test message part');
        $result = PromptNormalizer::normalize($messagePart);

        $this->assertInstanceOf(UserMessage::class, $result);
        $this->assertCount(1, $result->getParts());
        $this->assertSame($messagePart, $result->getParts()[0]);
    }

    /**
     * Tests normalizing single Message input.
     */
    public function testNormalizeSingleMessage(): void
    {
        $messagePart = new MessagePart('Test message');
        $message = new UserMessage([$messagePart]);
        $result = PromptNormalizer::normalize($message);

        $this->assertSame($message, $result);
    }

    /**
     * Tests normalizing array of Messages throws exception.
     */
    public function testNormalizeMessageArray(): void
    {
        $message1 = new UserMessage([new MessagePart('First message')]);
        $message2 = new UserMessage([new MessagePart('Second message')]);
        $messages = [$message1, $message2];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Array items must be strings or MessagePart objects, got ' . UserMessage::class
        );

        PromptNormalizer::normalize($messages);
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

        $this->assertInstanceOf(UserMessage::class, $result);
        $this->assertCount(2, $result->getParts());
        $this->assertSame($part1, $result->getParts()[0]);
        $this->assertSame($part2, $result->getParts()[1]);
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
        $this->expectExceptionMessage(
            'Array items must be strings or MessagePart objects, got integer'
        );

        PromptNormalizer::normalize($invalidArray);
    }

    /**
     * Tests invalid input type throws exception.
     */
    public function testNormalizeInvalidInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid prompt format: expected string, Message, MessagePart, structured array, ' .
            'or array of strings/MessageParts, got integer'
        );

        PromptNormalizer::normalize(123);
    }

    /**
     * Tests array with invalid object types throws exception.
     */
    public function testNormalizeArrayWithInvalidObjectsThrowsException(): void
    {
        $invalidArray = [new \stdClass(), new \DateTime()];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Array items must be strings or MessagePart objects, got stdClass'
        );

        PromptNormalizer::normalize($invalidArray);
    }

    /**
     * Tests normalizing message array.
     */
    public function testNormalizeMessageArrayShape(): void
    {
        $messageArray = [
            'role' => 'user',
            'parts' => [
                ['text' => 'You are a helpful assistant.'],
                ['text' => 'Be concise.']
            ]
        ];

        $result = PromptNormalizer::normalize($messageArray);

        $this->assertInstanceOf(Message::class, $result);
        $this->assertTrue($result->getRole()->equals(MessageRoleEnum::user()));
        $this->assertCount(2, $result->getParts());
        $this->assertEquals('You are a helpful assistant.', $result->getParts()[0]->getText());
        $this->assertEquals('Be concise.', $result->getParts()[1]->getText());
    }

    /**
     * Tests normalizing mixed array with strings and MessageParts.
     */
    public function testNormalizeMixedStringAndMessageParts(): void
    {
        $mixed = [
            'User message',
            new MessagePart('Part message')
        ];

        $result = PromptNormalizer::normalize($mixed);

        $this->assertInstanceOf(UserMessage::class, $result);
        $this->assertCount(2, $result->getParts());
        $this->assertEquals('User message', $result->getParts()[0]->getText());
        $this->assertEquals('Part message', $result->getParts()[1]->getText());
    }

    /**
     * Tests message array with invalid role throws exception.
     */
    public function testMessageArrayInvalidRoleThrowsException(): void
    {
        $messageArray = [
            'role' => 'invalid_role',
            'parts' => [['text' => 'Some text']]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid_role is not a valid backing value for enum');

        PromptNormalizer::normalize($messageArray);
    }

    /**
     * Tests message array with missing parts is treated as string array.
     */
    public function testMessageArrayMissingPartsTreatedAsStringArray(): void
    {
        $messageArray = [
            'role' => 'user'
            // Missing 'parts' - will be treated as array with string value 'user'
        ];

        $result = PromptNormalizer::normalize($messageArray);

        // It should create a UserMessage with a single part containing 'user' text
        $this->assertInstanceOf(UserMessage::class, $result);
        $this->assertCount(1, $result->getParts());
        $this->assertEquals('user', $result->getParts()[0]->getText());
    }

    /**
     * Tests role mapping for different variations.
     */
    public function testRoleMapping(): void
    {
        // Test user role
        $userMsg = [
            'role' => 'user',
            'parts' => [['text' => 'User']]
        ];
        $result = PromptNormalizer::normalize($userMsg);
        $this->assertTrue($result->getRole()->equals(MessageRoleEnum::user()));

        // Test model role
        $modelMsg = [
            'role' => 'model',
            'parts' => [['text' => 'Model']]
        ];
        $result = PromptNormalizer::normalize($modelMsg);
        $this->assertTrue($result->getRole()->equals(MessageRoleEnum::model()));
    }

    /**
     * Tests that isMessagesList returns true for a list of Message objects.
     */
    public function testIsMessagesListReturnsTrueForMessages(): void
    {
        $messages = [
            new UserMessage([new MessagePart('First')]),
            new UserMessage([new MessagePart('Second')]),
        ];

        $this->assertTrue(PromptNormalizer::isMessagesList($messages));
    }

    /**
     * Tests that isMessagesList returns false for non-list arrays.
     */
    public function testIsMessagesListReturnsFalseForNonList(): void
    {
        $messages = [
            1 => new UserMessage([new MessagePart('First')]),
            2 => new UserMessage([new MessagePart('Second')]),
        ];

        $this->assertFalse(PromptNormalizer::isMessagesList($messages));
    }

    /**
     * Tests that isMessagesList returns false for empty arrays.
     */
    public function testIsMessagesListReturnsFalseForEmpty(): void
    {
        $this->assertFalse(PromptNormalizer::isMessagesList([]));
    }

    /**
     * Tests that isMessagesList returns false for arrays with non-Message objects.
     */
    public function testIsMessagesListReturnsFalseForMixedTypes(): void
    {
        $mixed = [
            new UserMessage([new MessagePart('First')]),
            'string',
        ];

        $this->assertFalse(PromptNormalizer::isMessagesList($mixed));
    }

    /**
     * Tests that isMessagesList returns false for non-array types.
     */
    public function testIsMessagesListReturnsFalseForNonArray(): void
    {
        $this->assertFalse(PromptNormalizer::isMessagesList('string'));
        $this->assertFalse(PromptNormalizer::isMessagesList(123));
        $this->assertFalse(PromptNormalizer::isMessagesList(new UserMessage([new MessagePart('Test')])));
    }
}

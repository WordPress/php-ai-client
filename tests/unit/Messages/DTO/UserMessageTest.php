<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Tests\traits\ArrayTransformationTestTrait;

/**
 * @covers UserMessage
 */
class UserMessageTest extends TestCase
{
    use ArrayTransformationTestTrait;

    /**
     * Tests creating UserMessage automatically sets USER role.
     *
     * @return void
     */
    public function testAutomaticallySetsUserRole(): void
    {
        $parts = [
            new MessagePart('Hello, can you help me with PHP?'),
        ];

        $message = new UserMessage($parts);

        $this->assertEquals(MessageRoleEnum::user(), $message->getRole());
        $this->assertTrue($message->getRole()->isUser());
    }

    /**
     * Tests UserMessage with multiple parts.
     *
     * @return void
     */
    public function testWithMultipleParts(): void
    {
        $parts = [
            new MessagePart('I have a question about this code:'),
            new MessagePart('```php'),
            new MessagePart('function calculateSum($a, $b) {'),
            new MessagePart('    return $a + $b;'),
            new MessagePart('}'),
            new MessagePart('```'),
            new MessagePart('How can I add type hints?'),
        ];

        $message = new UserMessage($parts);

        $this->assertCount(7, $message->getParts());
        $this->assertEquals($parts, $message->getParts());
    }

    /**
     * Tests UserMessage with empty parts.
     *
     * @return void
     */
    public function testWithEmptyParts(): void
    {
        $message = new UserMessage([]);

        $this->assertEquals(MessageRoleEnum::user(), $message->getRole());
        $this->assertCount(0, $message->getParts());
    }

    /**
     * Tests UserMessage inherits from Message.
     *
     * @return void
     */
    public function testInheritsFromMessage(): void
    {
        $message = new UserMessage([]);

        $this->assertInstanceOf(Message::class, $message);
    }

    /**
     * Tests UserMessage with file attachment.
     *
     * @return void
     */
    public function testWithFileAttachment(): void
    {
        $file = new File('https://example.com/document.pdf', 'application/pdf');

        $parts = [
            new MessagePart('Can you analyze this document for me?'),
            new MessagePart($file),
            new MessagePart('I need a summary of the key points.'),
        ];

        $message = new UserMessage($parts);

        $this->assertCount(3, $message->getParts());
        $this->assertEquals('Can you analyze this document for me?', $message->getParts()[0]->getText());
        $this->assertSame($file, $message->getParts()[1]->getFile());
        $this->assertEquals('I need a summary of the key points.', $message->getParts()[2]->getText());
    }

    /**
     * Tests UserMessage with image and text.
     *
     * @return void
     */
    public function testWithImageAndText(): void
    {
        $imageFile = new File('data:image/png;base64,iVBORw0KGgoAAAANS', 'image/png');

        $parts = [
            new MessagePart('What do you see in this image?'),
            new MessagePart($imageFile),
        ];

        $message = new UserMessage($parts);

        $this->assertCount(2, $message->getParts());
        $this->assertNotNull($message->getParts()[0]->getText());
        $this->assertNotNull($message->getParts()[1]->getFile());
        $this->assertEquals('image/png', $message->getParts()[1]->getFile()->getMimeType());
    }

    /**
     * Tests JSON schema is inherited from parent.
     *
     * @return void
     */
    public function testJsonSchemaInheritance(): void
    {
        $schema = UserMessage::getJsonSchema();
        $parentSchema = Message::getJsonSchema();

        $this->assertEquals($parentSchema, $schema);
    }

    /**
     * Tests UserMessage with single question.
     *
     * @return void
     */
    public function testWithSingleQuestion(): void
    {
        $question = 'What is the difference between abstract classes and interfaces in PHP?';

        $message = new UserMessage([new MessagePart($question)]);

        $this->assertCount(1, $message->getParts());
        $this->assertEquals($question, $message->getParts()[0]->getText());
    }

    /**
     * Tests UserMessage with code example request.
     *
     * @return void
     */
    public function testWithCodeExampleRequest(): void
    {
        $parts = [
            new MessagePart('Can you show me an example of the Singleton pattern in PHP?'),
            new MessagePart('Please include:'),
            new MessagePart('1. Private constructor'),
            new MessagePart('2. Static instance method'),
            new MessagePart('3. Clone prevention'),
        ];

        $message = new UserMessage($parts);

        $this->assertCount(5, $message->getParts());
        $this->assertTrue($message->getRole()->isUser());
    }

    /**
     * Tests that parts are preserved in order.
     *
     * @return void
     */
    public function testPreservesPartOrder(): void
    {
        $parts = [
            new MessagePart('First part'),
            new MessagePart('Second part'),
            new MessagePart('Third part'),
            new MessagePart('Fourth part'),
        ];

        $message = new UserMessage($parts);
        $retrievedParts = $message->getParts();

        $this->assertEquals('First part', $retrievedParts[0]->getText());
        $this->assertEquals('Second part', $retrievedParts[1]->getText());
        $this->assertEquals('Third part', $retrievedParts[2]->getText());
        $this->assertEquals('Fourth part', $retrievedParts[3]->getText());
    }

    /**
     * Tests UserMessage with multiple files.
     *
     * @return void
     */
    public function testWithMultipleFiles(): void
    {
        $file1 = new File('https://example.com/image1.jpg', 'image/jpeg');
        $file2 = new File('https://example.com/image2.png', 'image/png');
        $file3 = new File('data:application/pdf;base64,JVBERi0xLjMNCg==', 'application/pdf');

        $parts = [
            new MessagePart('Please compare these images:'),
            new MessagePart($file1),
            new MessagePart($file2),
            new MessagePart('And review this document:'),
            new MessagePart($file3),
        ];

        $message = new UserMessage($parts);

        $this->assertCount(5, $message->getParts());
        $this->assertInstanceOf(File::class, $message->getParts()[1]->getFile());
        $this->assertInstanceOf(File::class, $message->getParts()[2]->getFile());
        $this->assertInstanceOf(File::class, $message->getParts()[4]->getFile());
    }

    /**
     * Tests array transformation.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $message = new UserMessage([
            new MessagePart('Hello, I need help'),
            new MessagePart('Can you assist?')
        ]);

        $json = $this->assertToArrayReturnsArray($message);

        $this->assertArrayHasKeys($json, ['role', 'parts']);
        $this->assertEquals(MessageRoleEnum::user()->value, $json['role']);
        $this->assertCount(2, $json['parts']);
        $this->assertEquals('Hello, I need help', $json['parts'][0]['text']);
        $this->assertEquals('Can you assist?', $json['parts'][1]['text']);
    }

    /**
     * Tests fromJson method.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $json = [
            'role' => MessageRoleEnum::user()->value,
            'parts' => [
                ['type' => MessagePartTypeEnum::text()->value, 'text' => 'Question 1'],
                ['type' => MessagePartTypeEnum::text()->value, 'text' => 'Question 2']
            ]
        ];

        $message = UserMessage::fromArray($json);

        $this->assertInstanceOf(UserMessage::class, $message);
        $this->assertEquals(MessageRoleEnum::user(), $message->getRole());
        $this->assertCount(2, $message->getParts());
        $this->assertEquals('Question 1', $message->getParts()[0]->getText());
        $this->assertEquals('Question 2', $message->getParts()[1]->getText());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $this->assertArrayRoundTrip(
            new UserMessage([
                new MessagePart('Test message'),
                new MessagePart(new File('https://example.com/image.jpg', 'image/jpeg'))
            ]),
            function ($original, $restored) {
                $this->assertEquals($original->getRole()->value, $restored->getRole()->value);
                $this->assertCount(count($original->getParts()), $restored->getParts());
                $this->assertEquals(
                    $original->getParts()[0]->getText(),
                    $restored->getParts()[0]->getText()
                );
                $this->assertEquals(
                    $original->getParts()[1]->getFile()->getUrl(),
                    $restored->getParts()[1]->getFile()->getUrl()
                );
            }
        );
    }

    /**
     * Tests UserMessage implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $message = new UserMessage([new MessagePart('test')]);
        $this->assertImplementsArrayTransformation($message);
    }

    /**
     * Tests that withPart returns a new Message with user role.
     *
     * @since n.e.x.t
     */
    public function testWithPartReturnsNewMessage(): void
    {
        $original = new UserMessage([new MessagePart('User text')]);
        $updated = $original->withPart(new MessagePart('More text'));

        $this->assertInstanceOf(Message::class, $updated);
        $this->assertNotSame($original, $updated);
        $this->assertCount(2, $updated->getParts());
        $this->assertEquals(MessageRoleEnum::user(), $updated->getRole());
        $this->assertTrue($updated->getRole()->isUser());
        $this->assertEquals('User text', $updated->getParts()[0]->getText());
        $this->assertEquals('More text', $updated->getParts()[1]->getText());
    }
}

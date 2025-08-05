<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Messages\ValueObjects\TextContent;
use WordPress\AiClient\Messages\ValueObjects\FileContent;
use WordPress\AiClient\Tests\traits\ArrayTransformationTestTrait;

/**
 * @covers \WordPress\AiClient\Messages\DTO\UserMessage
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
            new MessagePart(new TextContent('Hello, can you help me with PHP?')),
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
            new MessagePart(new TextContent('I have a question about this code:')),
            new MessagePart(new TextContent('```php')),
            new MessagePart(new TextContent('function calculateSum($a, $b) {')),
            new MessagePart(new TextContent('    return $a + $b;')),
            new MessagePart(new TextContent('}')),
            new MessagePart(new TextContent('```')),
            new MessagePart(new TextContent('How can I add type hints?')),
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
        
        $this->assertInstanceOf(\WordPress\AiClient\Messages\DTO\Message::class, $message);
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
            new MessagePart(new TextContent('Can you analyze this document for me?')),
            new MessagePart(new FileContent($file)),
            new MessagePart(new TextContent('I need a summary of the key points.')),
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
            new MessagePart(new TextContent('What do you see in this image?')),
            new MessagePart(new FileContent($imageFile)),
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
        $parentSchema = \WordPress\AiClient\Messages\DTO\Message::getJsonSchema();
        
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
        
        $message = new UserMessage([new MessagePart(new TextContent($question))]);
        
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
            new MessagePart(new TextContent('Can you show me an example of the Singleton pattern in PHP?')),
            new MessagePart(new TextContent('Please include:')),
            new MessagePart(new TextContent('1. Private constructor')),
            new MessagePart(new TextContent('2. Static instance method')),
            new MessagePart(new TextContent('3. Clone prevention')),
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
            new MessagePart(new TextContent('First part')),
            new MessagePart(new TextContent('Second part')),
            new MessagePart(new TextContent('Third part')),
            new MessagePart(new TextContent('Fourth part')),
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
            new MessagePart(new TextContent('Please compare these images:')),
            new MessagePart(new FileContent($file1)),
            new MessagePart(new FileContent($file2)),
            new MessagePart(new TextContent('And review this document:')),
            new MessagePart(new FileContent($file3)),
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
            new MessagePart(new TextContent('Hello, I need help')),
            new MessagePart(new TextContent('Can you assist?'))
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
                new MessagePart(new TextContent('Test message')),
                new MessagePart(new FileContent(new File('https://example.com/image.jpg', 'image/jpeg')))
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
        $message = new UserMessage([new MessagePart(new TextContent('test'))]);
        $this->assertImplementsArrayTransformation($message);
    }
}
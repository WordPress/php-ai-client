<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;

/**
 * @covers \WordPress\AiClient\Messages\DTO\ModelMessage
 */
class ModelMessageTest extends TestCase
{
    /**
     * Tests creating ModelMessage automatically sets MODEL role.
     *
     * @return void
     */
    public function testAutomaticallySetsModelRole(): void
    {
        $parts = [
            new MessagePart('This is a response from the AI model.'),
        ];
        
        $message = new ModelMessage($parts);
        
        $this->assertEquals(MessageRoleEnum::model(), $message->getRole());
        $this->assertTrue($message->getRole()->isModel());
    }

    /**
     * Tests ModelMessage with multiple parts.
     *
     * @return void
     */
    public function testWithMultipleParts(): void
    {
        $parts = [
            new MessagePart('Let me help you with that.'),
            new MessagePart('Here are the steps:'),
            new MessagePart('1. First step'),
            new MessagePart('2. Second step'),
        ];
        
        $message = new ModelMessage($parts);
        
        $this->assertCount(4, $message->getParts());
        $this->assertEquals($parts, $message->getParts());
    }

    /**
     * Tests ModelMessage with empty parts.
     *
     * @return void
     */
    public function testWithEmptyParts(): void
    {
        $message = new ModelMessage([]);
        
        $this->assertEquals(MessageRoleEnum::model(), $message->getRole());
        $this->assertCount(0, $message->getParts());
    }

    /**
     * Tests ModelMessage inherits from Message.
     *
     * @return void
     */
    public function testInheritsFromMessage(): void
    {
        $message = new ModelMessage([]);
        
        $this->assertInstanceOf(\WordPress\AiClient\Messages\DTO\Message::class, $message);
    }

    /**
     * Tests ModelMessage with various content types.
     *
     * @return void
     */
    public function testWithVariousContentTypes(): void
    {
        $file = new \WordPress\AiClient\Files\DTO\File('https://example.com/image.jpg', 'image/jpeg');
        $functionCall = new \WordPress\AiClient\Tools\DTO\FunctionCall('func_123', 'search', ['q' => 'test']);
        $functionResponse = new \WordPress\AiClient\Tools\DTO\FunctionResponse('func_123', 'search', ['results' => []]);
        
        $parts = [
            new MessagePart('I found the following:'),
            new MessagePart($file),
            new MessagePart($functionCall),
            new MessagePart($functionResponse),
        ];
        
        $message = new ModelMessage($parts);
        
        $this->assertEquals('I found the following:', $message->getParts()[0]->getText());
        $this->assertSame($file, $message->getParts()[1]->getFile());
        $this->assertSame($functionCall, $message->getParts()[2]->getFunctionCall());
        $this->assertSame($functionResponse, $message->getParts()[3]->getFunctionResponse());
    }

    /**
     * Tests JSON schema is inherited from parent.
     *
     * @return void
     */
    public function testJsonSchemaInheritance(): void
    {
        $schema = ModelMessage::getJsonSchema();
        $parentSchema = \WordPress\AiClient\Messages\DTO\Message::getJsonSchema();
        
        $this->assertEquals($parentSchema, $schema);
    }
}
<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Tests\traits\ArrayTransformationTestTrait;
use WordPress\AiClient\Tools\DTO\FunctionCall;

/**
 * @covers \WordPress\AiClient\Messages\DTO\ModelMessage
 */
class ModelMessageTest extends TestCase
{
    use ArrayTransformationTestTrait;

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

        $this->assertInstanceOf(Message::class, $message);
    }

    /**
     * Tests ModelMessage with various content types.
     *
     * @return void
     */
    public function testWithVariousContentTypes(): void
    {
        $file = new File('https://example.com/image.jpg', 'image/jpeg');
        $functionCall = new FunctionCall('func_123', 'search', ['q' => 'test']);

        $parts = [
            new MessagePart('I found the following:'),
            new MessagePart($file),
            new MessagePart($functionCall),
            new MessagePart('Here are the results based on my search.'),
        ];

        $message = new ModelMessage($parts);

        $this->assertEquals('I found the following:', $message->getParts()[0]->getText());
        $this->assertSame($file, $message->getParts()[1]->getFile());
        $this->assertSame($functionCall, $message->getParts()[2]->getFunctionCall());
        $this->assertEquals('Here are the results based on my search.', $message->getParts()[3]->getText());
    }

    /**
     * Tests JSON schema is inherited from parent.
     *
     * @return void
     */
    public function testJsonSchemaInheritance(): void
    {
        $schema = ModelMessage::getJsonSchema();
        $parentSchema = Message::getJsonSchema();

        $this->assertEquals($parentSchema, $schema);
    }

    /**
     * Tests array transformation.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $message = new ModelMessage([
            new MessagePart('I can help you with that.'),
            new MessagePart('Here is the solution:')
        ]);

        $json = $this->assertToArrayReturnsArray($message);

        $this->assertArrayHasKeys($json, ['role', 'parts']);
        $this->assertEquals(MessageRoleEnum::model()->value, $json['role']);
        $this->assertCount(2, $json['parts']);
        $this->assertEquals('I can help you with that.', $json['parts'][0]['text']);
        $this->assertEquals('Here is the solution:', $json['parts'][1]['text']);
    }

    /**
     * Tests fromJson method.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $json = [
            'role' => MessageRoleEnum::model()->value,
            'parts' => [
                ['type' => MessagePartTypeEnum::text()->value, 'text' => 'Model response 1'],
                ['type' => MessagePartTypeEnum::text()->value, 'text' => 'Model response 2']
            ]
        ];

        $message = ModelMessage::fromArray($json);

        $this->assertInstanceOf(ModelMessage::class, $message);
        $this->assertEquals(MessageRoleEnum::model(), $message->getRole());
        $this->assertCount(2, $message->getParts());
        $this->assertEquals('Model response 1', $message->getParts()[0]->getText());
        $this->assertEquals('Model response 2', $message->getParts()[1]->getText());
    }

    /**
     * Tests round-trip array transformation with function call.
     *
     * @return void
     */
    public function testArrayRoundTripWithFunctionCall(): void
    {
        $this->assertArrayRoundTrip(
            new ModelMessage([
                new MessagePart('I\'ll search for that information.'),
                new MessagePart(new FunctionCall('search_123', 'webSearch', ['query' => 'PHP 8 features']))
            ]),
            function ($original, $restored) {
                $this->assertEquals($original->getRole()->value, $restored->getRole()->value);
                $this->assertCount(count($original->getParts()), $restored->getParts());
                $this->assertEquals(
                    $original->getParts()[0]->getText(),
                    $restored->getParts()[0]->getText()
                );
                $this->assertEquals(
                    $original->getParts()[1]->getFunctionCall()->getId(),
                    $restored->getParts()[1]->getFunctionCall()->getId()
                );
                $this->assertEquals(
                    $original->getParts()[1]->getFunctionCall()->getName(),
                    $restored->getParts()[1]->getFunctionCall()->getName()
                );
            }
        );
    }

    /**
     * Tests ModelMessage implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $message = new ModelMessage([new MessagePart('test')]);
        $this->assertImplementsArrayTransformation($message);
    }
}

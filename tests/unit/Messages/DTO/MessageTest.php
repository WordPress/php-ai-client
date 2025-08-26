<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * @covers \WordPress\AiClient\Messages\DTO\Message
 */
class MessageTest extends TestCase
{
    /**
     * Tests creating Message with single text part.
     *
     * @return void
     */
    public function testCreateWithSingleTextPart(): void
    {
        $role = MessageRoleEnum::user();
        $part = new MessagePart('Hello, AI!');
        $message = new Message($role, [$part]);

        $this->assertEquals($role, $message->getRole());
        $this->assertCount(1, $message->getParts());
        $this->assertSame($part, $message->getParts()[0]);
    }

    /**
     * Tests creating Message with multiple parts.
     *
     * @return void
     */
    public function testCreateWithMultipleParts(): void
    {
        $role = MessageRoleEnum::model();
        $parts = [
            new MessagePart('Here is the information you requested:'),
            new MessagePart(new File('https://example.com/data.json', 'application/json')),
            new MessagePart('Let me know if you need anything else.'),
        ];

        $message = new Message($role, $parts);

        $this->assertEquals($role, $message->getRole());
        $this->assertCount(3, $message->getParts());
        $this->assertEquals($parts, $message->getParts());
    }

    /**
     * Tests creating Message with empty parts array.
     *
     * @return void
     */
    public function testCreateWithEmptyParts(): void
    {
        $role = MessageRoleEnum::user();
        $message = new Message($role, []);

        $this->assertEquals($role, $message->getRole());
        $this->assertCount(0, $message->getParts());
        $this->assertEquals([], $message->getParts());
    }

    /**
     * Tests with different roles.
     *
     * @dataProvider roleProvider
     * @param MessageRoleEnum $role
     * @return void
     */
    public function testWithDifferentRoles(MessageRoleEnum $role): void
    {
        $part = new MessagePart('Test message');
        $message = new Message($role, [$part]);

        $this->assertEquals($role, $message->getRole());
    }

    /**
     * Provides different message roles.
     *
     * @return array
     */
    public function roleProvider(): array
    {
        return [
            'user' => [MessageRoleEnum::user()],
            'model' => [MessageRoleEnum::model()],
        ];
    }

    /**
     * Tests complex message with all part types.
     *
     * @return void
     */
    public function testComplexMessageWithAllPartTypes(): void
    {
        $role = MessageRoleEnum::model();
        $parts = [
            new MessagePart('I\'ll help you with that. Let me search for the information.'),
            new MessagePart(new FunctionCall('search_123', 'webSearch', ['query' => 'latest PHP news'])),
            new MessagePart(new FunctionResponse('search_123', 'webSearch', ['results' => ['item1', 'item2']])),
            new MessagePart('Based on my search, here are the latest PHP news:'),
            new MessagePart(new File('data:text/plain;base64,SGVsbG8=', 'text/plain')),
        ];

        $message = new Message($role, $parts);

        $this->assertCount(5, $message->getParts());

        // Verify each part type
        $this->assertEquals(
            'I\'ll help you with that. Let me search for the information.',
            $message->getParts()[0]->getText()
        );
        $this->assertInstanceOf(FunctionCall::class, $message->getParts()[1]->getFunctionCall());
        $this->assertInstanceOf(FunctionResponse::class, $message->getParts()[2]->getFunctionResponse());
        $this->assertEquals('Based on my search, here are the latest PHP news:', $message->getParts()[3]->getText());
        $this->assertInstanceOf(File::class, $message->getParts()[4]->getFile());
    }

    /**
     * Tests JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = Message::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);

        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey(Message::KEY_ROLE, $schema['properties']);
        $this->assertArrayHasKey(Message::KEY_PARTS, $schema['properties']);

        // Check role property
        $roleSchema = $schema['properties'][Message::KEY_ROLE];
        $this->assertEquals('string', $roleSchema['type']);
        $this->assertArrayHasKey('enum', $roleSchema);
        $this->assertContains('user', $roleSchema['enum']);
        $this->assertContains('model', $roleSchema['enum']);

        // Check parts property
        $partsSchema = $schema['properties'][Message::KEY_PARTS];
        $this->assertEquals('array', $partsSchema['type']);
        $this->assertArrayHasKey('items', $partsSchema);
        $this->assertIsArray($partsSchema['items']);

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals([Message::KEY_ROLE, Message::KEY_PARTS], $schema['required']);
    }

    /**
     * Tests message with large number of parts.
     *
     * @return void
     */
    public function testMessageWithManyParts(): void
    {
        $role = MessageRoleEnum::user();
        $parts = [];

        // Create 100 parts
        for ($i = 0; $i < 100; $i++) {
            $parts[] = new MessagePart("Part number $i");
        }

        $message = new Message($role, $parts);

        $this->assertCount(100, $message->getParts());
        $this->assertEquals('Part number 0', $message->getParts()[0]->getText());
        $this->assertEquals('Part number 99', $message->getParts()[99]->getText());
    }

    /**
     * Tests preserving part order.
     *
     * @return void
     */
    public function testPreservesPartOrder(): void
    {
        $parts = [
            new MessagePart('First'),
            new MessagePart('Second'),
            new MessagePart('Third'),
            new MessagePart('Fourth'),
        ];

        $message = new Message(MessageRoleEnum::user(), $parts);
        $retrievedParts = $message->getParts();

        $this->assertEquals('First', $retrievedParts[0]->getText());
        $this->assertEquals('Second', $retrievedParts[1]->getText());
        $this->assertEquals('Third', $retrievedParts[2]->getText());
        $this->assertEquals('Fourth', $retrievedParts[3]->getText());
    }

    /**
     * Tests model message with function response.
     *
     * @return void
     */
    public function testModelMessageWithFunctionResponse(): void
    {
        $role = MessageRoleEnum::model();
        $functionResponse = new FunctionResponse(
            'calc_123',
            'calculate',
            ['result' => 42, 'formula' => '6 * 7']
        );
        $part = new MessagePart($functionResponse);

        $message = new Message($role, [$part]);

        $this->assertTrue($message->getRole()->isModel());
        $this->assertNotNull($message->getParts()[0]->getFunctionResponse());
    }

    /**
     * Tests array transformation.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $role = MessageRoleEnum::user();
        $parts = [
            new MessagePart('Hello, world!'),
            new MessagePart('How are you?')
        ];
        $message = new Message($role, $parts);
        $json = $message->toArray();

        $this->assertIsArray($json);
        $this->assertEquals($role->value, $json[Message::KEY_ROLE]);
        $this->assertIsArray($json[Message::KEY_PARTS]);
        $this->assertCount(2, $json[Message::KEY_PARTS]);
        $this->assertEquals('Hello, world!', $json[Message::KEY_PARTS][0][MessagePart::KEY_TEXT]);
        $this->assertEquals('How are you?', $json[Message::KEY_PARTS][1][MessagePart::KEY_TEXT]);
    }

    /**
     * Tests fromJson method.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $json = [
            Message::KEY_ROLE => MessageRoleEnum::user()->value,
            Message::KEY_PARTS => [
                [
                    MessagePart::KEY_TYPE => MessagePartTypeEnum::text()->value,
                    MessagePart::KEY_TEXT => 'Hello, how can you help me?'
                ]
            ]
        ];

        $message = Message::fromArray($json);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(MessageRoleEnum::user(), $message->getRole());
        $this->assertCount(1, $message->getParts());
        $this->assertEquals('Hello, how can you help me?', $message->getParts()[0]->getText());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $original = new Message(
            MessageRoleEnum::model(),
            [
                new MessagePart('Here is the result:'),
                new MessagePart(new File('https://example.com/result.png', 'image/png'))
            ]
        );

        $json = $original->toArray();
        $restored = Message::fromArray($json);

        $this->assertEquals($original->getRole()->value, $restored->getRole()->value);
        $this->assertCount(count($original->getParts()), $restored->getParts());
        $this->assertEquals($original->getParts()[0]->getText(), $restored->getParts()[0]->getText());
        $this->assertEquals(
            $original->getParts()[1]->getFile()->getUrl(),
            $restored->getParts()[1]->getFile()->getUrl()
        );
    }

    /**
     * Tests Message implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $message = new Message(MessageRoleEnum::user(), [new MessagePart('test')]);

        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface::class,
            $message
        );
    }
}

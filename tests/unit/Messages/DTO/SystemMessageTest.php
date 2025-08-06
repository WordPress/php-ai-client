<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\SystemMessage;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Tests\traits\ArrayTransformationTestTrait;

/**
 * @covers \WordPress\AiClient\Messages\DTO\SystemMessage
 */
class SystemMessageTest extends TestCase
{
    use ArrayTransformationTestTrait;

    /**
     * Tests creating SystemMessage automatically sets SYSTEM role.
     *
     * @return void
     */
    public function testAutomaticallySetsSystemRole(): void
    {
        $parts = [
            new MessagePart('You are a helpful AI assistant.'),
        ];

        $message = new SystemMessage($parts);

        $this->assertEquals(MessageRoleEnum::system(), $message->getRole());
        $this->assertTrue($message->getRole()->isSystem());
    }

    /**
     * Tests SystemMessage with multiple instruction parts.
     *
     * @return void
     */
    public function testWithMultipleInstructionParts(): void
    {
        $parts = [
            new MessagePart('You are an expert in PHP programming.'),
            new MessagePart('Always provide code examples when explaining concepts.'),
            new MessagePart('Be concise and clear in your explanations.'),
            new MessagePart('Follow PSR-12 coding standards.'),
        ];

        $message = new SystemMessage($parts);

        $this->assertCount(4, $message->getParts());
        $this->assertEquals($parts, $message->getParts());
    }

    /**
     * Tests SystemMessage with empty parts.
     *
     * @return void
     */
    public function testWithEmptyParts(): void
    {
        $message = new SystemMessage([]);

        $this->assertEquals(MessageRoleEnum::system(), $message->getRole());
        $this->assertCount(0, $message->getParts());
    }

    /**
     * Tests SystemMessage inherits from Message.
     *
     * @return void
     */
    public function testInheritsFromMessage(): void
    {
        $message = new SystemMessage([]);

        $this->assertInstanceOf(\WordPress\AiClient\Messages\DTO\Message::class, $message);
    }

    /**
     * Tests SystemMessage with complex instructions.
     *
     * @return void
     */
    public function testWithComplexInstructions(): void
    {
        $parts = [
            new MessagePart('You are a specialized code review assistant with expertise in:'),
            new MessagePart('- Security best practices'),
            new MessagePart('- Performance optimization'),
            new MessagePart('- Code maintainability'),
            new MessagePart('When reviewing code, always check for:'),
            new MessagePart('1. SQL injection vulnerabilities'),
            new MessagePart('2. XSS vulnerabilities'),
            new MessagePart('3. Performance bottlenecks'),
        ];

        $message = new SystemMessage($parts);

        $this->assertCount(8, $message->getParts());

        // Verify each part
        $this->assertEquals(
            'You are a specialized code review assistant with expertise in:',
            $message->getParts()[0]->getText()
        );
        $this->assertEquals('- Security best practices', $message->getParts()[1]->getText());
        $this->assertEquals('- Performance optimization', $message->getParts()[2]->getText());
        $this->assertEquals('- Code maintainability', $message->getParts()[3]->getText());
        $this->assertEquals('When reviewing code, always check for:', $message->getParts()[4]->getText());
        $this->assertEquals('1. SQL injection vulnerabilities', $message->getParts()[5]->getText());
        $this->assertEquals('2. XSS vulnerabilities', $message->getParts()[6]->getText());
        $this->assertEquals('3. Performance bottlenecks', $message->getParts()[7]->getText());
    }

    /**
     * Tests JSON schema is inherited from parent.
     *
     * @return void
     */
    public function testJsonSchemaInheritance(): void
    {
        $schema = SystemMessage::getJsonSchema();
        $parentSchema = \WordPress\AiClient\Messages\DTO\Message::getJsonSchema();

        $this->assertEquals($parentSchema, $schema);
    }

    /**
     * Tests SystemMessage with single long instruction.
     *
     * @return void
     */
    public function testWithSingleLongInstruction(): void
    {
        $longInstruction = 'You are an AI assistant specialized in helping developers understand ' .
            'and work with PHP code. Always provide clear explanations, use proper ' .
            'terminology, and ensure your code examples follow PSR-12 standards. ' .
            'When explaining complex concepts, break them down into simpler parts ' .
            'and provide practical examples. Be patient and thorough in your responses.';

        $message = new SystemMessage([new MessagePart($longInstruction)]);

        $this->assertCount(1, $message->getParts());
        $this->assertEquals($longInstruction, $message->getParts()[0]->getText());
    }

    /**
     * Tests that parts are preserved in order.
     *
     * @return void
     */
    public function testPreservesPartOrder(): void
    {
        $parts = [
            new MessagePart('First instruction'),
            new MessagePart('Second instruction'),
            new MessagePart('Third instruction'),
            new MessagePart('Fourth instruction'),
        ];

        $message = new SystemMessage($parts);
        $retrievedParts = $message->getParts();

        $this->assertEquals('First instruction', $retrievedParts[0]->getText());
        $this->assertEquals('Second instruction', $retrievedParts[1]->getText());
        $this->assertEquals('Third instruction', $retrievedParts[2]->getText());
        $this->assertEquals('Fourth instruction', $retrievedParts[3]->getText());
    }

    /**
     * Tests array transformation.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $message = new SystemMessage([
            new MessagePart('You are a helpful assistant.'),
            new MessagePart('Always be respectful and accurate.')
        ]);

        $json = $this->assertToArrayReturnsArray($message);

        $this->assertArrayHasKeys($json, ['role', 'parts']);
        $this->assertEquals(MessageRoleEnum::system()->value, $json['role']);
        $this->assertCount(2, $json['parts']);
        $this->assertEquals('You are a helpful assistant.', $json['parts'][0]['text']);
        $this->assertEquals('Always be respectful and accurate.', $json['parts'][1]['text']);
    }

    /**
     * Tests fromJson method.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $json = [
            'role' => MessageRoleEnum::system()->value,
            'parts' => [
                ['type' => MessagePartTypeEnum::text()->value, 'text' => 'System instruction 1'],
                ['type' => MessagePartTypeEnum::text()->value, 'text' => 'System instruction 2']
            ]
        ];

        $message = SystemMessage::fromArray($json);

        $this->assertInstanceOf(SystemMessage::class, $message);
        $this->assertEquals(MessageRoleEnum::system(), $message->getRole());
        $this->assertCount(2, $message->getParts());
        $this->assertEquals('System instruction 1', $message->getParts()[0]->getText());
        $this->assertEquals('System instruction 2', $message->getParts()[1]->getText());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $this->assertArrayRoundTrip(
            new SystemMessage([
                new MessagePart('You are an expert in PHP.'),
                new MessagePart('Follow best practices.')
            ]),
            function ($original, $restored) {
                $this->assertEquals($original->getRole()->value, $restored->getRole()->value);
                $this->assertCount(count($original->getParts()), $restored->getParts());
                $this->assertEquals(
                    $original->getParts()[0]->getText(),
                    $restored->getParts()[0]->getText()
                );
                $this->assertEquals(
                    $original->getParts()[1]->getText(),
                    $restored->getParts()[1]->getText()
                );
            }
        );
    }

    /**
     * Tests SystemMessage implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $message = new SystemMessage([new MessagePart('test')]);
        $this->assertImplementsArrayTransformation($message);
    }
}

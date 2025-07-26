<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * @covers \WordPress\AiClient\Messages\DTO\MessagePart
 */
class MessagePartTest extends TestCase
{
    /**
     * Tests creating MessagePart with text content.
     *
     * @return void
     */
    public function testCreateWithTextContent(): void
    {
        $text = 'Hello, this is a text message.';
        $part = new MessagePart($text);
        
        $this->assertEquals(MessagePartTypeEnum::text(), $part->getType());
        $this->assertEquals($text, $part->getText());
        $this->assertNull($part->getFile());
        $this->assertNull($part->getFunctionCall());
        $this->assertNull($part->getFunctionResponse());
    }

    /**
     * Tests creating MessagePart with File content.
     *
     * @return void
     */
    public function testCreateWithFileContent(): void
    {
        $file = new File('https://example.com/image.jpg', 'image/jpeg');
        $part = new MessagePart($file);
        
        $this->assertEquals(MessagePartTypeEnum::file(), $part->getType());
        $this->assertNull($part->getText());
        $this->assertSame($file, $part->getFile());
        $this->assertNull($part->getFunctionCall());
        $this->assertNull($part->getFunctionResponse());
    }

    /**
     * Tests creating MessagePart with FunctionCall content.
     *
     * @return void
     */
    public function testCreateWithFunctionCallContent(): void
    {
        $functionCall = new FunctionCall('func_123', 'testFunction', ['param' => 'value']);
        $part = new MessagePart($functionCall);
        
        $this->assertEquals(MessagePartTypeEnum::functionCall(), $part->getType());
        $this->assertNull($part->getText());
        $this->assertNull($part->getFile());
        $this->assertSame($functionCall, $part->getFunctionCall());
        $this->assertNull($part->getFunctionResponse());
    }

    /**
     * Tests creating MessagePart with FunctionResponse content.
     *
     * @return void
     */
    public function testCreateWithFunctionResponseContent(): void
    {
        $functionResponse = new FunctionResponse('func_123', 'testFunction', ['result' => 'success']);
        $part = new MessagePart($functionResponse);
        
        $this->assertEquals(MessagePartTypeEnum::functionResponse(), $part->getType());
        $this->assertNull($part->getText());
        $this->assertNull($part->getFile());
        $this->assertNull($part->getFunctionCall());
        $this->assertSame($functionResponse, $part->getFunctionResponse());
    }

    /**
     * Tests creating MessagePart with empty string.
     *
     * @return void
     */
    public function testCreateWithEmptyString(): void
    {
        $part = new MessagePart('');
        
        $this->assertEquals(MessagePartTypeEnum::text(), $part->getType());
        $this->assertEquals('', $part->getText());
    }

    /**
     * Tests that unsupported content type throws exception.
     *
     * @dataProvider unsupportedContentProvider
     * @param mixed $content
     * @param string $expectedType
     * @return void
     */
    public function testUnsupportedContentThrowsException($content, string $expectedType): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Unsupported content type %s. Expected string, File, FunctionCall, or FunctionResponse.',
            $expectedType
        ));
        
        new MessagePart($content);
    }

    /**
     * Provides unsupported content types.
     *
     * @return array
     */
    public function unsupportedContentProvider(): array
    {
        return [
            'integer' => [123, 'integer'],
            'float' => [3.14, 'double'],
            'boolean' => [true, 'boolean'],
            'array' => [['key' => 'value'], 'array'],
            'null' => [null, 'NULL'],
            'stdClass' => [new \stdClass(), 'stdClass'],
        ];
    }

    /**
     * Tests JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = MessagePart::getJsonSchema();
        
        $this->assertIsArray($schema);
        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertCount(4, $schema['oneOf']); // text, file, function_call, function_response
        
        // Check text variant
        $textSchema = $schema['oneOf'][0];
        $this->assertEquals('object', $textSchema['type']);
        $this->assertEquals(MessagePartTypeEnum::text()->value, $textSchema['properties']['type']['const']);
        $this->assertArrayHasKey('text', $textSchema['properties']);
        $this->assertEquals(['type', 'text'], $textSchema['required']);
        
        // Check file variant
        $fileSchema = $schema['oneOf'][1];
        $this->assertEquals('object', $fileSchema['type']);
        $this->assertEquals(MessagePartTypeEnum::file()->value, $fileSchema['properties']['type']['const']);
        $this->assertArrayHasKey('file', $fileSchema['properties']);
        $this->assertEquals(['type', 'file'], $fileSchema['required']);
        
        // Check function_call variant
        $functionCallSchema = $schema['oneOf'][2];
        $this->assertEquals('object', $functionCallSchema['type']);
        $this->assertEquals(MessagePartTypeEnum::functionCall()->value, $functionCallSchema['properties']['type']['const']);
        $this->assertArrayHasKey('functionCall', $functionCallSchema['properties']);
        $this->assertEquals(['type', 'functionCall'], $functionCallSchema['required']);
        
        // Check function_response variant
        $functionResponseSchema = $schema['oneOf'][3];
        $this->assertEquals('object', $functionResponseSchema['type']);
        $this->assertEquals(MessagePartTypeEnum::functionResponse()->value, $functionResponseSchema['properties']['type']['const']);
        $this->assertArrayHasKey('functionResponse', $functionResponseSchema['properties']);
        $this->assertEquals(['type', 'functionResponse'], $functionResponseSchema['required']);
    }

    /**
     * Tests with different file types.
     *
     * @return void
     */
    public function testWithDifferentFileTypes(): void
    {
        // Remote file
        $remoteFile = new File('https://example.com/doc.pdf', 'application/pdf');
        $part1 = new MessagePart($remoteFile);
        $this->assertEquals('https://example.com/doc.pdf', $part1->getFile()->getUrl());
        
        // Inline file
        $inlineFile = new File('SGVsbG8gV29ybGQ=', 'text/plain');
        $part2 = new MessagePart($inlineFile);
        $this->assertEquals('SGVsbG8gV29ybGQ=', $part2->getFile()->getBase64Data());
    }

    /**
     * Tests with complex function call.
     *
     * @return void
     */
    public function testWithComplexFunctionCall(): void
    {
        $complexArgs = [
            'query' => 'SELECT * FROM users WHERE active = ?',
            'params' => [true],
            'options' => [
                'timeout' => 30,
                'retries' => 3,
                'cache' => false
            ]
        ];
        
        $functionCall = new FunctionCall('db_123', 'executeQuery', $complexArgs);
        $part = new MessagePart($functionCall);
        
        $retrievedCall = $part->getFunctionCall();
        $this->assertNotNull($retrievedCall);
        $this->assertEquals($complexArgs, $retrievedCall->getArgs());
    }

    /**
     * Tests with Unicode text.
     *
     * @return void
     */
    public function testWithUnicodeText(): void
    {
        $unicodeText = '你好世界 🌍 مرحبا بالعالم';
        $part = new MessagePart($unicodeText);
        
        $this->assertEquals($unicodeText, $part->getText());
    }
}
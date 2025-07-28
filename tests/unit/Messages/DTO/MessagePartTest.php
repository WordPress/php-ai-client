<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
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

    /**
     * Tests JSON serialization with text content.
     *
     * @return void
     */
    public function testJsonSerializeWithText(): void
    {
        $part = new MessagePart('Hello, world!');
        $json = $part->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertArrayHasKey('type', $json);
        $this->assertArrayHasKey('text', $json);
        $this->assertEquals(MessagePartTypeEnum::text()->value, $json['type']);
        $this->assertEquals('Hello, world!', $json['text']);
        
        // Ensure other fields are not present
        $this->assertArrayNotHasKey('file', $json);
        $this->assertArrayNotHasKey('functionCall', $json);
        $this->assertArrayNotHasKey('functionResponse', $json);
    }

    /**
     * Tests JSON serialization with file content.
     *
     * @return void
     */
    public function testJsonSerializeWithFile(): void
    {
        $file = new File('https://example.com/image.jpg', 'image/jpeg');
        $part = new MessagePart($file);
        $json = $part->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertArrayHasKey('type', $json);
        $this->assertArrayHasKey('file', $json);
        $this->assertEquals(MessagePartTypeEnum::file()->value, $json['type']);
        $this->assertIsArray($json['file']);
    }

    /**
     * Tests fromJson with text content.
     *
     * @return void
     */
    public function testFromJsonWithText(): void
    {
        $json = [
            'type' => MessagePartTypeEnum::text()->value,
            'text' => 'Test message'
        ];
        
        $part = MessagePart::fromJson($json);
        
        $this->assertEquals(MessagePartTypeEnum::text(), $part->getType());
        $this->assertEquals('Test message', $part->getText());
    }

    /**
     * Tests fromJson with file content.
     *
     * @return void
     */
    public function testFromJsonWithFile(): void
    {
        $json = [
            'type' => MessagePartTypeEnum::file()->value,
            'file' => [
                'fileType' => FileTypeEnum::remote()->value,
                'mimeType' => 'image/jpeg',
                'url' => 'https://example.com/image.jpg'
            ]
        ];
        
        $part = MessagePart::fromJson($json);
        
        $this->assertEquals(MessagePartTypeEnum::file(), $part->getType());
        $this->assertInstanceOf(File::class, $part->getFile());
        $this->assertEquals('https://example.com/image.jpg', $part->getFile()->getUrl());
    }

    /**
     * Tests round-trip JSON serialization with different content types.
     *
     * @return void
     */
    public function testJsonRoundTrip(): void
    {
        // Test with text
        $textPart = new MessagePart('Test text');
        $textJson = $textPart->jsonSerialize();
        $restoredText = MessagePart::fromJson($textJson);
        $this->assertEquals($textPart->getText(), $restoredText->getText());
        
        // Test with file
        $file = new File('https://example.com/doc.pdf', 'application/pdf');
        $filePart = new MessagePart($file);
        $fileJson = $filePart->jsonSerialize();
        $restoredFile = MessagePart::fromJson($fileJson);
        $this->assertEquals($file->getUrl(), $restoredFile->getFile()->getUrl());
        $this->assertEquals($file->getMimeType(), $restoredFile->getFile()->getMimeType());
        
        // Test with function call
        $functionCall = new FunctionCall('id_123', 'getData', ['key' => 'value']);
        $funcPart = new MessagePart($functionCall);
        $funcJson = $funcPart->jsonSerialize();
        $restoredFunc = MessagePart::fromJson($funcJson);
        $this->assertEquals($functionCall->getId(), $restoredFunc->getFunctionCall()->getId());
        $this->assertEquals($functionCall->getName(), $restoredFunc->getFunctionCall()->getName());
        $this->assertEquals($functionCall->getArgs(), $restoredFunc->getFunctionCall()->getArgs());
    }

    /**
     * Tests MessagePart implements WithJsonSerialization.
     *
     * @return void
     */
    public function testImplementsWithJsonSerialization(): void
    {
        $part = new MessagePart('test');
        
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithJsonSerialization::class,
            $part
        );
        $this->assertInstanceOf(
            \JsonSerializable::class,
            $part
        );
    }
}
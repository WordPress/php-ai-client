<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
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
        $this->assertEquals(MessagePartChannelEnum::content(), $part->getChannel());
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
        $this->assertEquals(MessagePartChannelEnum::content(), $part->getChannel());
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
        $this->assertEquals(MessagePartChannelEnum::content(), $part->getChannel());
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
        $this->assertEquals(MessagePartChannelEnum::content(), $part->getChannel());
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
        $this->assertEquals(MessagePartChannelEnum::content(), $part->getChannel());
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
        $this->expectException(InvalidArgumentException::class);
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
            'stdClass' => [new stdClass(), 'stdClass'],
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
        $this->assertEquals(
            MessagePartTypeEnum::text()->value,
            $textSchema['properties'][MessagePart::KEY_TYPE]['const']
        );
        $this->assertArrayHasKey(MessagePart::KEY_TEXT, $textSchema['properties']);
        $this->assertEquals([MessagePart::KEY_TYPE, MessagePart::KEY_TEXT], $textSchema['required']);

        // Check file variant
        $fileSchema = $schema['oneOf'][1];
        $this->assertEquals('object', $fileSchema['type']);
        $this->assertEquals(
            MessagePartTypeEnum::file()->value,
            $fileSchema['properties'][MessagePart::KEY_TYPE]['const']
        );
        $this->assertArrayHasKey(MessagePart::KEY_FILE, $fileSchema['properties']);
        $this->assertEquals([MessagePart::KEY_TYPE, MessagePart::KEY_FILE], $fileSchema['required']);

        // Check function_call variant
        $functionCallSchema = $schema['oneOf'][2];
        $this->assertEquals('object', $functionCallSchema['type']);
        $this->assertEquals(
            MessagePartTypeEnum::functionCall()->value,
            $functionCallSchema['properties'][MessagePart::KEY_TYPE]['const']
        );
        $this->assertArrayHasKey(MessagePart::KEY_FUNCTION_CALL, $functionCallSchema['properties']);
        $this->assertEquals([MessagePart::KEY_TYPE, MessagePart::KEY_FUNCTION_CALL], $functionCallSchema['required']);

        // Check function_response variant
        $functionResponseSchema = $schema['oneOf'][3];
        $this->assertEquals('object', $functionResponseSchema['type']);
        $this->assertEquals(
            MessagePartTypeEnum::functionResponse()->value,
            $functionResponseSchema['properties'][MessagePart::KEY_TYPE]['const']
        );
        $this->assertArrayHasKey(MessagePart::KEY_FUNCTION_RESPONSE, $functionResponseSchema['properties']);
        $this->assertEquals(
            [MessagePart::KEY_TYPE, MessagePart::KEY_FUNCTION_RESPONSE],
            $functionResponseSchema['required']
        );
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
     * Tests array transformation with text content.
     *
     * @return void
     */
    public function testToArrayWithText(): void
    {
        $part = new MessagePart('Hello, world!');
        $json = $part->toArray();

        $this->assertIsArray($json);
        $this->assertArrayHasKey(MessagePart::KEY_CHANNEL, $json);
        $this->assertArrayHasKey(MessagePart::KEY_TYPE, $json);
        $this->assertArrayHasKey(MessagePart::KEY_TEXT, $json);
        $this->assertEquals(MessagePartChannelEnum::content()->value, $json[MessagePart::KEY_CHANNEL]);
        $this->assertEquals(MessagePartTypeEnum::text()->value, $json[MessagePart::KEY_TYPE]);
        $this->assertEquals('Hello, world!', $json[MessagePart::KEY_TEXT]);

        // Ensure other fields are not present
        $this->assertArrayNotHasKey(MessagePart::KEY_FILE, $json);
        $this->assertArrayNotHasKey(MessagePart::KEY_FUNCTION_CALL, $json);
        $this->assertArrayNotHasKey(MessagePart::KEY_FUNCTION_RESPONSE, $json);
    }

    /**
     * Tests array transformation with file content.
     *
     * @return void
     */
    public function testToArrayWithFile(): void
    {
        $file = new File('https://example.com/image.jpg', 'image/jpeg');
        $part = new MessagePart($file);
        $json = $part->toArray();

        $this->assertIsArray($json);
        $this->assertArrayHasKey(MessagePart::KEY_CHANNEL, $json);
        $this->assertArrayHasKey(MessagePart::KEY_TYPE, $json);
        $this->assertArrayHasKey(MessagePart::KEY_FILE, $json);
        $this->assertEquals(MessagePartChannelEnum::content()->value, $json[MessagePart::KEY_CHANNEL]);
        $this->assertEquals(MessagePartTypeEnum::file()->value, $json[MessagePart::KEY_TYPE]);
        $this->assertIsArray($json[MessagePart::KEY_FILE]);
    }

    /**
     * Tests fromJson with text content.
     *
     * @return void
     */
    public function testFromArrayWithText(): void
    {
        $json = [
            MessagePart::KEY_CHANNEL => MessagePartChannelEnum::thought()->value,
            MessagePart::KEY_TYPE => MessagePartTypeEnum::text()->value,
            MessagePart::KEY_TEXT => 'Test message'
        ];

        $part = MessagePart::fromArray($json);

        $this->assertEquals(MessagePartTypeEnum::text(), $part->getType());
        $this->assertEquals(MessagePartChannelEnum::thought(), $part->getChannel());
        $this->assertEquals('Test message', $part->getText());
    }

    /**
     * Tests fromJson with file content.
     *
     * @return void
     */
    public function testFromArrayWithFile(): void
    {
        $json = [
            MessagePart::KEY_CHANNEL => MessagePartChannelEnum::content()->value,
            MessagePart::KEY_TYPE => MessagePartTypeEnum::file()->value,
            MessagePart::KEY_FILE => [
                File::KEY_FILE_TYPE => FileTypeEnum::remote()->value,
                File::KEY_MIME_TYPE => 'image/jpeg',
                File::KEY_URL => 'https://example.com/image.jpg'
            ]
        ];

        $part = MessagePart::fromArray($json);

        $this->assertEquals(MessagePartTypeEnum::file(), $part->getType());
        $this->assertEquals(MessagePartChannelEnum::content(), $part->getChannel());
        $this->assertInstanceOf(File::class, $part->getFile());
        $this->assertEquals('https://example.com/image.jpg', $part->getFile()->getUrl());
    }

    /**
     * Tests round-trip array transformation with different content types.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        // Test with text
        $textPart = new MessagePart('Test text', MessagePartChannelEnum::thought());
        $textJson = $textPart->toArray();
        $restoredText = MessagePart::fromArray($textJson);
        $this->assertEquals($textPart->getText(), $restoredText->getText());
        $this->assertEquals($textPart->getChannel(), $restoredText->getChannel());

        // Test with file
        $file = new File('https://example.com/doc.pdf', 'application/pdf');
        $filePart = new MessagePart($file);
        $fileJson = $filePart->toArray();
        $restoredFile = MessagePart::fromArray($fileJson);
        $this->assertEquals($file->getUrl(), $restoredFile->getFile()->getUrl());
        $this->assertEquals($file->getMimeType(), $restoredFile->getFile()->getMimeType());
        $this->assertEquals($filePart->getChannel(), $restoredFile->getChannel());

        // Test with function call
        $functionCall = new FunctionCall('id_123', 'getData', ['key' => 'value']);
        $funcPart = new MessagePart($functionCall, MessagePartChannelEnum::thought());
        $funcJson = $funcPart->toArray();
        $restoredFunc = MessagePart::fromArray($funcJson);
        $this->assertEquals($functionCall->getId(), $restoredFunc->getFunctionCall()->getId());
        $this->assertEquals($functionCall->getName(), $restoredFunc->getFunctionCall()->getName());
        $this->assertEquals($functionCall->getArgs(), $restoredFunc->getFunctionCall()->getArgs());
        $this->assertEquals($funcPart->getChannel(), $restoredFunc->getChannel());
    }

    /**
     * Tests MessagePart implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $part = new MessagePart('test');

        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface::class,
            $part
        );
    }

    /**
     * Tests creating MessagePart with different channels.
     *
     * @return void
     */
    public function testCreateWithDifferentChannels(): void
    {
        // Default channel is CONTENT
        $part1 = new MessagePart('Some content');
        $this->assertEquals(MessagePartChannelEnum::content(), $part1->getChannel());
        $this->assertTrue($part1->getChannel()->isContent());
        $this->assertFalse($part1->getChannel()->isThought());

        // Explicitly set CONTENT channel
        $part2 = new MessagePart('Some content', MessagePartChannelEnum::content());
        $this->assertEquals(MessagePartChannelEnum::content(), $part2->getChannel());
        $this->assertTrue($part2->getChannel()->isContent());
        $this->assertFalse($part2->getChannel()->isThought());

        // Explicitly set THOUGHT channel
        $part3 = new MessagePart('Some thought', MessagePartChannelEnum::thought());
        $this->assertEquals(MessagePartChannelEnum::thought(), $part3->getChannel());
        $this->assertFalse($part3->getChannel()->isContent());
        $this->assertTrue($part3->getChannel()->isThought());
    }

    /**
     * Tests fromArray with an invalid channel value.
     *
     * @return void
     */
    public function testFromArrayWithInvalidChannel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid channel value: invalid_channel');

        $json = [
            MessagePart::KEY_CHANNEL => 'invalid_channel',
            MessagePart::KEY_TYPE => MessagePartTypeEnum::text()->value,
            MessagePart::KEY_TEXT => 'Test message'
        ];

        MessagePart::fromArray($json);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Files\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Files\ValueObjects\MimeType;

/**
 * @covers \WordPress\AiClient\Files\DTO\File
 */
class FileTest extends TestCase
{
    /**
     * Tests creating a File from a URL.
     *
     * @return void
     */
    public function testCreateFromUrl(): void
    {
        $url = 'https://example.com/image.jpg';
        $mimeType = 'image/jpeg';

        $file = new File($url, $mimeType);

        $this->assertEquals(FileTypeEnum::remote(), $file->getFileType());
        $this->assertEquals($url, $file->getUrl());
        $this->assertNull($file->getBase64Data());
        $this->assertNull($file->getDataUri());
        $this->assertEquals($mimeType, $file->getMimeType());
        $this->assertTrue($file->isImage());
    }

    /**
     * Tests creating a File from a URL with inferred MIME type.
     *
     * @return void
     */
    public function testCreateFromUrlWithInferredMimeType(): void
    {
        $url = 'https://example.com/document.pdf';

        $file = new File($url);

        $this->assertEquals(FileTypeEnum::remote(), $file->getFileType());
        $this->assertEquals($url, $file->getUrl());
        $this->assertEquals('application/pdf', $file->getMimeType());
        $this->assertFalse($file->isText());
    }

    /**
     * Tests creating a File from a data URI.
     *
     * @return void
     */
    public function testCreateFromDataUri(): void
    {
        $base64Data = 'SGVsbG8gV29ybGQ=';
        $dataUri = 'data:text/plain;base64,' . $base64Data;

        $file = new File($dataUri);

        $this->assertEquals(FileTypeEnum::inline(), $file->getFileType());
        $this->assertNull($file->getUrl());
        $this->assertEquals($base64Data, $file->getBase64Data());
        $this->assertEquals($dataUri, $file->getDataUri());
        $this->assertEquals('text/plain', $file->getMimeType());
        $this->assertTrue($file->isText());
    }

    /**
     * Tests creating a File from a data URI with provided MIME type override.
     *
     * @return void
     */
    public function testCreateFromDataUriWithMimeTypeOverride(): void
    {
        $base64Data = 'SGVsbG8gV29ybGQ=';
        $dataUri = 'data:text/plain;base64,' . $base64Data;
        $overrideMimeType = 'text/html';

        $file = new File($dataUri, $overrideMimeType);

        $this->assertEquals(FileTypeEnum::inline(), $file->getFileType());
        $this->assertEquals($base64Data, $file->getBase64Data());
        $this->assertEquals($overrideMimeType, $file->getMimeType());
        $this->assertEquals('data:text/html;base64,' . $base64Data, $file->getDataUri());
    }

    /**
     * Tests creating a File from plain base64 data.
     *
     * @return void
     */
    public function testCreateFromPlainBase64(): void
    {
        $base64Data = 'SGVsbG8gV29ybGQ=';
        $mimeType = 'text/plain';

        $file = new File($base64Data, $mimeType);

        $this->assertEquals(FileTypeEnum::inline(), $file->getFileType());
        $this->assertNull($file->getUrl());
        $this->assertEquals($base64Data, $file->getBase64Data());
        $this->assertEquals('data:text/plain;base64,' . $base64Data, $file->getDataUri());
        $this->assertEquals($mimeType, $file->getMimeType());
    }

    /**
     * Tests that plain base64 without MIME type throws exception.
     *
     * @return void
     */
    public function testPlainBase64WithoutMimeTypeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'MIME type is required when providing plain base64 data without data URI format.'
        );

        new File('SGVsbG8gV29ybGQ=');
    }

    /**
     * Tests creating a File from a local file path.
     *
     * @return void
     */
    public function testCreateFromLocalFile(): void
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'Hello World');

        try {
            $file = new File($tempFile, 'text/plain');

            $this->assertEquals(FileTypeEnum::inline(), $file->getFileType());
            $this->assertNull($file->getUrl());
            $this->assertEquals(base64_encode('Hello World'), $file->getBase64Data());
            $this->assertEquals('text/plain', $file->getMimeType());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Tests that invalid file format throws exception.
     *
     * @return void
     */
    public function testInvalidFileFormatThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file provided. Expected URL, base64 data, or valid local file path.');

        new File('not-a-valid-file-or-url', 'text/plain');
    }

    /**
     * Tests that non-existent local file throws exception.
     *
     * @return void
     */
    public function testNonExistentLocalFileThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file provided. Expected URL, base64 data, or valid local file path.');

        new File('/path/to/non/existent/file.txt', 'text/plain');
    }

    /**
     * Tests that passing a directory throws exception.
     *
     * @return void
     */
    public function testDirectoryThrowsException(): void
    {
        // Create a directory instead of a file
        $tempDir = sys_get_temp_dir() . '/test_dir_' . uniqid();
        mkdir($tempDir);

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(
                'Invalid file provided. Expected URL, base64 data, or valid local file path.'
            );

            new File($tempDir, 'text/plain');
        } finally {
            rmdir($tempDir);
        }
    }

    /**
     * Tests MIME type methods.
     *
     * @return void
     */
    public function testMimeTypeMethods(): void
    {
        $file = new File('https://example.com/video.mp4');

        $this->assertEquals('video/mp4', $file->getMimeType());
        $this->assertInstanceOf(MimeType::class, $file->getMimeTypeObject());
        $this->assertTrue($file->isVideo());
        $this->assertFalse($file->isImage());
        $this->assertFalse($file->isAudio());
        $this->assertFalse($file->isText());
        $this->assertTrue($file->isMimeType('video'));
        $this->assertFalse($file->isMimeType('image'));
        $this->assertFalse($file->isMimeType('audio'));
        $this->assertFalse($file->isMimeType('text'));
    }

    /**
     * Tests JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = File::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertCount(2, $schema['oneOf']);

        // Check remote file schema
        $remoteSchema = $schema['oneOf'][0];
        $this->assertArrayHasKey('properties', $remoteSchema);
        $this->assertArrayHasKey(File::KEY_FILE_TYPE, $remoteSchema['properties']);
        $this->assertArrayHasKey(File::KEY_MIME_TYPE, $remoteSchema['properties']);
        $this->assertArrayHasKey(File::KEY_URL, $remoteSchema['properties']);
        $this->assertEquals(
            [File::KEY_FILE_TYPE, File::KEY_MIME_TYPE, File::KEY_URL],
            $remoteSchema['required']
        );

        // Check inline file schema
        $inlineSchema = $schema['oneOf'][1];
        $this->assertArrayHasKey('properties', $inlineSchema);
        $this->assertArrayHasKey(File::KEY_FILE_TYPE, $inlineSchema['properties']);
        $this->assertArrayHasKey(File::KEY_MIME_TYPE, $inlineSchema['properties']);
        $this->assertArrayHasKey(File::KEY_BASE64_DATA, $inlineSchema['properties']);
        $this->assertEquals(
            [File::KEY_FILE_TYPE, File::KEY_MIME_TYPE, File::KEY_BASE64_DATA],
            $inlineSchema['required']
        );
    }

    /**
     * Tests data URI without MIME type defaults correctly.
     *
     * @return void
     */
    public function testDataUriWithoutMimeType(): void
    {
        $base64Data = 'SGVsbG8gV29ybGQ=';
        $dataUri = 'data:;base64,' . $base64Data;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to determine MIME type. Please provide it explicitly.');

        new File($dataUri);
    }

    /**
     * Tests URL with unknown extension.
     *
     * @return void
     */
    public function testUrlWithUnknownExtension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to determine MIME type. Please provide it explicitly.');

        new File('https://example.com/file.unknown');
    }

    /**
     * Tests array transformation for remote file.
     *
     * @return void
     */
    public function testToArrayRemoteFile(): void
    {
        $file = new File('https://example.com/image.jpg', 'image/jpeg');
        $json = $file->toArray();

        $this->assertIsArray($json);
        $this->assertEquals(FileTypeEnum::remote()->value, $json[File::KEY_FILE_TYPE]);
        $this->assertEquals('image/jpeg', $json[File::KEY_MIME_TYPE]);
        $this->assertEquals('https://example.com/image.jpg', $json[File::KEY_URL]);
        $this->assertArrayNotHasKey(File::KEY_BASE64_DATA, $json);
    }

    /**
     * Tests array transformation for inline file.
     *
     * @return void
     */
    public function testToArrayInlineFile(): void
    {
        $base64Data = 'SGVsbG8gV29ybGQ=';
        $dataUri = 'data:text/plain;base64,' . $base64Data;
        $file = new File($dataUri);
        $json = $file->toArray();

        $this->assertIsArray($json);
        $this->assertEquals(FileTypeEnum::inline()->value, $json[File::KEY_FILE_TYPE]);
        $this->assertEquals('text/plain', $json[File::KEY_MIME_TYPE]);
        $this->assertEquals($base64Data, $json[File::KEY_BASE64_DATA]);
        $this->assertArrayNotHasKey(File::KEY_URL, $json);
    }

    /**
     * Tests fromJson for remote file.
     *
     * @return void
     */
    public function testFromArrayRemoteFile(): void
    {
        $json = [
            File::KEY_FILE_TYPE => FileTypeEnum::remote()->value,
            File::KEY_MIME_TYPE => 'image/png',
            File::KEY_URL => 'https://example.com/test.png'
        ];

        $file = File::fromArray($json);

        $this->assertInstanceOf(File::class, $file);
        $this->assertTrue($file->getFileType()->isRemote());
        $this->assertEquals('image/png', $file->getMimeType());
        $this->assertEquals('https://example.com/test.png', $file->getUrl());
        $this->assertNull($file->getBase64Data());
    }

    /**
     * Tests fromJson for inline file.
     *
     * @return void
     */
    public function testFromArrayInlineFile(): void
    {
        $base64Data = 'SGVsbG8gV29ybGQ=';
        $json = [
            File::KEY_FILE_TYPE => FileTypeEnum::inline()->value,
            File::KEY_MIME_TYPE => 'text/plain',
            File::KEY_BASE64_DATA => $base64Data
        ];

        $file = File::fromArray($json);

        $this->assertInstanceOf(File::class, $file);
        $this->assertTrue($file->getFileType()->isInline());
        $this->assertEquals('text/plain', $file->getMimeType());
        $this->assertEquals($base64Data, $file->getBase64Data());
        $this->assertNull($file->getUrl());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        // Test remote file
        $remoteFile = new File('https://example.com/doc.pdf', 'application/pdf');
        $remoteJson = $remoteFile->toArray();
        $restoredRemote = File::fromArray($remoteJson);

        $this->assertEquals($remoteFile->getFileType()->value, $restoredRemote->getFileType()->value);
        $this->assertEquals($remoteFile->getMimeType(), $restoredRemote->getMimeType());
        $this->assertEquals($remoteFile->getUrl(), $restoredRemote->getUrl());

        // Test inline file
        $dataUri = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        $inlineFile = new File($dataUri);
        $inlineJson = $inlineFile->toArray();
        $restoredInline = File::fromArray($inlineJson);

        $this->assertEquals($inlineFile->getFileType()->value, $restoredInline->getFileType()->value);
        $this->assertEquals($inlineFile->getMimeType(), $restoredInline->getMimeType());
        $this->assertEquals($inlineFile->getBase64Data(), $restoredInline->getBase64Data());
    }

    /**
     * Tests File implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $file = new File('https://example.com/test.jpg');

        $this->assertInstanceOf(
            WithArrayTransformationInterface::class,
            $file
        );
    }

    /**
     * Tests isImage passthrough method.
     *
     * @return void
     */
    public function testIsImage(): void
    {
        $imageFile = new File('https://example.com/test.jpg');
        $this->assertTrue($imageFile->isImage());

        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $pngFile = new File('data:image/png;base64,' . $pngBase64);
        $this->assertTrue($pngFile->isImage());

        $textFile = new File('https://example.com/test.txt', 'text/plain');
        $this->assertFalse($textFile->isImage());
    }

    /**
     * Tests isVideo passthrough method.
     *
     * @return void
     */
    public function testIsVideo(): void
    {
        $videoFile = new File('https://example.com/test.mp4');
        $this->assertTrue($videoFile->isVideo());

        $aviFile = new File('https://example.com/test.avi');
        $this->assertTrue($aviFile->isVideo());

        $imageFile = new File('https://example.com/test.jpg');
        $this->assertFalse($imageFile->isVideo());
    }

    /**
     * Tests isAudio passthrough method.
     *
     * @return void
     */
    public function testIsAudio(): void
    {
        $audioFile = new File('https://example.com/test.mp3');
        $this->assertTrue($audioFile->isAudio());

        $wavFile = new File('https://example.com/test.wav');
        $this->assertTrue($wavFile->isAudio());

        $imageFile = new File('https://example.com/test.jpg');
        $this->assertFalse($imageFile->isAudio());
    }

    /**
     * Tests isText passthrough method.
     *
     * @return void
     */
    public function testIsText(): void
    {
        $textFile = new File('https://example.com/test.txt');
        $this->assertTrue($textFile->isText());

        $csvFile = new File('https://example.com/test.csv');
        $this->assertTrue($csvFile->isText());

        $htmlFile = new File('https://example.com/test.html');
        $this->assertTrue($htmlFile->isText());

        $imageFile = new File('https://example.com/test.jpg');
        $this->assertFalse($imageFile->isText());
    }

    /**
     * Tests isDocument passthrough method.
     *
     * @return void
     */
    public function testIsDocument(): void
    {
        $pdfFile = new File('https://example.com/test.pdf');
        $this->assertTrue($pdfFile->isDocument());

        $docFile = new File('https://example.com/test.doc');
        $this->assertTrue($docFile->isDocument());

        $docxFile = new File('https://example.com/test.docx');
        $this->assertTrue($docxFile->isDocument());

        $imageFile = new File('https://example.com/test.jpg');
        $this->assertFalse($imageFile->isDocument());

        $audioFile = new File('https://example.com/test.mp3');
        $this->assertFalse($audioFile->isDocument());
    }

    /**
     * Tests isMimeType passthrough method.
     *
     * @return void
     */
    public function testIsMimeType(): void
    {
        $imageFile = new File('https://example.com/test.jpg');
        $this->assertTrue($imageFile->isMimeType('image'));
        $this->assertFalse($imageFile->isMimeType('video'));

        $videoFile = new File('https://example.com/test.mp4');
        $this->assertTrue($videoFile->isMimeType('video'));
        $this->assertFalse($videoFile->isMimeType('audio'));

        $textFile = new File('https://example.com/test.txt');
        $this->assertTrue($textFile->isMimeType('text'));
        $this->assertFalse($textFile->isMimeType('image'));
    }

    /**
     * Tests isInline method for inline files.
     *
     * @return void
     */
    public function testIsInlineForInlineFiles(): void
    {
        // Test with base64 data
        $base64File = new File('SGVsbG8gV29ybGQ=', 'text/plain');
        $this->assertTrue($base64File->isInline());
        $this->assertFalse($base64File->isRemote());

        // Test with data URI
        $dataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJA'
            . 'AAADUJEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $dataUriFile = new File($dataUri);
        $this->assertTrue($dataUriFile->isInline());
        $this->assertFalse($dataUriFile->isRemote());

        // Test with local file (becomes inline)
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');
        try {
            $localFile = new File($tempFile, 'text/plain');
            $this->assertTrue($localFile->isInline());
            $this->assertFalse($localFile->isRemote());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Tests isRemote method for remote files.
     *
     * @return void
     */
    public function testIsRemoteForRemoteFiles(): void
    {
        // Test with URL
        $urlFile = new File('https://example.com/image.jpg', 'image/jpeg');
        $this->assertTrue($urlFile->isRemote());
        $this->assertFalse($urlFile->isInline());

        // Test with URL without explicit MIME type
        $urlFileNoMime = new File('https://example.com/document.pdf');
        $this->assertTrue($urlFileNoMime->isRemote());
        $this->assertFalse($urlFileNoMime->isInline());
    }
}

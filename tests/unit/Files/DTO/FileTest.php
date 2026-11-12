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
     * Tests creating a File from a local file path via the explicit factory.
     *
     * @return void
     */
    public function testFromLocalPathHappyPath(): void
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'Hello World');

        try {
            $file = File::fromLocalPath($tempFile, 'text/plain');

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
        $this->expectExceptionMessage('Invalid file provided.');

        new File('not-a-valid-file-or-url', 'text/plain');
    }

    /**
     * Tests that a non-existent local file path is rejected by the constructor.
     *
     * @return void
     */
    public function testNonExistentLocalFileThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file provided.');

        new File('/path/to/non/existent/file.txt', 'text/plain');
    }

    /**
     * Tests that passing a directory to the constructor throws exception.
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
            $this->expectExceptionMessage('Invalid file provided.');

            new File($tempDir, 'text/plain');
        } finally {
            rmdir($tempDir);
        }
    }

    /**
     * Tests that the constructor still reads an existing local file (deprecated) and emits a deprecation.
     *
     * @return void
     */
    public function testConstructorLocalPathIsDeprecatedButStillSupported(): void
    {
        $tempDir = sys_get_temp_dir() . '/testdir' . uniqid();
        mkdir($tempDir);
        $path = $tempDir . '/note.txt';
        file_put_contents($path, 'Hello World');

        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            if ($errno === E_USER_DEPRECATED) {
                $deprecations[] = $errstr;
                return true;
            }
            return false;
        });

        try {
            $file = new File($path);
            $this->assertTrue($file->isInline());
            $this->assertEquals(base64_encode('Hello World'), $file->getBase64Data());
            $this->assertEquals('text/plain', $file->getMimeType());
            $this->assertCount(1, $deprecations);
            $this->assertStringContainsString('File::fromLocalPath()', $deprecations[0]);

            // An extension-less path (e.g. from tempnam()) must still be read, not treated as base64.
            $plainPath = $tempDir . '/phpAbc1';
            file_put_contents($plainPath, 'Hello World');
            $plainFile = new File($plainPath, 'image/png');
            $this->assertEquals(base64_encode('Hello World'), $plainFile->getBase64Data());
            $this->assertCount(2, $deprecations);
            unlink($plainPath);
        } finally {
            restore_error_handler();
            unlink($path);
            rmdir($tempDir);
        }
    }

    /**
     * Tests that the constructor rejects URL schemes other than http(s) before anything else.
     *
     * @dataProvider nonHttpSchemeProvider
     *
     * @param string $input The input string.
     * @return void
     */
    public function testConstructorRejectsNonHttpSchemes(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only http:// and https:// URLs are supported.');

        new File($input, 'text/plain');
    }

    /**
     * @return array<string, array{string}>
     */
    public function nonHttpSchemeProvider(): array
    {
        return [
            'file' => ['file:///etc/passwd'],
            'ftp' => ['ftp://127.0.0.1:1/x'],
            'phar' => ['phar://archive.phar/x.txt'],
            'php filter' => ['php://filter/resource=/etc/passwd'],
            'glob' => ['glob://*'],
        ];
    }

    /**
     * Tests that empty payloads are supported consistently (constructor, data URI, local file, round trip).
     *
     * @return void
     */
    public function testEmptyPayloadIsSupportedConsistently(): void
    {
        $empty = new File('', 'text/plain');
        $this->assertTrue($empty->isInline());
        $this->assertSame('', $empty->getBase64Data());

        $emptyDataUri = new File('data:text/plain;base64,');
        $this->assertSame('', $emptyDataUri->getBase64Data());
        $restored = File::fromArray($emptyDataUri->toArray());
        $this->assertSame('', $restored->getBase64Data());
        $this->assertSame('text/plain', $restored->getMimeType());

        $this->assertSame('', File::fromBase64('', 'text/plain')->getBase64Data());

        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        try {
            $this->assertSame('', File::fromLocalPath($tempFile, 'text/plain')->getBase64Data());
        } finally {
            unlink($tempFile);
        }
    }

    /**
     * Tests that an empty string without a MIME type still requires one.
     *
     * @return void
     */
    public function testEmptyStringWithoutMimeTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MIME type is required');

        new File('');
    }

    /**
     * Tests File::fromUrl() happy path and MIME inference.
     *
     * @return void
     */
    public function testFromUrl(): void
    {
        $file = File::fromUrl('https://example.com/image.jpg', 'image/png');
        $this->assertTrue($file->isRemote());
        $this->assertEquals('https://example.com/image.jpg', $file->getUrl());
        $this->assertEquals('image/png', $file->getMimeType());

        $inferred = File::fromUrl('http://example.com/doc.pdf?x=1#frag');
        $this->assertEquals('application/pdf', $inferred->getMimeType());
    }

    /**
     * Tests File::fromUrl() rejects anything that is not an http(s) URL.
     *
     * @dataProvider invalidUrlProvider
     *
     * @param string $input The input string.
     * @return void
     */
    public function testFromUrlRejectsInvalidInput(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL. Only http:// and https:// URLs are supported.');

        File::fromUrl($input, 'text/plain');
    }

    /**
     * @return array<string, array{string}>
     */
    public function invalidUrlProvider(): array
    {
        return [
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://example.com/x'],
            'plain path' => ['/etc/passwd'],
            'relative path' => ['composer.json'],
            'javascript' => ['javascript:alert(1)'],
            'empty' => [''],
        ];
    }

    /**
     * Tests File::fromDataUri() happy path and override.
     *
     * @return void
     */
    public function testFromDataUri(): void
    {
        $base64Data = 'SGVsbG8gV29ybGQ=';
        $file = File::fromDataUri('data:text/plain;base64,' . $base64Data);
        $this->assertTrue($file->isInline());
        $this->assertEquals($base64Data, $file->getBase64Data());
        $this->assertEquals('text/plain', $file->getMimeType());

        $override = File::fromDataUri('data:text/plain;base64,' . $base64Data, 'text/html');
        $this->assertEquals('text/html', $override->getMimeType());
    }

    /**
     * Tests File::fromDataUri() rejects invalid input.
     *
     * @dataProvider invalidDataUriProvider
     *
     * @param string $input The input string.
     * @return void
     */
    public function testFromDataUriRejectsInvalidInput(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data URI.');

        File::fromDataUri($input);
    }

    /**
     * @return array<string, array{string}>
     */
    public function invalidDataUriProvider(): array
    {
        return [
            'raw base64' => ['SGVsbG8gV29ybGQ='],
            'trailing newline' => ["data:text/plain;base64,SGVsbG8gV29ybGQ=\n"],
            'local path' => ['/etc/passwd'],
            'url' => ['https://example.com/x.png'],
        ];
    }

    /**
     * Tests File::fromDataUri() without MIME type throws.
     *
     * @return void
     */
    public function testFromDataUriWithoutMimeTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to determine MIME type.');

        File::fromDataUri('data:;base64,SGVsbG8gV29ybGQ=');
    }

    /**
     * Tests File::fromBase64() happy path, including JPEG data starting with a slash.
     *
     * @return void
     */
    public function testFromBase64(): void
    {
        $file = File::fromBase64('SGVsbG8gV29ybGQ=', 'text/plain');
        $this->assertTrue($file->isInline());
        $this->assertEquals('SGVsbG8gV29ybGQ=', $file->getBase64Data());
        $this->assertEquals('text/plain', $file->getMimeType());

        $jpeg = File::fromBase64('/9j/4AAQSkZJRg==', 'image/jpeg');
        $this->assertEquals('/9j/4AAQSkZJRg==', $jpeg->getBase64Data());
    }

    /**
     * Tests File::fromBase64() rejects invalid input.
     *
     * @dataProvider invalidBase64Provider
     *
     * @param string $input The input string.
     * @param string $expectedMessage The expected exception message fragment.
     * @return void
     */
    public function testFromBase64RejectsInvalidInput(string $input, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        File::fromBase64($input, 'text/plain');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function invalidBase64Provider(): array
    {
        return [
            'data uri' => ['data:text/plain;base64,SGVsbG8=', 'use File::fromDataUri()'],
            'invalid chars' => ['not base64!', 'Invalid base64 data provided.'],
            'path with dot' => ['/etc/hosts.txt', 'Invalid base64 data provided.'],
            'trailing newline' => ["SGVsbG8=\n", 'Invalid base64 data provided.'],
        ];
    }

    /**
     * Tests File::fromBase64() rejects an invalid MIME type.
     *
     * @return void
     */
    public function testFromBase64RejectsInvalidMimeType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid MIME type: not a mime');

        File::fromBase64('SGVsbG8=', 'not a mime');
    }

    /**
     * Tests File::fromLocalPath() infers the MIME type from the extension.
     *
     * @return void
     */
    public function testFromLocalPathInfersMimeTypeFromExtension(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_dir_' . uniqid();
        mkdir($tempDir);
        $path = $tempDir . '/note.txt';
        file_put_contents($path, 'note');

        try {
            $file = File::fromLocalPath($path);
            $this->assertEquals('text/plain', $file->getMimeType());
            $this->assertEquals(base64_encode('note'), $file->getBase64Data());
        } finally {
            unlink($path);
            rmdir($tempDir);
        }
    }

    /**
     * Tests File::fromLocalPath() error cases that are checked before any filesystem access.
     *
     * @dataProvider localPathRejectedProvider
     *
     * @param string $input The input string.
     * @param string $expectedMessage The expected exception message fragment.
     * @return void
     */
    public function testFromLocalPathRejectsInput(string $input, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        File::fromLocalPath($input, 'text/plain');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function localPathRejectedProvider(): array
    {
        return [
            'empty' => ['', 'Local file path must not be empty.'],
            'file wrapper' => ['file:///etc/passwd', 'Stream wrappers are not supported'],
            'ftp wrapper' => ['ftp://127.0.0.1:1/x', 'Stream wrappers are not supported'],
            'phar wrapper' => ['phar://archive.phar/x', 'Stream wrappers are not supported'],
            'missing file' => ['/path/to/non/existent/file.txt', 'Local file not found or not a regular file'],
        ];
    }

    /**
     * Tests File::fromLocalPath() rejects directories and unknown extensions without MIME type.
     *
     * @return void
     */
    public function testFromLocalPathRejectsDirectoryAndUnknownExtension(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_dir_' . uniqid();
        mkdir($tempDir);
        $unknown = $tempDir . '/data.unknownext';
        file_put_contents($unknown, 'x');

        try {
            try {
                File::fromLocalPath($tempDir, 'text/plain');
                $this->fail('Expected InvalidArgumentException for directory');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('not a regular file', $e->getMessage());
            }

            try {
                File::fromLocalPath($unknown);
                $this->fail('Expected InvalidArgumentException for unknown extension');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Unable to determine MIME type', $e->getMessage());
            }
        } finally {
            unlink($unknown);
            rmdir($tempDir);
        }
    }

    /**
     * Tests fromArray() rejects local paths and stream wrappers in url and base64Data.
     *
     * @return void
     */
    public function testFromArrayRejectsLocalPaths(): void
    {
        $tempDir = sys_get_temp_dir() . '/testdir' . uniqid();
        mkdir($tempDir);
        $secretFile = $tempDir . '/secret.txt';
        file_put_contents($secretFile, 'Hello World');
        $plainPath = $tempDir . '/secretfile';
        file_put_contents($plainPath, 'Hello World');

        $cases = [
            'path in url' => [
                File::KEY_FILE_TYPE => FileTypeEnum::remote()->value,
                File::KEY_MIME_TYPE => 'text/plain',
                File::KEY_URL => $secretFile,
            ],
            'file wrapper in url' => [
                File::KEY_FILE_TYPE => FileTypeEnum::remote()->value,
                File::KEY_MIME_TYPE => 'text/plain',
                File::KEY_URL => 'file://' . $secretFile,
            ],
            'ftp wrapper in url' => [
                File::KEY_FILE_TYPE => FileTypeEnum::remote()->value,
                File::KEY_MIME_TYPE => 'text/plain',
                File::KEY_URL => 'ftp://127.0.0.1:1/x',
            ],
            'path in base64Data' => [
                File::KEY_FILE_TYPE => FileTypeEnum::inline()->value,
                File::KEY_MIME_TYPE => 'text/plain',
                File::KEY_BASE64_DATA => $secretFile,
            ],
            'path in base64Data with inline type but url key' => [
                File::KEY_FILE_TYPE => FileTypeEnum::inline()->value,
                File::KEY_MIME_TYPE => 'text/plain',
                File::KEY_URL => $secretFile,
            ],
        ];

        try {
            foreach ($cases as $label => $array) {
                try {
                    File::fromArray($array);
                    $this->fail("Expected InvalidArgumentException for case: {$label}");
                } catch (InvalidArgumentException $e) {
                    $this->assertFalse(File::isArrayShape($array), "isArrayShape should be false for: {$label}");
                }
            }

            // An extension-less path passes the base64 pattern but is never read.
            $file = File::fromArray([
                File::KEY_FILE_TYPE => FileTypeEnum::inline()->value,
                File::KEY_MIME_TYPE => 'text/plain',
                File::KEY_BASE64_DATA => $plainPath,
            ]);
            $this->assertSame($plainPath, $file->getBase64Data());
        } finally {
            unlink($plainPath);
            unlink($secretFile);
            rmdir($tempDir);
        }
    }

    /**
     * Tests fromArray() structural validation.
     *
     * @dataProvider invalidArrayShapeProvider
     *
     * @param array<string, mixed> $array The array.
     * @param string $expectedMessage The expected exception message fragment.
     * @return void
     */
    public function testFromArrayRejectsInvalidShape(array $array, string $expectedMessage): void
    {
        $this->assertFalse(File::isArrayShape($array));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        File::fromArray($array);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public function invalidArrayShapeProvider(): array
    {
        return [
            'missing fileType' => [
                [File::KEY_MIME_TYPE => 'text/plain', File::KEY_URL => 'https://example.com/x.txt'],
                'missing required keys: fileType',
            ],
            'unknown fileType' => [
                [File::KEY_FILE_TYPE => 'local', File::KEY_MIME_TYPE => 'text/plain', File::KEY_URL => '/x'],
                'Invalid file type.',
            ],
            'remote with base64Data' => [
                [
                    File::KEY_FILE_TYPE => 'remote',
                    File::KEY_MIME_TYPE => 'text/plain',
                    File::KEY_URL => 'https://example.com/x.txt',
                    File::KEY_BASE64_DATA => 'SGVsbG8=',
                ],
                'Remote files must not contain base64Data.',
            ],
            'remote without url' => [
                [File::KEY_FILE_TYPE => 'remote', File::KEY_MIME_TYPE => 'text/plain'],
                'Remote files require a url',
            ],
            'remote with non-string url' => [
                [File::KEY_FILE_TYPE => 'remote', File::KEY_MIME_TYPE => 'text/plain', File::KEY_URL => 123],
                'Remote files require a url',
            ],
            'inline with url' => [
                [
                    File::KEY_FILE_TYPE => 'inline',
                    File::KEY_MIME_TYPE => 'text/plain',
                    File::KEY_BASE64_DATA => 'SGVsbG8=',
                    File::KEY_URL => 'https://example.com/x.txt',
                ],
                'Inline files must not contain url.',
            ],
            'inline without base64Data' => [
                [File::KEY_FILE_TYPE => 'inline', File::KEY_MIME_TYPE => 'text/plain'],
                'Inline files require base64Data.',
            ],
            'inline with non-string base64Data' => [
                [File::KEY_FILE_TYPE => 'inline', File::KEY_MIME_TYPE => 'text/plain', File::KEY_BASE64_DATA => []],
                'Inline files require base64Data.',
            ],
            'inline with data uri' => [
                [
                    File::KEY_FILE_TYPE => 'inline',
                    File::KEY_MIME_TYPE => 'text/plain',
                    File::KEY_BASE64_DATA => 'data:text/plain;base64,SGVsbG8=',
                ],
                'not a data URI',
            ],
            'inline without mimeType' => [
                [File::KEY_FILE_TYPE => 'inline', File::KEY_BASE64_DATA => 'SGVsbG8='],
                'Inline files require a mimeType.',
            ],
            'invalid mimeType' => [
                [File::KEY_FILE_TYPE => 'inline', File::KEY_MIME_TYPE => 'nope', File::KEY_BASE64_DATA => 'SGVsbG8='],
                'Invalid MIME type provided in file array.',
            ],
            'non-string mimeType' => [
                [File::KEY_FILE_TYPE => 'inline', File::KEY_MIME_TYPE => 5, File::KEY_BASE64_DATA => 'SGVsbG8='],
                'Invalid MIME type provided in file array.',
            ],
        ];
    }

    /**
     * Tests fromArray() for a remote file without mimeType infers it from the URL.
     *
     * @return void
     */
    public function testFromArrayRemoteInfersMimeType(): void
    {
        $array = [File::KEY_FILE_TYPE => 'remote', File::KEY_URL => 'https://example.com/x.png'];
        $this->assertTrue(File::isArrayShape($array));
        $file = File::fromArray($array);
        $this->assertEquals('image/png', $file->getMimeType());
    }

    /**
     * Tests isArrayShape() for valid shapes.
     *
     * @return void
     */
    public function testIsArrayShapeForValidShapes(): void
    {
        $this->assertTrue(File::isArrayShape([
            File::KEY_FILE_TYPE => 'remote',
            File::KEY_MIME_TYPE => 'image/png',
            File::KEY_URL => 'https://example.com/x.png',
        ]));
        $this->assertTrue(File::isArrayShape([
            File::KEY_FILE_TYPE => 'inline',
            File::KEY_MIME_TYPE => 'image/png',
            File::KEY_BASE64_DATA => 'iVBORw0KGgo=',
        ]));
    }

    /**
     * Tests round trip from a local path through toArray()/fromArray().
     *
     * @return void
     */
    public function testArrayRoundTripFromLocalPath(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_dir_' . uniqid();
        mkdir($tempDir);
        $path = $tempDir . '/note.txt';
        file_put_contents($path, 'round trip');

        try {
            $file = File::fromLocalPath($path);
            $restored = File::fromArray($file->toArray());
            $this->assertEquals($file->getBase64Data(), $restored->getBase64Data());
            $this->assertEquals($file->getMimeType(), $restored->getMimeType());
            $this->assertTrue($restored->isInline());
        } finally {
            unlink($path);
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
            $localFile = File::fromLocalPath($tempFile, 'text/plain');
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

    /**
     * Tests that cloning File creates an independent MimeType copy.
     *
     * @return void
     */
    public function testCloneClonesMimeType(): void
    {
        $original = new File('https://example.com/document.pdf', 'application/pdf');
        $cloned = clone $original;

        // MimeType object should be a different instance
        $this->assertNotSame($original->getMimeTypeObject(), $cloned->getMimeTypeObject());

        // But the string value should be equivalent
        $this->assertEquals($original->getMimeType(), $cloned->getMimeType());

        // FileType enum should be the same instance (enums are singletons)
        $this->assertSame($original->getFileType(), $cloned->getFileType());
    }


    /**
     * Tests that a null mimeType is treated like an absent one (inferred from the URL for remote files).
     *
     * @return void
     */
    public function testFromArrayWithNullMimeType(): void
    {
        $remote = [
            File::KEY_FILE_TYPE => 'remote',
            File::KEY_MIME_TYPE => null,
            File::KEY_URL => 'https://example.com/photo.jpg',
        ];
        $this->assertTrue(File::isArrayShape($remote));
        $file = File::fromArray($remote);
        $this->assertEquals('image/jpeg', $file->getMimeType());
        $this->assertEquals('https://example.com/photo.jpg', $file->getUrl());

        $inline = [
            File::KEY_FILE_TYPE => 'inline',
            File::KEY_MIME_TYPE => null,
            File::KEY_BASE64_DATA => 'SGVsbG8=',
        ];
        $this->assertFalse(File::isArrayShape($inline));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inline files require a mimeType.');
        File::fromArray($inline);
    }


    /**
     * Tests that a remote file array without an inferable MIME type is rejected by both isArrayShape and fromArray.
     *
     * @return void
     */
    public function testFromArrayRemoteWithoutInferableMimeTypeIsRejected(): void
    {
        $array = [File::KEY_FILE_TYPE => 'remote', File::KEY_URL => 'https://example.com/file.unknown'];
        $this->assertFalse(File::isArrayShape($array));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to determine MIME type.');
        File::fromArray($array);
    }

    /**
     * Tests that creating inline files from large payloads does not copy the payload several times.
     *
     * Regression test: routing the full base64 payload through a data URI and parsing it again
     * multiplied peak memory usage for large files.
     *
     * @since 1.5.1
     */
    public function testLargePayloadsDoNotMultiplyPeakMemory(): void
    {
        $size = 8 * 1024 * 1024;
        $tempFile = tempnam(sys_get_temp_dir(), 'file-test-');
        $this->assertIsString($tempFile);
        file_put_contents($tempFile, random_bytes($size));

        try {
            $base64 = base64_encode((string) file_get_contents($tempFile));
            $base64Length = strlen($base64);

            $peakBefore = memory_get_peak_usage();
            $fromBase64 = File::fromBase64($base64, 'application/octet-stream');
            $fromBase64Increase = memory_get_peak_usage() - $peakBefore;

            $peakBefore = memory_get_peak_usage();
            $fromLocalPath = File::fromLocalPath($tempFile, 'application/octet-stream');
            $fromLocalPathIncrease = memory_get_peak_usage() - $peakBefore;
        } finally {
            unlink($tempFile);
        }

        $this->assertSame($base64, $fromBase64->getBase64Data());
        $this->assertSame($base64, $fromLocalPath->getBase64Data());

        // fromBase64() receives the payload and must not need another full copy of it.
        $this->assertLessThan($base64Length / 2, $fromBase64Increase);
        // fromLocalPath() needs the raw contents plus their base64 form, but no further copies.
        $this->assertLessThan(2 * $base64Length, $fromLocalPathIncrease);
    }
}

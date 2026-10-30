<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Files\Util;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Files\Util\FileInputUtil;

/**
 * @covers \WordPress\AiClient\Files\Util\FileInputUtil
 */
class FileInputUtilTest extends TestCase
{
    /**
     * Tests resolution of an http(s) URL.
     *
     * @return void
     */
    public function testResolvesUrl(): void
    {
        $file = FileInputUtil::fromString('https://example.com/image.jpg');
        $this->assertTrue($file->isRemote());
        $this->assertEquals('https://example.com/image.jpg', $file->getUrl());
        $this->assertEquals('image/jpeg', $file->getMimeType());
    }

    /**
     * Tests resolution of a data URI.
     *
     * @return void
     */
    public function testResolvesDataUri(): void
    {
        $file = FileInputUtil::fromString('data:text/plain;base64,SGVsbG8=');
        $this->assertTrue($file->isInline());
        $this->assertEquals('SGVsbG8=', $file->getBase64Data());
        $this->assertEquals('text/plain', $file->getMimeType());
    }

    /**
     * Tests resolution of plain base64 data, including JPEG data starting with a slash.
     *
     * @return void
     */
    public function testResolvesBase64(): void
    {
        $file = FileInputUtil::fromString('/9j/4AAQSkZJRg==', 'image/jpeg');
        $this->assertTrue($file->isInline());
        $this->assertEquals('/9j/4AAQSkZJRg==', $file->getBase64Data());
    }

    /**
     * Tests plain base64 without a MIME type throws.
     *
     * @return void
     */
    public function testBase64WithoutMimeTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MIME type is required when providing plain base64 data');

        FileInputUtil::fromString('SGVsbG8=');
    }

    /**
     * Tests resolution of a local path, and that it wins over a base64 lookalike.
     *
     * @return void
     */
    public function testResolvesLocalPathAndLocalPathWinsOverBase64Lookalike(): void
    {
        $tempDir = sys_get_temp_dir() . '/test_dir_' . uniqid();
        mkdir($tempDir);
        $path = $tempDir . '/note.txt';
        file_put_contents($path, 'note');
        $plainPath = $tempDir . '/plainfile';
        file_put_contents($plainPath, 'plain');

        try {
            $file = FileInputUtil::fromString($path);
            $this->assertEquals(base64_encode('note'), $file->getBase64Data());
            $this->assertEquals('text/plain', $file->getMimeType());

            $plainFile = FileInputUtil::fromString($plainPath, 'text/plain');
            $this->assertEquals(base64_encode('plain'), $plainFile->getBase64Data());
        } finally {
            unlink($plainPath);
            unlink($path);
            rmdir($tempDir);
        }
    }

    /**
     * Tests rejected inputs.
     *
     * @dataProvider rejectedInputProvider
     *
     * @param string $input The input string.
     * @param string $expectedMessage The expected exception message fragment.
     * @return void
     */
    public function testRejectsInput(string $input, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        FileInputUtil::fromString($input, 'text/plain');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function rejectedInputProvider(): array
    {
        return [
            'ftp' => ['ftp://127.0.0.1:1/x', 'Only http:// and https:// URLs are supported.'],
            'file wrapper' => ['file:///etc/passwd', 'Only http:// and https:// URLs are supported.'],
            'phar' => ['phar://x.phar/y', 'Only http:// and https:// URLs are supported.'],
            'missing file' => ['/path/to/non/existent/file.txt', 'Invalid file provided.'],
            'garbage' => ['not a file!', 'Invalid file provided.'],
            'invalid data uri' => ['data:nope', 'Invalid data URI.'],
        ];
    }


    /**
     * Tests that an empty string with a MIME type resolves to an empty inline file, and an empty local file is read.
     *
     * @return void
     */
    public function testEmptyPayloads(): void
    {
        $this->assertSame('', FileInputUtil::fromString('', 'text/plain')->getBase64Data());

        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        try {
            $this->assertSame('', FileInputUtil::fromString($tempFile, 'text/plain')->getBase64Data());
        } finally {
            unlink($tempFile);
        }
    }
}

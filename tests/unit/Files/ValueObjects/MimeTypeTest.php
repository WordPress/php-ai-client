<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Files\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\ValueObjects\MimeType;

/**
 * @covers \WordPress\AiClient\Files\ValueObjects\MimeType
 */
class MimeTypeTest extends TestCase
{
    /**
     * Tests valid MIME type creation.
     *
     * @dataProvider validMimeTypeProvider
     * @param string $input
     * @param string $expected
     * @return void
     */
    public function testValidMimeTypeCreation(string $input, string $expected): void
    {
        $mimeType = new MimeType($input);
        $this->assertEquals($expected, (string) $mimeType);
    }

    /**
     * Provides valid MIME types.
     *
     * @return array
     */
    public function validMimeTypeProvider(): array
    {
        return [
            'simple type' => ['text/plain', 'text/plain'],
            'with uppercase' => ['TEXT/HTML', 'text/html'],
            'complex type' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                             'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];
    }

    /**
     * Tests invalid MIME type throws exception.
     *
     * @dataProvider invalidMimeTypeProvider
     * @param string $input
     * @return void
     */
    public function testInvalidMimeTypeThrowsException(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid MIME type: ' . $input);
        
        new MimeType($input);
    }

    /**
     * Provides invalid MIME types.
     *
     * @return array
     */
    public function invalidMimeTypeProvider(): array
    {
        return [
            'empty string' => [''],
            'no slash' => ['textplain'],
            'multiple slashes' => ['text/plain/extra'],
            'starts with slash' => ['/text/plain'],
            'ends with slash' => ['text/plain/'],
            'only type' => ['text/'],
            'only subtype' => ['/plain'],
            'invalid characters' => ['text/pl@in'],
        ];
    }

    /**
     * Tests creating MimeType from file extension.
     *
     * @dataProvider extensionProvider
     * @param string $extension
     * @param string $expectedMimeType
     * @return void
     */
    public function testFromExtension(string $extension, string $expectedMimeType): void
    {
        $mimeType = MimeType::fromExtension($extension);
        $this->assertEquals($expectedMimeType, (string) $mimeType);
    }

    /**
     * Provides file extensions and expected MIME types.
     *
     * @return array
     */
    public function extensionProvider(): array
    {
        return [
            // Text
            ['txt', 'text/plain'],
            ['html', 'text/html'],
            ['css', 'text/css'],
            ['js', 'application/javascript'],
            ['json', 'application/json'],
            ['xml', 'application/xml'],
            ['csv', 'text/csv'],
            
            // Images
            ['jpg', 'image/jpeg'],
            ['jpeg', 'image/jpeg'],
            ['png', 'image/png'],
            ['gif', 'image/gif'],
            ['webp', 'image/webp'],
            ['svg', 'image/svg+xml'],
            ['ico', 'image/x-icon'],
            
            // Documents
            ['pdf', 'application/pdf'],
            ['doc', 'application/msword'],
            ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ['xls', 'application/vnd.ms-excel'],
            ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            
            // Audio
            ['mp3', 'audio/mpeg'],
            ['wav', 'audio/wav'],
            ['ogg', 'audio/ogg'],
            
            // Video
            ['mp4', 'video/mp4'],
            ['avi', 'video/x-msvideo'],
            ['webm', 'video/webm'],
            
            // Archives
            ['zip', 'application/zip'],
            ['tar', 'application/x-tar'],
            ['gz', 'application/gzip'],
        ];
    }

    /**
     * Tests unknown extension throws exception.
     *
     * @return void
     */
    public function testUnknownExtensionThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown file extension: xyz');
        
        MimeType::fromExtension('xyz');
    }

    /**
     * Tests isValid method.
     *
     * @return void
     */
    public function testIsValid(): void
    {
        $this->assertTrue(MimeType::isValid('text/plain'));
        $this->assertTrue(MimeType::isValid('application/json'));
        $this->assertFalse(MimeType::isValid('invalid'));
        $this->assertFalse(MimeType::isValid(''));
    }

    /**
     * Tests isImage method.
     *
     * @return void
     */
    public function testIsImage(): void
    {
        $this->assertTrue((new MimeType('image/jpeg'))->isImage());
        $this->assertTrue((new MimeType('image/png'))->isImage());
        $this->assertTrue((new MimeType('image/gif'))->isImage());
        $this->assertFalse((new MimeType('text/plain'))->isImage());
        $this->assertFalse((new MimeType('video/mp4'))->isImage());
    }

    /**
     * Tests isVideo method.
     *
     * @return void
     */
    public function testIsVideo(): void
    {
        $this->assertTrue((new MimeType('video/mp4'))->isVideo());
        $this->assertTrue((new MimeType('video/webm'))->isVideo());
        $this->assertFalse((new MimeType('image/jpeg'))->isVideo());
        $this->assertFalse((new MimeType('audio/mp3'))->isVideo());
    }

    /**
     * Tests isAudio method.
     *
     * @return void
     */
    public function testIsAudio(): void
    {
        $this->assertTrue((new MimeType('audio/mpeg'))->isAudio());
        $this->assertTrue((new MimeType('audio/wav'))->isAudio());
        $this->assertFalse((new MimeType('video/mp4'))->isAudio());
        $this->assertFalse((new MimeType('text/plain'))->isAudio());
    }

    /**
     * Tests isText method.
     *
     * @return void
     */
    public function testIsText(): void
    {
        $this->assertTrue((new MimeType('text/plain'))->isText());
        $this->assertTrue((new MimeType('text/html'))->isText());
        $this->assertFalse((new MimeType('application/json'))->isText());
        $this->assertFalse((new MimeType('application/xml'))->isText());
        $this->assertFalse((new MimeType('image/jpeg'))->isText());
        $this->assertFalse((new MimeType('video/mp4'))->isText());
    }

    /**
     * Tests isDocument method.
     *
     * @return void
     */
    public function testIsDocument(): void
    {
        $this->assertTrue((new MimeType('application/pdf'))->isDocument());
        $this->assertTrue((new MimeType('application/msword'))->isDocument());
        $this->assertTrue((new MimeType('application/vnd.openxmlformats-officedocument.wordprocessingml.document'))->isDocument());
        $this->assertFalse((new MimeType('text/plain'))->isDocument());
        $this->assertFalse((new MimeType('image/jpeg'))->isDocument());
    }

    /**
     * Tests equals method.
     *
     * @return void
     */
    public function testEquals(): void
    {
        $mimeType1 = new MimeType('text/plain');
        $mimeType2 = new MimeType('text/plain');
        $mimeType3 = new MimeType('text/html');
        
        // Test with MimeType objects
        $this->assertTrue($mimeType1->equals($mimeType2));
        $this->assertFalse($mimeType1->equals($mimeType3));
        
        // Test with strings
        $this->assertTrue($mimeType1->equals('text/plain'));
        $this->assertTrue($mimeType1->equals('TEXT/PLAIN'));
        $this->assertFalse($mimeType1->equals('text/html'));
        
        // Test with invalid types
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid MIME type comparison: integer');
        $mimeType1->equals(123);
    }

    /**
     * Tests toString method.
     *
     * @return void
     */
    public function testToString(): void
    {
        $mimeType = new MimeType('TEXT/HTML');
        $this->assertEquals('text/html', (string) $mimeType);
    }

    /**
     * Tests normalizing values.
     *
     * @return void
     */
    public function testNormalizesValues(): void
    {
        $mimeType = new MimeType('IMAGE/JPEG');
        $this->assertEquals('image/jpeg', (string) $mimeType);
    }

    public function testHasMimeType(): void
    {
        $mimeType = new MimeType('image/jpeg');
        $fontType = new MimeType('font/ttf');
        
        $this->assertTrue($mimeType->hasMimeType('image'));
        $this->assertFalse($mimeType->hasMimeType('video'));

        $this->assertTrue($fontType->hasMimeType('font'));
        $this->assertFalse($fontType->hasMimeType('image'));
    }
}
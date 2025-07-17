<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Tests\unit\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Messages\Enums\ModalityEnum
 */
class ModalityEnumTest extends TestCase
{
    use EnumTestTrait;

    protected function getEnumClass(): string
    {
        return ModalityEnum::class;
    }

    protected function getExpectedValues(): array
    {
        return [
            'TEXT' => 'text',
            'DOCUMENT' => 'document',
            'IMAGE' => 'image',
            'AUDIO' => 'audio',
            'VIDEO' => 'video',
        ];
    }

    public function testSpecificEnumMethods(): void
    {
        $text = ModalityEnum::text();
        $this->assertTrue($text->isText());
        $this->assertFalse($text->isDocument());

        $image = ModalityEnum::image();
        $this->assertTrue($image->isImage());
        $this->assertFalse($image->isAudio());

        $video = ModalityEnum::video();
        $this->assertTrue($video->isVideo());
        $this->assertFalse($video->isText());
    }
}

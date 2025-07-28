<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Tests\traits\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum
 */
class CapabilityEnumTest extends TestCase
{
    use EnumTestTrait;

    /**
     * Gets the enum class to test.
     *
     * @return string
     */
    protected function getEnumClass(): string
    {
        return CapabilityEnum::class;
    }

    /**
     * Gets the expected enum values.
     *
     * @return array
     */
    protected function getExpectedValues(): array
    {
        return [
            'TEXT_GENERATION' => 'text_generation',
            'IMAGE_GENERATION' => 'image_generation',
            'TEXT_TO_SPEECH_CONVERSION' => 'text_to_speech_conversion',
            'SPEECH_GENERATION' => 'speech_generation',
            'MUSIC_GENERATION' => 'music_generation',
            'VIDEO_GENERATION' => 'video_generation',
            'EMBEDDING_GENERATION' => 'embedding_generation',
            'CHAT_HISTORY' => 'chat_history',
        ];
    }

    /**
     * Tests the specific enum methods.
     *
     * @return void
     */
    public function testSpecificEnumMethods(): void
    {
        $textGen = CapabilityEnum::textGeneration();
        $this->assertTrue($textGen->isTextGeneration());
        $this->assertFalse($textGen->isImageGeneration());

        $imageGen = CapabilityEnum::imageGeneration();
        $this->assertTrue($imageGen->isImageGeneration());
        $this->assertFalse($imageGen->isTextToSpeechConversion());

        $chatHistory = CapabilityEnum::chatHistory();
        $this->assertTrue($chatHistory->isChatHistory());
        $this->assertFalse($chatHistory->isEmbeddingGeneration());
    }
}

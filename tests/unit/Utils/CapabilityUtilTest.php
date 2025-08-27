<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Utils;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Utils\CapabilityUtil;

/**
 * @covers \WordPress\AiClient\Utils\CapabilityUtil
 */
class CapabilityUtilTest extends TestCase
{
    /**
     * Tests capability mapping for generation types.
     */
    public function testGetCapabilityForGenerationType(): void
    {
        $this->assertTrue(
            CapabilityUtil::getCapabilityForGenerationType('text')?->equals(CapabilityEnum::textGeneration())
        );
        $result = CapabilityUtil::getCapabilityForGenerationType('image');
        $this->assertTrue($result?->equals(CapabilityEnum::imageGeneration()));
        $result = CapabilityUtil::getCapabilityForGenerationType('speech');
        $this->assertTrue($result?->equals(CapabilityEnum::speechGeneration()));
        $result = CapabilityUtil::getCapabilityForGenerationType('text-to-speech');
        $this->assertTrue($result?->equals(CapabilityEnum::textToSpeechConversion()));
        $result = CapabilityUtil::getCapabilityForGenerationType('tts');
        $this->assertTrue($result?->equals(CapabilityEnum::textToSpeechConversion()));
        $result = CapabilityUtil::getCapabilityForGenerationType('music');
        $this->assertTrue($result?->equals(CapabilityEnum::musicGeneration()));
        $result = CapabilityUtil::getCapabilityForGenerationType('video');
        $this->assertTrue($result?->equals(CapabilityEnum::videoGeneration()));
        $result = CapabilityUtil::getCapabilityForGenerationType('embedding');
        $this->assertTrue($result?->equals(CapabilityEnum::embeddingGeneration()));
        $result = CapabilityUtil::getCapabilityForGenerationType('embeddings');
        $this->assertTrue($result?->equals(CapabilityEnum::embeddingGeneration()));
    }

    /**
     * Tests case insensitive capability mapping.
     */
    public function testGetCapabilityForGenerationTypeIsCaseInsensitive(): void
    {
        $textResult = CapabilityUtil::getCapabilityForGenerationType('TEXT');
        $this->assertTrue($textResult?->equals(CapabilityEnum::textGeneration()));

        $imageResult = CapabilityUtil::getCapabilityForGenerationType('Image');
        $this->assertTrue($imageResult?->equals(CapabilityEnum::imageGeneration()));

        $speechResult = CapabilityUtil::getCapabilityForGenerationType('SPEECH');
        $this->assertTrue($speechResult?->equals(CapabilityEnum::speechGeneration()));
    }

    /**
     * Tests unknown generation types return null.
     */
    public function testGetCapabilityForGenerationTypeReturnsNullForUnknown(): void
    {
        $this->assertNull(CapabilityUtil::getCapabilityForGenerationType('unknown'));
        $this->assertNull(CapabilityUtil::getCapabilityForGenerationType(''));
        $this->assertNull(CapabilityUtil::getCapabilityForGenerationType('invalid-type'));
    }

    /**
     * Tests primary output modality mapping.
     */
    public function testGetPrimaryOutputModality(): void
    {
        $this->assertTrue(
            CapabilityUtil::getPrimaryOutputModality(CapabilityEnum::textGeneration())?->equals(ModalityEnum::text())
        );
        $this->assertTrue(
            CapabilityUtil::getPrimaryOutputModality(CapabilityEnum::imageGeneration())?->equals(ModalityEnum::image())
        );
        $this->assertTrue(
            CapabilityUtil::getPrimaryOutputModality(CapabilityEnum::speechGeneration())?->equals(ModalityEnum::audio())
        );
        $this->assertTrue(
            CapabilityUtil::getPrimaryOutputModality(CapabilityEnum::textToSpeechConversion())?->equals(ModalityEnum::audio())
        );
        $this->assertTrue(
            CapabilityUtil::getPrimaryOutputModality(CapabilityEnum::musicGeneration())?->equals(ModalityEnum::audio())
        );
        $this->assertTrue(
            CapabilityUtil::getPrimaryOutputModality(CapabilityEnum::videoGeneration())?->equals(ModalityEnum::video())
        );
    }

    /**
     * Tests embedding generation returns null for output modality.
     */
    public function testGetPrimaryOutputModalityReturnsNullForEmbedding(): void
    {
        $this->assertNull(CapabilityUtil::getPrimaryOutputModality(CapabilityEnum::embeddingGeneration()));
    }

    /**
     * Tests default input modalities for capabilities.
     */
    public function testGetDefaultInputModalities(): void
    {
        $expected = [ModalityEnum::text()];

        $this->assertEquals($expected, CapabilityUtil::getDefaultInputModalities(CapabilityEnum::textGeneration()));
        $this->assertEquals($expected, CapabilityUtil::getDefaultInputModalities(CapabilityEnum::imageGeneration()));
        $this->assertEquals($expected, CapabilityUtil::getDefaultInputModalities(CapabilityEnum::speechGeneration()));
        $this->assertEquals($expected, CapabilityUtil::getDefaultInputModalities(CapabilityEnum::textToSpeechConversion()));
        $this->assertEquals($expected, CapabilityUtil::getDefaultInputModalities(CapabilityEnum::musicGeneration()));
        $this->assertEquals($expected, CapabilityUtil::getDefaultInputModalities(CapabilityEnum::videoGeneration()));
        $this->assertEquals($expected, CapabilityUtil::getDefaultInputModalities(CapabilityEnum::embeddingGeneration()));
    }

    /**
     * Tests chat history default input modalities.
     */
    public function testGetDefaultInputModalitiesForChatHistory(): void
    {
        $this->assertEquals([], CapabilityUtil::getDefaultInputModalities(CapabilityEnum::chatHistory()));
    }

    /**
     * Tests capability compatibility.
     */
    public function testAreCompatible(): void
    {
        // Same capability is compatible
        $this->assertTrue(CapabilityUtil::areCompatible(
            CapabilityEnum::textGeneration(),
            CapabilityEnum::textGeneration()
        ));

        // Chat history is compatible with generation types
        $this->assertTrue(CapabilityUtil::areCompatible(
            CapabilityEnum::chatHistory(),
            CapabilityEnum::textGeneration()
        ));
        $this->assertTrue(CapabilityUtil::areCompatible(
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory()
        ));

        // Different generation types are not compatible
        $this->assertFalse(CapabilityUtil::areCompatible(
            CapabilityEnum::textGeneration(),
            CapabilityEnum::imageGeneration()
        ));
        $this->assertFalse(CapabilityUtil::areCompatible(
            CapabilityEnum::speechGeneration(),
            CapabilityEnum::videoGeneration()
        ));
    }

    /**
     * Tests getting all generation capabilities.
     */
    public function testGetAllGenerationCapabilities(): void
    {
        $capabilities = CapabilityUtil::getAllGenerationCapabilities();

        $this->assertCount(7, $capabilities);
        $this->assertContains(CapabilityEnum::textGeneration(), $capabilities);
        $this->assertContains(CapabilityEnum::imageGeneration(), $capabilities);
        $this->assertContains(CapabilityEnum::speechGeneration(), $capabilities);
        $this->assertContains(CapabilityEnum::textToSpeechConversion(), $capabilities);
        $this->assertContains(CapabilityEnum::musicGeneration(), $capabilities);
        $this->assertContains(CapabilityEnum::videoGeneration(), $capabilities);
        $this->assertContains(CapabilityEnum::embeddingGeneration(), $capabilities);
    }

    /**
     * Tests chat history is not in generation capabilities.
     */
    public function testGetAllGenerationCapabilitiesExcludesChatHistory(): void
    {
        $capabilities = CapabilityUtil::getAllGenerationCapabilities();
        $this->assertNotContains(CapabilityEnum::chatHistory(), $capabilities);
    }

    /**
     * Tests file output detection.
     */
    public function testProducesFileOutput(): void
    {
        // Capabilities that produce files
        $this->assertTrue(CapabilityUtil::producesFileOutput(CapabilityEnum::imageGeneration()));
        $this->assertTrue(CapabilityUtil::producesFileOutput(CapabilityEnum::speechGeneration()));
        $this->assertTrue(CapabilityUtil::producesFileOutput(CapabilityEnum::textToSpeechConversion()));
        $this->assertTrue(CapabilityUtil::producesFileOutput(CapabilityEnum::musicGeneration()));
        $this->assertTrue(CapabilityUtil::producesFileOutput(CapabilityEnum::videoGeneration()));

        // Capabilities that don't produce files
        $this->assertFalse(CapabilityUtil::producesFileOutput(CapabilityEnum::textGeneration()));
        $this->assertFalse(CapabilityUtil::producesFileOutput(CapabilityEnum::embeddingGeneration()));
        $this->assertFalse(CapabilityUtil::producesFileOutput(CapabilityEnum::chatHistory()));
    }

    /**
     * Tests file extension suggestions.
     */
    public function testGetSuggestedFileExtensions(): void
    {
        // Image generation
        $imageExtensions = CapabilityUtil::getSuggestedFileExtensions(CapabilityEnum::imageGeneration());
        $this->assertEquals(['png', 'jpg', 'jpeg', 'webp'], $imageExtensions);

        // Speech generation
        $speechExtensions = CapabilityUtil::getSuggestedFileExtensions(CapabilityEnum::speechGeneration());
        $this->assertEquals(['mp3', 'wav', 'ogg'], $speechExtensions);

        // Text-to-speech
        $ttsExtensions = CapabilityUtil::getSuggestedFileExtensions(CapabilityEnum::textToSpeechConversion());
        $this->assertEquals(['mp3', 'wav', 'ogg'], $ttsExtensions);

        // Music generation
        $musicExtensions = CapabilityUtil::getSuggestedFileExtensions(CapabilityEnum::musicGeneration());
        $this->assertEquals(['mp3', 'wav', 'midi'], $musicExtensions);

        // Video generation
        $videoExtensions = CapabilityUtil::getSuggestedFileExtensions(CapabilityEnum::videoGeneration());
        $this->assertEquals(['mp4', 'webm', 'mov'], $videoExtensions);
    }

    /**
     * Tests no file extensions for non-file-producing capabilities.
     */
    public function testGetSuggestedFileExtensionsReturnsEmptyForNonFileCapabilities(): void
    {
        $this->assertEmpty(CapabilityUtil::getSuggestedFileExtensions(CapabilityEnum::textGeneration()));
        $this->assertEmpty(CapabilityUtil::getSuggestedFileExtensions(CapabilityEnum::embeddingGeneration()));
        $this->assertEmpty(CapabilityUtil::getSuggestedFileExtensions(CapabilityEnum::chatHistory()));
    }

    /**
     * Tests comprehensive compatibility matrix.
     */
    public function testComprehensiveCompatibilityMatrix(): void
    {
        $generationCapabilities = CapabilityUtil::getAllGenerationCapabilities();

        // Test that no two different generation capabilities are compatible
        for ($i = 0; $i < count($generationCapabilities); $i++) {
            for ($j = $i + 1; $j < count($generationCapabilities); $j++) {
                $this->assertFalse(
                    CapabilityUtil::areCompatible($generationCapabilities[$i], $generationCapabilities[$j]),
                    sprintf(
                        'Generation capabilities should not be compatible: %s vs %s',
                        $generationCapabilities[$i]->value,
                        $generationCapabilities[$j]->value
                    )
                );
            }
        }

        // Test that all generation capabilities are compatible with chat history
        foreach ($generationCapabilities as $capability) {
            $this->assertTrue(
                CapabilityUtil::areCompatible($capability, CapabilityEnum::chatHistory()),
                sprintf('Generation capability %s should be compatible with chat history', $capability->value)
            );
            $this->assertTrue(
                CapabilityUtil::areCompatible(CapabilityEnum::chatHistory(), $capability),
                sprintf('Chat history should be compatible with generation capability %s', $capability->value)
            );
        }
    }
}

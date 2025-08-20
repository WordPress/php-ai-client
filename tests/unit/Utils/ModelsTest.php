<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Utils;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Tests\mocks\MockImageGenerationModel;
use WordPress\AiClient\Tests\mocks\MockModel;
use WordPress\AiClient\Tests\mocks\MockTextGenerationModel;
use WordPress\AiClient\Utils\Models;

/**
 * Test case for Models utility class.
 *
 * @covers \WordPress\AiClient\Utils\Models
 * @since n.e.x.t
 */
class ModelsTest extends TestCase
{
    private ProviderRegistry $registry;
    private MockTextGenerationModel $mockTextModel;
    private MockImageGenerationModel $mockImageModel;
    private MockModel $mockModel;

    protected function setUp(): void
    {
        $this->registry = new ProviderRegistry();

        $mockMetadata = $this->createMock(ModelMetadata::class);
        $mockConfig = $this->createMock(ModelConfig::class);

        $this->mockTextModel = new MockTextGenerationModel();
        $this->mockImageModel = new MockImageGenerationModel();
        $this->mockModel = new MockModel($mockMetadata, $mockConfig);
    }

    /**
     * Tests that validateTextGeneration passes with valid model.
     *
     * @covers \WordPress\AiClient\Utils\Models::validateTextGeneration
     */
    public function testValidateTextGenerationPassesWithValidModel(): void
    {
        $this->expectNotToPerformAssertions();
        Models::validateTextGeneration($this->mockTextModel);
    }

    /**
     * Tests that validateTextGeneration throws exception with invalid model.
     *
     * @covers \WordPress\AiClient\Utils\Models::validateTextGeneration
     */
    public function testValidateTextGenerationThrowsExceptionWithInvalidModel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model must implement TextGenerationModelInterface for text generation');

        Models::validateTextGeneration($this->mockModel);
    }

    /**
     * Tests that validateImageGeneration passes with valid model.
     *
     * @covers \WordPress\AiClient\Utils\Models::validateImageGeneration
     */
    public function testValidateImageGenerationPassesWithValidModel(): void
    {
        $this->expectNotToPerformAssertions();
        Models::validateImageGeneration($this->mockImageModel);
    }

    /**
     * Tests that validateImageGeneration throws exception with invalid model.
     *
     * @covers \WordPress\AiClient\Utils\Models::validateImageGeneration
     */
    public function testValidateImageGenerationThrowsExceptionWithInvalidModel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model must implement ImageGenerationModelInterface for image generation');

        Models::validateImageGeneration($this->mockModel);
    }

    /**
     * Tests that validateTextToSpeechConversion throws exception with invalid model.
     *
     * @covers \WordPress\AiClient\Utils\Models::validateTextToSpeechConversion
     */
    public function testValidateTextToSpeechConversionThrowsExceptionWithInvalidModel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model must implement TextToSpeechConversionModelInterface for text-to-speech conversion');

        Models::validateTextToSpeechConversion($this->mockModel);
    }

    /**
     * Tests that validateSpeechGeneration throws exception with invalid model.
     *
     * @covers \WordPress\AiClient\Utils\Models::validateSpeechGeneration
     */
    public function testValidateSpeechGenerationThrowsExceptionWithInvalidModel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model must implement SpeechGenerationModelInterface for speech generation');

        Models::validateSpeechGeneration($this->mockModel);
    }

    /**
     * Tests that validateTextGenerationOperation throws exception with invalid model.
     *
     * @covers \WordPress\AiClient\Utils\Models::validateTextGenerationOperation
     */
    public function testValidateTextGenerationOperationThrowsExceptionWithInvalidModel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model must implement TextGenerationModelInterface for text generation operations');

        Models::validateTextGenerationOperation($this->mockModel);
    }

    /**
     * Tests that validateImageGenerationOperation throws exception with invalid model.
     *
     * @covers \WordPress\AiClient\Utils\Models::validateImageGenerationOperation
     */
    public function testValidateImageGenerationOperationThrowsExceptionWithInvalidModel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model must implement ImageGenerationModelInterface for image generation operations');

        Models::validateImageGenerationOperation($this->mockModel);
    }

    /**
     * Tests that validateTextToSpeechConversionOperation throws exception with invalid model.
     */
    public function testValidateTextToSpeechConversionOperationThrowsExceptionWithInvalidModel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model must implement TextToSpeechConversionOperationModelInterface for text-to-speech conversion operations');

        Models::validateTextToSpeechConversionOperation($this->mockModel);
    }

    /**
     * Tests that validateSpeechGenerationOperation throws exception with invalid model.
     */
    public function testValidateSpeechGenerationOperationThrowsExceptionWithInvalidModel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model must implement SpeechGenerationOperationModelInterface for speech generation operations');

        Models::validateSpeechGenerationOperation($this->mockModel);
    }

    /**
     * Tests that findTextModel throws exception when no models available.
     */
    public function testFindTextModelThrowsExceptionWhenNoModelsAvailable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No text generation models available');

        Models::findTextModel($this->registry);
    }

    /**
     * Tests that findImageModel throws exception when no models available.
     */
    public function testFindImageModelThrowsExceptionWhenNoModelsAvailable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No image generation models available');

        Models::findImageModel($this->registry);
    }

    /**
     * Tests that findTextToSpeechModel throws exception when no models available.
     */
    public function testFindTextToSpeechModelThrowsExceptionWhenNoModelsAvailable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No text-to-speech conversion models available');

        Models::findTextToSpeechModel($this->registry);
    }

    /**
     * Tests that findSpeechModel throws exception when no models available.
     */
    public function testFindSpeechModelThrowsExceptionWhenNoModelsAvailable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No speech generation models available');

        Models::findSpeechModel($this->registry);
    }
}

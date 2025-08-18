<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Utils;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Tests\mocks\MockImageGenerationModel;
use WordPress\AiClient\Tests\mocks\MockTextGenerationModel;
use WordPress\AiClient\Utils\GenerationStrategyResolver;

/**
 * @covers \WordPress\AiClient\Utils\GenerationStrategyResolver
 */
class GenerationStrategyResolverTest extends TestCase
{
    /**
     * Tests resolve returns correct method for text generation model.
     */
    public function testResolveReturnsTextGenerationMethod(): void
    {
        $model = $this->createMock(MockTextGenerationModel::class);

        $method = GenerationStrategyResolver::resolve($model);

        $this->assertEquals('generateTextResult', $method);
    }

    /**
     * Tests resolve returns correct method for image generation model.
     */
    public function testResolveReturnsImageGenerationMethod(): void
    {
        $model = $this->createMock(MockImageGenerationModel::class);

        $method = GenerationStrategyResolver::resolve($model);

        $this->assertEquals('generateImageResult', $method);
    }

    /**
     * Tests resolve throws exception for unsupported model.
     */
    public function testResolveThrowsExceptionForUnsupportedModel(): void
    {
        $model = $this->createMock(ModelInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model must implement at least one supported generation interface ' .
            '(TextGeneration, ImageGeneration, TextToSpeechConversion, SpeechGeneration)'
        );

        GenerationStrategyResolver::resolve($model);
    }
}

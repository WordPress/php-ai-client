<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * Mock image generation model for testing.
 *
 * @since n.e.x.t
 */
class MockImageGenerationModel implements ModelInterface, ImageGenerationModelInterface
{
    /**
     * @var ModelMetadata The model metadata.
     */
    private ModelMetadata $metadata;

    /**
     * @var ModelConfig The model configuration.
     */
    private ModelConfig $config;

    /**
     * Constructor.
     *
     * @param ModelMetadata|null $metadata The model metadata.
     * @param ModelConfig|null $config The model configuration.
     */
    public function __construct(?ModelMetadata $metadata = null, ?ModelConfig $config = null)
    {
        $this->metadata = $metadata ?? new ModelMetadata(
            'mock-image-model',
            'Mock Image Model',
            [CapabilityEnum::imageGeneration()],
            []
        );
        $this->config = $config ?? new ModelConfig();
    }

    /**
     * {@inheritDoc}
     */
    public function metadata(): ModelMetadata
    {
        return $this->metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig(): ModelConfig
    {
        return $this->config;
    }

    /**
     * {@inheritDoc}
     */
    public function setConfig(ModelConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function generateImageResult(array $prompt): GenerativeAiResult
    {
        $mockImageFile = new File(
            'data:image/png;base64,' .
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'image/png'
        );

        $candidate = new Candidate(
            new ModelMessage([new MessagePart($mockImageFile)]),
            FinishReasonEnum::stop()
        );
        $tokenUsage = new TokenUsage(3, 8, 11);

        $providerMetadata = new ProviderMetadata(
            'mock-image-provider',
            'Mock Image Provider',
            ProviderTypeEnum::cloud()
        );

        return new GenerativeAiResult(
            'mock-image-result-id',
            [$candidate],
            $tokenUsage,
            $providerMetadata,
            $this->metadata
        );
    }
}

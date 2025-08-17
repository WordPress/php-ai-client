<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Operations\DTO\EmbeddingOperation;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts\EmbeddingGenerationOperationModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Mock embedding generation operation model for testing.
 *
 * @since n.e.x.t
 */
class MockEmbeddingGenerationOperationModel implements ModelInterface, EmbeddingGenerationOperationModelInterface
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
            'mock-embedding-operation-model',
            'Mock Embedding Operation Model',
            [CapabilityEnum::embeddingGeneration()],
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
    public function generateEmbeddingsOperation(array $input): EmbeddingOperation
    {
        // Create a mock embedding operation in starting state
        return new EmbeddingOperation(
            'mock-embedding-op-' . uniqid(),
            OperationStateEnum::starting(),
            null
        );
    }
}

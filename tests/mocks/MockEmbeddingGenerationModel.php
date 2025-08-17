<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Embeddings\DTO\Embedding;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts\EmbeddingGenerationModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * Mock embedding generation model for testing.
 *
 * @since n.e.x.t
 */
class MockEmbeddingGenerationModel implements ModelInterface, EmbeddingGenerationModelInterface
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
            'mock-embedding-model',
            'Mock Embedding Model',
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
    public function generateEmbeddingsResult(array $input): EmbeddingResult
    {
        // Generate mock embeddings based on input length
        $embeddings = [];
        foreach ($input as $index => $message) {
            // Create a simple mock embedding vector based on message index
            $vector = array_fill(0, 3, 0.1 + ($index * 0.1));
            $embeddings[] = new Embedding($vector);
        }

        $tokenUsage = new TokenUsage(count($input) * 5, 0, count($input) * 5);

        return new EmbeddingResult(
            'mock-embedding-result-' . uniqid(),
            $embeddings,
            $tokenUsage,
            ['model' => 'mock-embedding-model']
        );
    }
}

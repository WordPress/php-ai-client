<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use Generator;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * Mock text generation model for testing.
 *
 * @since n.e.x.t
 */
class MockTextGenerationModel implements ModelInterface, TextGenerationModelInterface
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
            'mock-text-model',
            'Mock Text Model',
            [CapabilityEnum::textGeneration()],
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
    public function generateTextResult(array $prompt): GenerativeAiResult
    {
        $candidate = new Candidate(
            new ModelMessage([new MessagePart('Mock text generation result')]),
            FinishReasonEnum::stop()
        );
        $tokenUsage = new TokenUsage(5, 15, 20);

        $providerMetadata = new ProviderMetadata(
            'mock-text-provider',
            'Mock Text Provider',
            ProviderTypeEnum::cloud()
        );

        return new GenerativeAiResult(
            'mock-text-result-id',
            [$candidate],
            $tokenUsage,
            $providerMetadata,
            $this->metadata
        );
    }

    /**
     * {@inheritDoc}
     */
    public function streamGenerateTextResult(array $prompt): Generator
    {
        // Return a simple mock generator that yields one result
        yield $this->generateTextResult($prompt);
    }
}

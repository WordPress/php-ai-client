<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\Http\Contracts\WithHttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\WithRequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\Traits\WithHttpTransporterTrait;
use WordPress\AiClient\Providers\Http\Traits\WithRequestAuthenticationTrait;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Mock model for testing.
 *
 * @since n.e.x.t
 */
class MockModel implements ModelInterface, WithHttpTransporterInterface, WithRequestAuthenticationInterface
{
    use WithHttpTransporterTrait;
    use WithRequestAuthenticationTrait;

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
     * @param ModelMetadata $metadata The model metadata.
     * @param ModelConfig $config The model configuration.
     */
    public function __construct(ModelMetadata $metadata, ModelConfig $config)
    {
        $this->metadata = $metadata;
        $this->config = $config;
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
    public function providerMetadata(): \WordPress\AiClient\Providers\DTO\ProviderMetadata
    {
        // This mock doesn't need to return actual provider metadata for its tests.
        return $this->createMock(\WordPress\AiClient\Providers\DTO\ProviderMetadata::class);
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
}

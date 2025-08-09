<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers;

use InvalidArgumentException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Contracts\WithHttpTransporterInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Traits\WithHttpTransporterTrait;

/**
 * Base class for an API-based model for a provider.
 *
 * While this class contains no abstract methods, it is still abstract to ensure that each model class can actually
 * perform generative AI tasks by implementing the corresponding interfaces.
 *
 * @since n.e.x.t
 */
abstract class AbstractApiBasedModel implements
    ModelInterface,
    WithHttpTransporterInterface
{
    use WithHttpTransporterTrait;

    /**
     * @var ModelMetadata The metadata for the model.
     */
    private ModelMetadata $metadata;

    /**
     * @var ProviderMetadata The metadata for the model's provider.
     */
    private ProviderMetadata $providerMetadata;

    /**
     * @var ModelConfig The configuration for the model.
     */
    private ModelConfig $config;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param ModelMetadata $metadata The metadata for the model.
     * @param ProviderMetadata $providerMetadata The metadata for the model's provider.
     */
    public function __construct(ModelMetadata $metadata, ProviderMetadata $providerMetadata)
    {
        $this->metadata = $metadata;
        $this->providerMetadata = $providerMetadata;
        $this->config = ModelConfig::fromArray([]);
    }

    /**
     * @inheritdoc
     */
    final public function metadata(): ModelMetadata
    {
        return $this->metadata;
    }

    /**
     * @inheritdoc
     */
    final public function providerMetadata(): ProviderMetadata
    {
        return $this->providerMetadata;
    }

    /**
     * @inheritdoc
     */
    final public function setConfig(ModelConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    final public function getConfig(): ModelConfig
    {
        return $this->config;
    }
}

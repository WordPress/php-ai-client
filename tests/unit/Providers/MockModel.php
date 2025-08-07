
<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers;

use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Mock model for testing.
 *
 * @since n.e.x.t
 */
class MockModel implements ModelInterface
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
    public function getMetadata(): ModelMetadata
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
}

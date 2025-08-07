<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Contracts;

use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Interface for AI models.
 *
 * All models must implement this interface to provide
 * metadata access and configuration capabilities.
 *
 * @since n.e.x.t
 */
interface ModelInterface
{
    /**
     * Gets the model's metadata.
     *
     * @since n.e.x.t
     *
     * @return ModelMetadata The model metadata.
     */
    public function getMetadata(): ModelMetadata;

    /**
     * Gets the current model configuration.
     *
     * @since n.e.x.t
     *
     * @return ModelConfig The model configuration.
     */
    public function getConfig(): ModelConfig;

    /**
     * Sets the model configuration.
     *
     * @since n.e.x.t
     *
     * @param ModelConfig $config The model configuration.
     */
    public function setConfig(ModelConfig $config): void;
}

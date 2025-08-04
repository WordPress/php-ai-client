<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Contracts;

use InvalidArgumentException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Interface for accessing model metadata within a provider.
 *
 * Provides methods to list, check, and retrieve model metadata
 * for all models supported by a provider.
 *
 * @since n.e.x.t
 */
interface ModelMetadataDirectoryInterface
{
    /**
     * Lists all available model metadata.
     *
     * @since n.e.x.t
     *
     * @return list<ModelMetadata> Array of model metadata.
     */
    public function listModelMetadata(): array;

    /**
     * Checks if metadata exists for a specific model.
     *
     * @since n.e.x.t
     *
     * @param string $modelId Model identifier.
     * @return bool True if metadata exists, false otherwise.
     */
    public function hasModelMetadata(string $modelId): bool;

    /**
     * Gets metadata for a specific model.
     *
     * @since n.e.x.t
     *
     * @param string $modelId Model identifier.
     * @return ModelMetadata Model metadata.
     * @throws InvalidArgumentException If model metadata not found.
     */
    public function getModelMetadata(string $modelId): ModelMetadata;
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Contracts;

use WordPress\AiClient\Operations\DTO\EmbeddingOperation;

/**
 * Interface for models that can retrieve embedding operations.
 *
 * @since 0.2.0
 */
interface WithEmbeddingOperationsInterface
{
    /**
     * Retrieves an embedding operation by ID.
     *
     * @since 0.2.0
     *
     * @param string $operationId The operation identifier.
     * @return EmbeddingOperation The embedding operation.
     */
    public function getOperation(string $operationId): EmbeddingOperation;
}

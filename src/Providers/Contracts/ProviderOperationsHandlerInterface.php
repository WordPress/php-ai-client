<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Contracts;

use InvalidArgumentException;
use WordPress\AiClient\Operations\DTO\GenerativeAiOperation;

/**
 * Interface for handling provider-level operations.
 *
 * Provides methods to retrieve and manage long-running operations
 * across all models within a provider. Operations are tracked at the
 * provider level rather than per-model.
 *
 * @since n.e.x.t
 */
interface ProviderOperationsHandlerInterface
{
    /**
     * Gets an operation by ID.
     *
     * @since n.e.x.t
     *
     * @param string $operationId Operation identifier.
     * @return GenerativeAiOperation The operation.
     * @throws InvalidArgumentException If operation not found.
     */
    public function getOperation(string $operationId): GenerativeAiOperation;
}

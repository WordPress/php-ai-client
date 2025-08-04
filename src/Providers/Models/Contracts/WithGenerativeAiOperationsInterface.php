<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Contracts;

use InvalidArgumentException;
use WordPress\AiClient\Operations\DTO\GenerativeAiOperation;

/**
 * Interface for models that support generative AI operations.
 *
 * Provides methods to retrieve and manage long-running generative AI
 * operations such as text, image, or speech generation.
 *
 * @since n.e.x.t
 */
interface WithGenerativeAiOperationsInterface
{
    /**
     * Gets a generative AI operation by ID.
     *
     * @since n.e.x.t
     *
     * @param string $operationId Operation identifier.
     * @return GenerativeAiOperation The generative AI operation.
     * @throws InvalidArgumentException If operation not found.
     */
    public function getOperation(string $operationId): GenerativeAiOperation;
}

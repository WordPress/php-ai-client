<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Operations\DTO\EmbeddingOperation;

/**
 * Interface for models that support asynchronous embedding generation operations.
 *
 * Provides methods for initiating long-running embedding generation tasks.
 * This is useful for large datasets or when embedding generation takes significant time.
 *
 * @since n.e.x.t
 */
interface EmbeddingGenerationOperationModelInterface
{
    /**
     * Creates an embedding generation operation.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $input Array of messages containing the input data to generate embeddings for.
     * @return EmbeddingOperation The initiated embedding generation operation.
     */
    public function generateEmbeddingsOperation(array $input): EmbeddingOperation;
}
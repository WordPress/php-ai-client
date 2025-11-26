<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Operations\DTO\EmbeddingOperation;

/**
 * Interface for models that can initiate embedding generation operations.
 *
 * @since 0.2.0
 */
interface EmbeddingGenerationOperationModelInterface
{
    /**
     * Starts an embedding generation operation for asynchronous processing.
     *
     * @since 0.2.0
     *
     * @param list<Message> $input The input documents/messages to embed.
     * @return EmbeddingOperation The created operation.
     */
    public function generateEmbeddingsOperation(array $input): EmbeddingOperation;
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Results\DTO\EmbeddingResult;

/**
 * Interface for models that support embedding generation.
 *
 * Provides synchronous methods for generating embeddings from input data.
 * Embeddings are high-dimensional vector representations of data that enable
 * semantic similarity comparisons, search, and classification tasks.
 *
 * @since n.e.x.t
 */
interface EmbeddingGenerationModelInterface
{
    /**
     * Generates embeddings from input data.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $input Array of messages containing the input data to generate embeddings for.
     * @return EmbeddingResult Result containing generated embeddings.
     */
    public function generateEmbeddingsResult(array $input): EmbeddingResult;
}
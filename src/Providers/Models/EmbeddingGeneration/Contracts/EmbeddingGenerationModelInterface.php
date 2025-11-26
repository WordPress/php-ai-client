<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Results\DTO\EmbeddingResult;

/**
 * Interface for models that support embedding generation.
 *
 * @since 0.2.0
 */
interface EmbeddingGenerationModelInterface
{
    /**
     * Generates embeddings for the provided input.
     *
     * @since 0.2.0
     *
     * @param list<Message> $input The input documents/messages to embed.
     * @return EmbeddingResult The generated embeddings result.
     */
    public function generateEmbeddingsResult(array $input): EmbeddingResult;
}

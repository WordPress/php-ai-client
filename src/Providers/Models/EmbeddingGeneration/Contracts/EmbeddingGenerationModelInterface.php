<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Results\DTO\EmbeddingResult;

/**
 * Interface for models that support embedding generation.
 *
 * @since n.e.x.t
 */
interface EmbeddingGenerationModelInterface
{
    /**
     * Generates embeddings from one or more prompts.
     *
     * @since n.e.x.t
     *
     * @param list<list<Message>> $prompts Array of message lists to embed.
     * @return EmbeddingResult Result containing generated embedding vectors.
     */
    public function generateEmbeddingResult(array $prompts): EmbeddingResult;
}

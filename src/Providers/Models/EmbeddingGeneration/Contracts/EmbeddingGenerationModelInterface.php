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
     * Generates embeddings from a prompt.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt Array of messages containing the embedding prompt.
     * @return EmbeddingResult Result containing embedding vectors.
     */
    public function generateEmbeddingResult(array $prompt): EmbeddingResult;
}

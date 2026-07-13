<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts;

use WordPress\AiClient\Results\DTO\EmbeddingResult;

/**
 * Interface for models that support embedding generation.
 *
 * @since n.e.x.t
 */
interface EmbeddingGenerationModelInterface
{
    /**
     * Generates embeddings from one or more inputs.
     *
     * @since n.e.x.t
     *
     * @param list<string> $inputs Text inputs to embed.
     * @return EmbeddingResult Result containing generated embedding vectors.
     */
    public function generateEmbeddingResult(array $inputs): EmbeddingResult;
}

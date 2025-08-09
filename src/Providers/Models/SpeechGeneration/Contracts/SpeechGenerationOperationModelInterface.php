<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Operations\DTO\GenerativeAiOperation;

/**
 * Interface for models that support asynchronous speech generation operations.
 *
 * Provides methods for initiating long-running speech generation tasks.
 *
 * @since n.e.x.t
 */
interface SpeechGenerationOperationModelInterface
{
    /**
     * Creates a speech generation operation.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt Array of messages containing the speech generation prompt.
     * @return GenerativeAiOperation The initiated speech generation operation.
     */
    public function generateSpeechOperation(array $prompt): GenerativeAiOperation;
}

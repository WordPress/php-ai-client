<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\SoundGeneration\Contracts;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Operations\DTO\GenerativeAiOperation;

/**
 * Interface for models that support asynchronous sound generation operations.
 *
 * Provides methods for initiating long-running sound generation tasks.
 *
 * @since 1.4.0
 */
interface SoundGenerationOperationModelInterface
{
    /**
     * Creates a sound generation operation.
     *
     * @since 1.4.0
     *
     * @param list<Message> $prompt Array of messages containing the sound generation prompt.
     * @return GenerativeAiOperation The initiated sound generation operation.
     */
    public function generateSoundOperation(array $prompt): GenerativeAiOperation;
}

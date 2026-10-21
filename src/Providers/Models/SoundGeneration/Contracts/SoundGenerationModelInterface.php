<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\SoundGeneration\Contracts;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Interface for models that support sound generation.
 *
 * Provides synchronous methods for generating sounds from prompts.
 *
 * @since 1.4.0
 */
interface SoundGenerationModelInterface
{
    /**
     * Generates sound from a prompt.
     *
     * @since 1.4.0
     *
     * @param list<Message> $prompt Array of messages containing the sound generation prompt.
     * @return GenerativeAiResult Result containing generated sound audio.
     */
    public function generateSoundResult(array $prompt): GenerativeAiResult;
}

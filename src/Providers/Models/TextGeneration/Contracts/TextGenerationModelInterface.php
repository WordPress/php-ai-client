<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\TextGeneration\Contracts;

use Generator;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Interface for models that support text generation.
 *
 * Provides synchronous and streaming methods for generating text from prompts.
 *
 * @since n.e.x.t
 */
interface TextGenerationModelInterface
{
    /**
     * Generates text from a prompt.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt Array of messages containing the text generation prompt.
     * @return GenerativeAiResult Result containing generated text.
     */
    public function generateTextResult(array $prompt): GenerativeAiResult;

    /**
     * Streams text generation from a prompt.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt Array of messages containing the text generation prompt.
     * @return Generator<GenerativeAiResult> Generator yielding partial results.
     */
    public function streamGenerateTextResult(array $prompt): Generator;
}

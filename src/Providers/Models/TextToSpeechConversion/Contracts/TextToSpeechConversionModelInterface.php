<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Interface for models that support text-to-speech conversion.
 *
 * Provides synchronous methods for converting text to speech audio.
 *
 * @since n.e.x.t
 */
interface TextToSpeechConversionModelInterface
{
    /**
     * Converts text to speech.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt Array of messages containing the text to convert to speech.
     * @return GenerativeAiResult Result containing generated speech audio.
     */
    public function convertTextToSpeechResult(array $prompt): GenerativeAiResult;
}

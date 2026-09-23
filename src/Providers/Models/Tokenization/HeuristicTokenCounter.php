<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Tokenization;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Tokenization\Contracts\TokenCounterInterface;

/**
 * Rough, provider-agnostic token counter used as the SDK default.
 *
 * This counter approximates token usage by dividing the total number of characters in the text
 * parts of the messages by a fixed characters-per-token ratio. It is intentionally simple and
 * dependency free, and it is NOT accurate: real tokenizers vary by model, language, and content
 * (code, non-English text, and markup all tokenize differently). It is meant only to catch grossly
 * oversized prompts as a best-effort safeguard. For accurate counts, provide a custom
 * {@see TokenCounterInterface} implementation via
 * {@see \WordPress\AiClient\Builders\PromptBuilder::usingTokenCounter()}.
 *
 * Non-text parts (files, function calls, and function responses) are not counted.
 *
 * @since n.e.x.t
 */
final class HeuristicTokenCounter implements TokenCounterInterface
{
    /**
     * Approximate number of characters per token for English-like text.
     *
     * @since n.e.x.t
     *
     * @var int
     */
    private const CHARACTERS_PER_TOKEN = 4;

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to estimate a token count for.
     * @param ModelMetadata $modelMetadata The metadata of the model the messages are intended for.
     * @return int The estimated number of tokens. Never negative.
     */
    public function countTokens(array $messages, ModelMetadata $modelMetadata): int
    {
        $characters = 0;

        foreach ($messages as $message) {
            foreach ($message->getParts() as $part) {
                if (!$part->getType()->isText()) {
                    continue;
                }

                $text = $part->getText();
                if ($text === null) {
                    continue;
                }

                $characters += strlen($text);
            }
        }

        if ($characters === 0) {
            return 0;
        }

        return (int) ceil($characters / self::CHARACTERS_PER_TOKEN);
    }
}

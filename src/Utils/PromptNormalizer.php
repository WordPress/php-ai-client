<?php

declare(strict_types=1);

namespace WordPress\AiClient\Utils;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;

/**
 * Utility class for normalizing various prompt formats into standardized Message arrays.
 *
 * @since n.e.x.t
 */
class PromptNormalizer
{
    /**
     * Normalizes various prompt formats into a standardized Message array.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|Message|list<string|MessagePart|Message> $prompt The prompt content in various formats.
     * @return list<Message> Array of Message objects.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     */
    public static function normalize($prompt): array
    {
        // Normalize to array first for consistent processing
        if (!is_array($prompt)) {
            $prompt = [$prompt];
        }

        // Empty array check
        if (empty($prompt)) {
            throw new \InvalidArgumentException('Prompt array cannot be empty');
        }

        // Process each item individually
        $messages = [];
        foreach ($prompt as $index => $item) {
            $messages[] = self::normalizeItem($item, $index);
        }

        return $messages;
    }

    /**
     * Normalizes a single prompt item to a Message.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|Message $item The prompt item to normalize.
     * @param int $index The array index for error reporting.
     * @return Message The normalized message.
     *
     * @throws \InvalidArgumentException If the item format is invalid.
     */
    private static function normalizeItem($item, int $index): Message
    {
        if (is_string($item)) {
            return new UserMessage([new MessagePart($item)]);
        }

        if ($item instanceof MessagePart) {
            return new UserMessage([$item]);
        }

        if ($item instanceof Message) {
            return $item;
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Array element at index %d must be a string, MessagePart, or Message, %s given',
                $index,
                gettype($item)
            )
        );
    }
}

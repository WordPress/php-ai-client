<?php

declare(strict_types=1);

namespace WordPress\AiClient\Common\Utilities;

use InvalidArgumentException;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;

/**
 * Utility class for handling prompts.
 *
 * Provides methods for normalizing various prompt formats into a standard list of messages.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type MessageArrayShape from Message
 * @phpstan-import-type MessagePartArrayShape from MessagePart
 */
class Prompts
{
    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * Normalizes various prompt formats into a list of messages.
     *
     * Accepts strings, MessageParts, Messages, MessageArrayShapes, or lists of strings/MessageParts/MessagePartArrayShapes.
     * Returns a list of Message objects suitable for use with AI models.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|Message|MessageArrayShape|list<string|MessagePart|MessagePartArrayShape>|list<Message> $prompt The prompt to normalize.
     * @return list<Message> The normalized list of messages.
     * @throws InvalidArgumentException If the prompt format is invalid.
     */
    // phpcs:enable Generic.Files.LineLength.TooLong
    public static function normalizeToMessages($prompt): array
    {
        // 1. Check if it's already a list of Messages
        if (self::isMessagesList($prompt)) {
            return $prompt;
        }

        // 2. Check if it's a single Message
        if ($prompt instanceof Message) {
            return [$prompt];
        }

        // 3. Check if it's a MessageArrayShape (single message as array)
        if (self::isMessageArrayShape($prompt)) {
            return [Message::fromArray($prompt)];
        }

        // 4. If it's not an array, wrap it in an array
        if (!is_array($prompt)) {
            $prompt = [$prompt];
        }

        // 5. Loop through the array and handle conversions - all become parts of a single UserMessage
        $parts = [];

        foreach ($prompt as $item) {
            if (is_string($item)) {
                $parts[] = new MessagePart($item);
            } elseif ($item instanceof MessagePart) {
                $parts[] = $item;
            } elseif (self::isMessagePartArrayShape($item)) {
                $parts[] = MessagePart::fromArray($item);
            } else {
                $type = is_object($item) ? get_class($item) : gettype($item);
                throw new InvalidArgumentException(
                    sprintf('Invalid item type %s in prompt.', $type)
                );
            }
        }

        return [new UserMessage($parts)];
    }

    /**
     * Checks if the value is a list of Message objects.
     *
     * @since n.e.x.t
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is a list of Message objects.
     *
     * @phpstan-assert-if-true list<Message> $value
     */
    public static function isMessagesList($value): bool
    {
        if (!is_array($value) || empty($value) || !array_is_list($value)) {
            return false;
        }

        // Check if all items are Messages
        foreach ($value as $item) {
            if (!($item instanceof Message)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the value is a MessageArrayShape.
     *
     * @since n.e.x.t
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is a MessageArrayShape.
     *
     * @phpstan-assert-if-true MessageArrayShape $value
     */
    public static function isMessageArrayShape($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        // Must have required keys
        if (!isset($value['role']) || !isset($value['parts'])) {
            return false;
        }

        // Role must be a string
        if (!is_string($value['role'])) {
            return false;
        }

        // Parts must be an array
        if (!is_array($value['parts'])) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the value is a MessagePartArrayShape.
     *
     * @since n.e.x.t
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is a MessagePartArrayShape.
     *
     * @phpstan-assert-if-true MessagePartArrayShape $value
     */
    public static function isMessagePartArrayShape($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        // Channel is optional but if present must be a string
        if (isset($value['channel']) && !is_string($value['channel'])) {
            return false;
        }

        // Must have exactly one of the content fields: text, file, functionCall, or functionResponse
        // This matches the logic in MessagePart::fromArray()
        $contentFields = [
            isset($value['text']),
            isset($value['file']),
            isset($value['functionCall']),
            isset($value['functionResponse'])
        ];

        // Count how many are true - must be exactly 1
        return count(array_filter($contentFields)) === 1;
    }
}

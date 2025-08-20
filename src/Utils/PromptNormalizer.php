<?php

declare(strict_types=1);

namespace WordPress\AiClient\Utils;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;

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
     * Supports:
     * - Strings: converted to UserMessage with MessagePart
     * - MessagePart: wrapped in UserMessage
     * - Message: used directly
     * - Structured arrays: {'role': 'system', 'parts': [...]} format with role mapping
     * - Arrays of any combination of the above
     *
     * @since n.e.x.t
     *
     * @param mixed $prompt The prompt content in various formats.
     * @return list<Message> Array of Message objects.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     */
    public static function normalize($prompt): array
    {
        // Handle structured message arrays at the top level
        if (is_array($prompt) && self::isStructuredMessageArray($prompt)) {
            return [self::normalizeStructuredMessage($prompt, 0)];
        }

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
            $messages[] = self::normalizeItem($item, is_int($index) ? $index : 0);
        }

        return $messages;
    }

    /**
     * Normalizes a single prompt item to a Message.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|Message|array<string,mixed> $item The prompt item to normalize.
     * @param int $index The array index for error reporting.
     * @return Message The normalized message.
     *
     * @throws \InvalidArgumentException If the item format is invalid.
     */
    private static function normalizeItem($item, int $index): Message
    {
        // Handle Message objects
        if ($item instanceof Message) {
            return $item;
        }

        // Handle structured message arrays: {'role': 'system', 'parts': [...]}
        if (is_array($item) && self::isStructuredMessageArray($item)) {
            return self::normalizeStructuredMessage($item, $index);
        }

        // Handle strings - convert to user message
        if (is_string($item)) {
            return new UserMessage([new MessagePart($item)]);
        }

        // Handle MessagePart objects - wrap in user message
        if ($item instanceof MessagePart) {
            return new UserMessage([$item]);
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Array element at index %d must be a string, MessagePart, Message, or ' .
                'structured message array, %s given',
                $index,
                is_array($item) ? 'invalid array format' : gettype($item)
            )
        );
    }

    /**
     * Checks if an array is a structured message format.
     *
     * @since n.e.x.t
     *
     * @param array<string,mixed> $item The array to check.
     * @return bool True if it's a structured message array.
     */
    private static function isStructuredMessageArray(array $item): bool
    {
        return isset($item['role']);
    }

    /**
     * Normalizes a structured message array using Message::fromArray() with role mapping.
     *
     * @since n.e.x.t
     *
     * @param array<string,mixed> $item The structured message array.
     * @param int $index The array index for error reporting.
     * @return Message The normalized message.
     *
     * @throws \InvalidArgumentException If the structured format is invalid.
     */
    private static function normalizeStructuredMessage(array $item, int $index): Message
    {
        // Validate required keys
        if (!isset($item['parts'])) {
            throw new \InvalidArgumentException(
                sprintf('Structured message at index %d is missing required "parts" field', $index)
            );
        }

        // Map role to standard format and let Message::fromArray handle the rest
        $normalizedArray = [
            Message::KEY_ROLE => self::mapRole($item['role'], $index),
            Message::KEY_PARTS => self::normalizeParts($item['parts'], $index),
        ];

        try {
            return Message::fromArray($normalizedArray);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                sprintf('Invalid structured message at index %d: %s', $index, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Maps role strings to MessageRoleEnum values with support for common aliases.
     *
     * @since n.e.x.t
     *
     * @param mixed $role The role value to map.
     * @param int $index The array index for error reporting.
     * @return string The mapped role value.
     *
     * @throws \InvalidArgumentException If the role is invalid.
     */
    private static function mapRole($role, int $index): string
    {
        if (!is_string($role)) {
            throw new \InvalidArgumentException(
                sprintf('Role at index %d must be a string, %s given', $index, gettype($role))
            );
        }

        // Map common role aliases to standard enum values
        switch (strtolower($role)) {
            case 'system':
                return MessageRoleEnum::system()->value;
            case 'user':
                return MessageRoleEnum::user()->value;
            case 'model':
            case 'assistant':
                return MessageRoleEnum::model()->value;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid role "%s" at index %d. Must be "system", "user", "model", or "assistant"',
                        $role,
                        $index
                    )
                );
        }
    }

    /**
     * Normalizes parts array for Message::fromArray().
     *
     * @since n.e.x.t
     *
     * @param mixed $parts The parts to normalize.
     * @param int $index The array index for error reporting.
     * @return list<array<string,mixed>|string> The normalized parts.
     *
     * @throws \InvalidArgumentException If the parts format is invalid.
     */
    private static function normalizeParts($parts, int $index): array
    {
        if (!is_array($parts)) {
            throw new \InvalidArgumentException(
                sprintf('Parts at index %d must be an array, %s given', $index, gettype($parts))
            );
        }

        $normalizedParts = [];
        foreach ($parts as $partIndex => $part) {
            if (is_string($part)) {
                // Simple text part - Message::fromArray will handle it
                $normalizedParts[] = [MessagePart::KEY_TEXT => $part];
            } elseif ($part instanceof MessagePart) {
                // Convert MessagePart to array for Message::fromArray
                $normalizedParts[] = $part->toArray();
            } elseif (is_array($part)) {
                // Assume it's already in the correct format for MessagePart::fromArray
                $normalizedParts[] = $part;
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Part at index %d[%d] must be a string, MessagePart, or array, %s given',
                        $index,
                        $partIndex,
                        gettype($part)
                    )
                );
            }
        }

        return $normalizedParts;
    }
}

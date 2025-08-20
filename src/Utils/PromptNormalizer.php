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
        if (is_array($prompt) && self::hasStringKeys($prompt) && self::isStructuredMessageArray($prompt)) {
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
     * @param mixed $item The prompt item to normalize.
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
        if (is_array($item) && self::hasStringKeys($item) && self::isStructuredMessageArray($item)) {
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
     * Normalizes a structured message array by creating Message directly.
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

        // Map role and create message parts
        $role = self::mapRoleToEnum($item['role'], $index);
        $parts = self::createMessageParts($item['parts'], $index);

        return new Message($role, $parts);
    }

    /**
     * Maps role strings to MessageRoleEnum instances with support for common aliases.
     *
     * @since n.e.x.t
     *
     * @param mixed $role The role value to map.
     * @param int $index The array index for error reporting.
     * @return MessageRoleEnum The mapped role enum.
     *
     * @throws \InvalidArgumentException If the role is invalid.
     */
    private static function mapRoleToEnum($role, int $index): MessageRoleEnum
    {
        if (!is_string($role)) {
            throw new \InvalidArgumentException(
                sprintf('Role at index %d must be a string, %s given', $index, gettype($role))
            );
        }

        // Map common role aliases to enum instances
        switch (strtolower($role)) {
            case 'system':
                return MessageRoleEnum::system();
            case 'user':
                return MessageRoleEnum::user();
            case 'model':
            case 'assistant':
                return MessageRoleEnum::model();
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
     * Creates MessagePart objects from various input formats.
     *
     * @since n.e.x.t
     *
     * @param mixed $parts The parts to create.
     * @param int $index The array index for error reporting.
     * @return list<MessagePart> The created message parts.
     *
     * @throws \InvalidArgumentException If the parts format is invalid.
     */
    private static function createMessageParts($parts, int $index): array
    {
        if (!is_array($parts)) {
            throw new \InvalidArgumentException(
                sprintf('Parts at index %d must be an array, %s given', $index, gettype($parts))
            );
        }

        $messageParts = [];
        foreach ($parts as $partIndex => $part) {
            if (is_string($part)) {
                $messageParts[] = new MessagePart($part);
            } elseif ($part instanceof MessagePart) {
                $messageParts[] = $part;
            } elseif (is_array($part)) {
                try {
                    $messageParts[] = MessagePart::fromArray($part);
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Invalid message part at index %d[%d]: %s',
                            $index,
                            $partIndex,
                            $e->getMessage()
                        ),
                        0,
                        $e
                    );
                }
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

        return $messageParts;
    }

    /**
     * Checks if an array has string keys (associative array).
     *
     * @since n.e.x.t
     *
     * @param array<mixed,mixed> $array The array to check.
     * @return bool True if the array has string keys.
     * @phpstan-assert-if-true array<string,mixed> $array
     */
    private static function hasStringKeys(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}

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
     * @param string|string[]|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content in various formats.
     * @return list<Message> Array of Message objects.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     */
    public static function normalize($prompt): array
    {
        // Handle string input
        if (is_string($prompt)) {
            return [new UserMessage([new MessagePart($prompt)])];
        }

        // Handle single MessagePart
        if ($prompt instanceof MessagePart) {
            return [new UserMessage([$prompt])];
        }

        // Handle single Message
        if ($prompt instanceof Message) {
            return [$prompt];
        }

        // Handle arrays
        if (is_array($prompt)) {
            // Empty array
            if (empty($prompt)) {
                throw new \InvalidArgumentException('Prompt array cannot be empty');
            }

            // Check first element to determine array type
            $firstElement = reset($prompt);

            // Array of Messages
            if ($firstElement instanceof Message) {
                // Validate all elements are Messages
                foreach ($prompt as $item) {
                    if (!$item instanceof Message) {
                        throw new \InvalidArgumentException(
                            'Array must contain only Message, MessagePart, or string objects'
                        );
                    }
                }
                /** @var Message[] $messages */
                $messages = $prompt;
                return array_values($messages);
            }

            // Array of MessageParts
            if ($firstElement instanceof MessagePart) {
                // Validate all elements are MessageParts
                foreach ($prompt as $item) {
                    if (!$item instanceof MessagePart) {
                        throw new \InvalidArgumentException(
                            'Array must contain only Message, MessagePart, or string objects'
                        );
                    }
                }
                // Convert each MessagePart to a UserMessage
                /** @var MessagePart[] $messageParts */
                $messageParts = $prompt;
                return array_values(array_map(fn(MessagePart $part) => new UserMessage([$part]), $messageParts));
            }

            // Array of strings
            if (is_string($firstElement)) {
                // Validate all elements are strings
                foreach ($prompt as $index => $item) {
                    if (!is_string($item)) {
                        throw new \InvalidArgumentException(
                            sprintf('Array element at index %d must be a string, %s given', $index, gettype($item))
                        );
                    }
                }
                // Convert each string to a UserMessage
                /** @var string[] $stringArray */
                $stringArray = $prompt;
                return array_values(array_map(
                    fn(string $text) => new UserMessage([new MessagePart($text)]),
                    $stringArray
                ));
            }

            // Invalid array content
            throw new \InvalidArgumentException('Array must contain only Message, MessagePart, or string objects');
        }

        // Unsupported type
        throw new \InvalidArgumentException('Invalid prompt format provided');
    }
}

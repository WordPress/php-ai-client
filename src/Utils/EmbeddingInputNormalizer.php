<?php

declare(strict_types=1);

namespace WordPress\AiClient\Utils;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;

/**
 * Utility class for normalizing embedding input data into standardized Message arrays.
 *
 * Handles the specific requirements for embedding generation input,
 * which can be either string arrays or other message formats.
 *
 * @since n.e.x.t
 */
class EmbeddingInputNormalizer
{
    /**
     * Normalizes embedding input into a standardized Message array.
     *
     * Handles both string arrays (common for embeddings) and other
     * message formats that can be processed by PromptNormalizer.
     *
     * @since n.e.x.t
     *
     * @param string[]|string|MessagePart|MessagePart[]|Message|Message[] $input The input data in various formats.
     * @return list<Message> Array of Message objects.
     *
     * @throws \InvalidArgumentException If the input format is invalid.
     */
    public static function normalize($input): array
    {
        // Handle string array input (most common for embeddings)
        if (is_array($input) && !empty($input) && is_string($input[0])) {
            /** @var string[] $stringArray */
            $stringArray = $input;
            return self::normalizeStringArray($stringArray);
        }

        // For all other formats, delegate to PromptNormalizer
        /** @var string|MessagePart|MessagePart[]|Message|Message[] $input */
        return PromptNormalizer::normalize($input);
    }

    /**
     * Normalizes a string array into Message objects.
     *
     * Each string becomes a UserMessage with a single MessagePart.
     *
     * @since n.e.x.t
     *
     * @param string[] $stringArray Array of strings to normalize.
     * @return list<Message> Array of Message objects.
     *
     * @throws \InvalidArgumentException If the array contains non-string elements.
     */
    private static function normalizeStringArray(array $stringArray): array
    {
        // Validate all elements are strings
        foreach ($stringArray as $index => $item) {
            if (!is_string($item)) {
                throw new \InvalidArgumentException(
                    sprintf('Array element at index %d must be a string, %s given', $index, gettype($item))
                );
            }
        }

        // Convert each string to a UserMessage
        $messages = array_map(
            fn(string $text) => new UserMessage([new MessagePart($text)]),
            $stringArray
        );

        return array_values($messages);
    }

    /**
     * Validates that input is suitable for embedding generation.
     *
     * @since n.e.x.t
     *
     * @param mixed $input The input to validate.
     * @return bool True if the input is valid for embedding generation.
     */
    public static function isValidEmbeddingInput($input): bool
    {
        try {
            /** @phpstan-ignore-next-line */
            self::normalize($input);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Gets the number of input items that will be processed for embedding generation.
     *
     * Useful for understanding how many embeddings will be generated.
     *
     * @since n.e.x.t
     *
     * @param string[]|string|MessagePart|MessagePart[]|Message|Message[] $input The input data.
     * @return int The number of items that will be processed.
     *
     * @throws \InvalidArgumentException If the input format is invalid.
     */
    public static function getInputCount($input): int
    {
        $normalizedMessages = self::normalize($input);
        return count($normalizedMessages);
    }
}

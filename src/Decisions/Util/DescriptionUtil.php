<?php

declare(strict_types=1);

namespace WordPress\AiClient\Decisions\Util;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Class with static utility methods to validate the instructions and descriptions in decision questions.
 *
 * The instructions state a question, and a description explains an option, a level, or what counts as true
 * or false. Each is either a sentence, or a JSON-compatible array for when structure makes it clearer, such
 * as `['what' => 'Payments and refunds.', 'not_for' => 'Questions about prices.']`. Providers whose APIs only
 * take text send a structured value as JSON.
 *
 * @since n.e.x.t
 */
class DescriptionUtil
{
    /**
     * Validates instructions or a description.
     *
     * @since n.e.x.t
     *
     * @param mixed $value The instructions or description to validate.
     * @param string $label The label of the value, for the error message.
     * @return string|array<mixed> The validated value.
     * @throws InvalidArgumentException If the value is not a non-empty string or a non-empty array of
     *                                  JSON-compatible values.
     */
    public static function validate($value, string $label)
    {
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        if (is_array($value) && $value !== [] && self::isJsonCompatible($value)) {
            return $value;
        }

        throw new InvalidArgumentException(
            sprintf(
                '%s must be a non-empty string, or a non-empty array of strings, numbers, booleans, null, '
                . 'and arrays.',
                $label
            )
        );
    }

    /**
     * Checks whether every value in an array is JSON-compatible.
     *
     * @since n.e.x.t
     *
     * @param array<mixed> $values The values to check.
     * @return bool True if every value is a string, a finite number, a boolean, null, or such an array.
     */
    private static function isJsonCompatible(array $values): bool
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                if (!self::isJsonCompatible($value)) {
                    return false;
                }
            } elseif ((is_float($value) && !is_finite($value)) || ($value !== null && !is_scalar($value))) {
                return false;
            }
        }

        return true;
    }
}

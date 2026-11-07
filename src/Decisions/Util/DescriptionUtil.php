<?php

declare(strict_types=1);

namespace WordPress\AiClient\Decisions\Util;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Class with static utility methods to validate the descriptions in decision questions.
 *
 * A description explains an option, a level, or what counts as true or false. It is either a sentence, or
 * a JSON-compatible array for when structure makes it clearer, such as
 * `['what' => 'Payments and refunds.', 'not_for' => 'Questions about prices.']`. Providers whose APIs only
 * take text send a structured description as JSON.
 *
 * @since n.e.x.t
 */
class DescriptionUtil
{
    /**
     * Validates a description.
     *
     * @since n.e.x.t
     *
     * @param mixed $description The description to validate.
     * @param string $label The label of the description, for the error message.
     * @return string|array<mixed> The validated description.
     * @throws InvalidArgumentException If the description is not a non-empty string or a non-empty array of
     *                                  JSON-compatible values.
     */
    public static function validate($description, string $label)
    {
        if (is_string($description) && trim($description) !== '') {
            return $description;
        }

        if (is_array($description) && $description !== [] && self::isJsonCompatible($description)) {
            return $description;
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

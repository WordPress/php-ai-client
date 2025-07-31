<?php

/**
 * Polyfills for PHP functions that may not be available in older versions.
 *
 * @since n.e.x.t
 */

declare(strict_types=1);

if (!function_exists('array_is_list')) {
    /**
     * Checks whether a given array is a list.
     *
     * An array is considered a list if its keys consist of consecutive numbers from 0 to count($array)-1.
     *
     * @param array<mixed> $array The array to check.
     * @return bool True if the array is a list, false otherwise.
     *
     * @since n.e.x.t
     */
    function array_is_list(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        $expectedKey = 0;
        foreach (array_keys($array) as $key) {
            if ($key !== $expectedKey) {
                return false;
            }
            $expectedKey++;
        }

        return true;
    }
}

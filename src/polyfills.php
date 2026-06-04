<?php

/**
 * Polyfills for PHP functions that may not be available in older versions.
 *
 * @since 0.1.0
 */

declare(strict_types=1);

// PHP 7.4.0–7.4.11 have a covariant return type bug (https://bugs.php.net/bug.php?id=80126)
// that causes a fatal error with the DTO inheritance pattern used throughout this library.
// Warn early so the problem surfaces as a clear message rather than a cryptic fatal.
if (PHP_VERSION_ID >= 70400 && PHP_VERSION_ID < 70412) {
    trigger_error(
        sprintf(
            'PHP AI Client requires PHP 7.4.12 or later. You are running PHP %s, which has a known'
            . ' covariant return type bug (https://bugs.php.net/bug.php?id=80126) that will cause'
            . ' fatal errors with this library. Please upgrade to PHP 7.4.12 or later.',
            PHP_VERSION
        ),
        E_USER_WARNING
    );
}

if (!function_exists('array_is_list')) {
    /**
     * Checks whether a given array is a list.
     *
     * An array is considered a list if its keys consist of consecutive numbers from 0 to count($array)-1.
     *
     * @since 0.1.0
     *
     * @param array<mixed> $array The array to check.
     * @return bool True if the array is a list, false otherwise.
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

if (!function_exists('str_starts_with')) {
    /**
     * Checks if a string starts with a given substring.
     *
     * @since 0.1.0
     *
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for.
     * @return bool True if $haystack starts with $needle, false otherwise.
     */
    function str_starts_with(string $haystack, string $needle): bool
    {
        if ('' === $needle) {
            return true;
        }

        return 0 === strpos($haystack, $needle);
    }
}

if (!function_exists('str_contains')) {
    /**
     * Checks if a string contains a given substring.
     *
     * @since 0.1.0
     *
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for.
     * @return bool True if $haystack contains $needle, false otherwise.
     */
    function str_contains(string $haystack, string $needle): bool
    {
        if ('' === $needle) {
            return true;
        }

        return false !== strpos($haystack, $needle);
    }
}

if (!function_exists('str_ends_with')) {
    /**
     * Checks if a string ends with a given substring.
     *
     * @since 0.1.0
     *
     * @param string $haystack The string to search in.
     * @param string $needle The substring to search for.
     * @return bool True if $haystack ends with $needle, false otherwise.
     */
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ('' === $haystack) {
            return '' === $needle;
        }

        $len = strlen($needle);

        return substr($haystack, -$len, $len) === $needle;
    }
}

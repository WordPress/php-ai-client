<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Utilities;

/**
 * Utility for extracting error messages from API response data.
 *
 * Centralizes the logic for parsing common API error response formats
 * to avoid code duplication across exception classes.
 *
 * @since 0.2.0
 */
class ErrorMessageExtractor
{
    /**
     * Extracts error message from API response data.
     *
     * Handles common error response formats:
     * - { "error": { "message": "Error text" } }
     * - { "error": "Error text" }
     * - { "message": "Error text" }
     *
     * @since 0.2.0
     *
     * @param mixed $data The response data to extract error message from.
     * @return string|null The extracted error message, or null if none found.
     */
    public static function extractFromResponseData($data): ?string
    {
        if (!is_array($data)) {
            return null;
        }

        // Handle { "error": { "message": "Error text" } }
        if (
            isset($data['error']) &&
            is_array($data['error']) &&
            isset($data['error']['message']) &&
            is_string($data['error']['message'])
        ) {
            return $data['error']['message'];
        }

        // Handle { "error": "Error text" }
        if (isset($data['error']) && is_string($data['error'])) {
            return $data['error'];
        }

        // Handle { "message": "Error text" }
        if (isset($data['message']) && is_string($data['message'])) {
            return $data['message'];
        }

        return null;
    }
}

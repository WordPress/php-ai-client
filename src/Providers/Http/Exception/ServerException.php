<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Exception;

use WordPress\AiClient\Providers\Http\DTO\Response;

/**
 * Exception thrown for 5xx HTTP server errors.
 *
 * This represents errors where the server failed to fulfill
 * a valid request due to internal server errors.
 *
 * @since n.e.x.t
 */
class ServerException extends RequestException
{
    /**
     * Creates a ServerException from a server error response.
     *
     * This method extracts error details from common API response formats
     * and creates an exception with a descriptive message and status code.
     *
     * @since n.e.x.t
     *
     * @param Response $response The HTTP response that failed.
     * @return self
     */
    public static function fromServerError(Response $response): self
    {
        $errorMessage = sprintf(
            'Server error (%d): Request failed due to server-side issue',
            $response->getStatusCode()
        );

        // Handle common error formats in API responses
        $data = $response->getData();
        if (
            is_array($data) &&
            isset($data['error']) &&
            is_array($data['error']) &&
            isset($data['error']['message']) &&
            is_string($data['error']['message'])
        ) {
            $errorMessage .= ' - ' . $data['error']['message'];
        } elseif (
            is_array($data) &&
            isset($data['error']) &&
            is_string($data['error'])
        ) {
            $errorMessage .= ' - ' . $data['error'];
        } elseif (
            is_array($data) &&
            isset($data['message']) &&
            is_string($data['message'])
        ) {
            $errorMessage .= ' - ' . $data['message'];
        }

        return new self($errorMessage, $response->getStatusCode());
    }
}

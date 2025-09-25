<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Exception;

use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Utilities\ErrorMessageExtractor;

/**
 * Exception thrown for 5xx HTTP server errors.
 *
 * This represents errors where the server failed to fulfill
 * a valid request due to internal server errors.
 *
 * @since n.e.x.t
 */
class ServerException extends RuntimeException
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

        // Extract error message from response data using centralized utility
        $extractedError = ErrorMessageExtractor::extractFromResponseData($response->getData());
        if ($extractedError !== null) {
            $errorMessage .= ' - ' . $extractedError;
        }

        return new self($errorMessage, $response->getStatusCode());
    }
}

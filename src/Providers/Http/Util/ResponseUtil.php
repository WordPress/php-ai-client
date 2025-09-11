<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Util;

use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Exception\ServerException;

/**
 * Class with static utility methods to process HTTP responses.
 *
 * @since 0.1.0
 */
class ResponseUtil
{
    /**
     * Throws an appropriate exception if the given response is not successful.
     *
     * This method checks the HTTP status code of the response and throws
     * the appropriate exception type based on the status code range:
     * - 4xx: ClientException (client errors)
     * - 5xx: ServerException (server errors)
     * - Other unsuccessful responses: ResponseException (malformed responses)
     *
     * @since 0.1.0
     *
     * @param Response $response The HTTP response to check.
     * @throws ClientException If the response indicates a client error (4xx).
     * @throws ServerException If the response indicates a server error (5xx).
     * @throws ResponseException If the response format is unexpected.
     */
    public static function throwIfNotSuccessful(Response $response): void
    {
        if ($response->isSuccessful()) {
            return;
        }

        $statusCode = $response->getStatusCode();

        // 4xx Client Errors
        if ($statusCode >= 400 && $statusCode < 500) {
            // Special handling for 400 Bad Request
            if ($statusCode === 400) {
                $body = (string) $response->getBody();
                $errorDetail = $body ? substr($body, 0, 200) : 'Invalid request parameters';
                throw ClientException::fromBadRequestResponse($errorDetail);
            }
            // General 4xx client errors
            throw ClientException::fromClientError($response);
        }

        // 5xx Server Errors
        if ($statusCode >= 500 && $statusCode < 600) {
            throw ServerException::fromServerError($response);
        }

        // Other unsuccessful responses (3xx redirects, etc.) - these should be rare
        // as most HTTP clients handle redirects automatically
        throw ResponseException::fromBadResponse($response);
    }
}

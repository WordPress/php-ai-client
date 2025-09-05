<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Exception;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Exception thrown for AI API request errors due to bad request data.
 *
 * This includes malformed requests, invalid parameters, and scenarios
 * where the API responds with a 400 Bad Request status code indicating
 * that our code didn't catch an invalid argument but the API did.
 *
 * @since n.e.x.t
 */
class RequestException extends InvalidArgumentException
{
    /**
     * Creates a RequestException from a bad request to a specific URI.
     *
     * @since n.e.x.t
     *
     * @param string $uri The URI that was requested.
     * @param string $errorDetail Details about what made the request bad.
     * @return self
     */
    public static function fromBadRequestToUri(string $uri, string $errorDetail = 'Invalid request parameters'): self
    {
        return new self(sprintf('Bad request to %s (400): %s', $uri, $errorDetail));
    }
}

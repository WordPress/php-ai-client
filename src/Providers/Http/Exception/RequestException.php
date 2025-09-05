<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Exception;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\Http\DTO\Response;

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
     * Creates a RequestException for invalid API parameters.
     *
     * @since n.e.x.t
     *
     * @param string $apiName The name of the API/provider.
     * @param string $paramName The parameter that was invalid.
     * @param string $message Additional error message.
     * @return self
     */
    public static function fromInvalidParam(string $apiName, string $paramName, string $message = ''): self
    {
        $errorMessage = sprintf('Invalid parameter "%s" for %s API', $paramName, $apiName);
        if ($message !== '') {
            $errorMessage .= ': ' . $message;
        }

        return new self($errorMessage);
    }

    /**
     * Creates a RequestException from a 400 Bad Request API response.
     *
     * @since n.e.x.t
     *
     * @param string $apiName The name of the API/provider.
     * @param Response $response The HTTP response containing the error.
     * @return self
     */
    public static function fromBadRequestResponse(string $apiName, Response $response): self
    {
        $body = $response->getBody();
        $errorDetail = $body ? substr($body, 0, 200) : 'Invalid request parameters';

        $message = sprintf(
            'Bad request to %s API (400): %s',
            $apiName,
            $errorDetail
        );

        return new self($message);
    }

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

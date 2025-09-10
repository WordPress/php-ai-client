<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Exception;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
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
class RequestException extends InvalidArgumentException implements RequestExceptionInterface
{
    /**
     * The request that failed.
     *
     * @var RequestInterface|null
     */
    private ?RequestInterface $request = null;

    /**
     * Creates a RequestException from a bad request.
     *
     * @since n.e.x.t
     *
     * @param RequestInterface $request The request that failed.
     * @param string $errorDetail Details about what made the request bad.
     * @return self
     */
    public static function fromBadRequest(
        RequestInterface $request,
        string $errorDetail = 'Invalid request parameters'
    ): self {
        $message = sprintf('Bad request to %s (400): %s', (string) $request->getUri(), $errorDetail);

        $exception = new self($message);
        $exception->request = $request;
        return $exception;
    }

    /**
     * Creates a RequestException from a bad request to a specific URI.
     *
     * @since n.e.x.t
     *
     * @param string $uri The URI that was requested.
     * @param string $errorDetail Details about what made the request bad.
     * @return self
     *
     * @deprecated Use fromBadRequest() with RequestInterface for PSR-18 compliance
     */
    public static function fromBadRequestToUri(string $uri, string $errorDetail = 'Invalid request parameters'): self
    {
        return new self(sprintf('Bad request to %s (400): %s', $uri, $errorDetail));
    }

    /**
     * Returns the request that failed.
     *
     * @since n.e.x.t
     *
     * @return RequestInterface
     * @throws \RuntimeException If no request is available (when using deprecated fromBadRequestToUri)
     */
    public function getRequest(): RequestInterface
    {
        if ($this->request === null) {
            throw new \RuntimeException(
                'Request object not available. This exception was created using the deprecated ' .
                'fromBadRequestToUri() method. Use fromBadRequest() instead for PSR-18 compliance.'
            );
        }

        return $this->request;
    }
}

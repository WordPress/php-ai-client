<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Exception;

use Psr\Http\Message\RequestInterface;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Utilities\ErrorMessageExtractor;

/**
 * Exception thrown for 4xx HTTP client errors.
 *
 * This represents errors where the client request was malformed,
 * unauthorized, forbidden, or otherwise invalid.
 *
 * @since n.e.x.t
 */
class ClientException extends InvalidArgumentException
{
    /**
     * The request that failed.
     *
     * @var Request|null
     */
    protected ?Request $request = null;

    /**
     * Returns the request that failed as our Request DTO.
     *
     * @since n.e.x.t
     *
     * @return Request
     * @throws \RuntimeException If no request is available
     */
    public function getRequest(): Request
    {
        if ($this->request === null) {
            throw new \RuntimeException(
                'Request object not available. This exception was directly instantiated. ' .
                'Use a factory method that provides request context.'
            );
        }

        return $this->request;
    }

    /**
     * Creates a ClientException from a 400 Bad Request response.
     *
     * @since n.e.x.t
     *
     * @param string $errorDetail Details about what made the request bad.
     * @return self
     */
    public static function fromBadRequestResponse(string $errorDetail = 'Invalid request parameters'): self
    {
        $message = sprintf('Bad request (400): %s', $errorDetail);
        return new self($message, 400);
    }

    /**
     * Creates a ClientException from a bad request.
     *
     * @since n.e.x.t
     *
     * @param RequestInterface $psrRequest The PSR-7 request that failed.
     * @param string $errorDetail Details about what made the request bad.
     * @return self
     */
    public static function fromBadRequest(
        RequestInterface $psrRequest,
        string $errorDetail = 'Invalid request parameters'
    ): self {
        $request = Request::fromPsrRequest($psrRequest);
        $message = sprintf('Bad request to %s (400): %s', $request->getUri(), $errorDetail);

        $exception = new self($message, 400);
        $exception->request = $request;
        return $exception;
    }

    /**
     * Creates a ClientException from a bad request to a specific URI.
     *
     * @since n.e.x.t
     *
     * @param string $uri The URI that was requested.
     * @param string $errorDetail Details about what made the request bad.
     * @return self
     *
     * @deprecated Use fromBadRequest() with RequestInterface for better type safety
     */
    public static function fromBadRequestToUri(string $uri, string $errorDetail = 'Invalid request parameters'): self
    {
        return new self(sprintf('Bad request to %s (400): %s', $uri, $errorDetail), 400);
    }

    /**
     * Creates a ClientException from a client error response (4xx).
     *
     * This method extracts error details from common API response formats
     * and creates an exception with a descriptive message and status code.
     *
     * @since n.e.x.t
     *
     * @param Response $response The HTTP response that failed.
     * @return self
     */
    public static function fromClientError(Response $response): self
    {
        $errorMessage = sprintf(
            'Client error (%d): Request was rejected due to client-side issue',
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

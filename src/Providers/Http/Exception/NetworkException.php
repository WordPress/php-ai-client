<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Exception;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use WordPress\AiClient\Common\Exception\RuntimeException;

/**
 * Exception thrown for network-related errors.
 *
 * This includes HTTP transport errors, connection failures,
 * timeouts, and other network-related issues.
 *
 * @since n.e.x.t
 */
class NetworkException extends RuntimeException implements NetworkExceptionInterface
{
    /**
     * The request that failed.
     *
     * @var RequestInterface|null
     */
    private ?RequestInterface $request = null;

    /**
     * Creates a NetworkException from a PSR-18 network exception.
     *
     * @since n.e.x.t
     *
     * @param RequestInterface $request The request that failed.
     * @param \Throwable $networkException The PSR-18 network exception.
     * @return self
     */
    public static function fromPsr18NetworkException(RequestInterface $request, \Throwable $networkException): self
    {
        $message = sprintf(
            'Network error occurred while sending request to %s: %s',
            (string) $request->getUri(),
            $networkException->getMessage()
        );

        $exception = new self($message, 0, $networkException);
        $exception->request = $request;
        return $exception;
    }

    /**
     * Returns the request that failed.
     *
     * @since n.e.x.t
     *
     * @return RequestInterface
     * @throws \RuntimeException If no request is available (when directly instantiated)
     */
    public function getRequest(): RequestInterface
    {
        if ($this->request === null) {
            throw new \RuntimeException(
                'Request object not available. This exception was directly instantiated. ' .
                'Use fromPsr18NetworkException() factory method for PSR-18 compliance.'
            );
        }

        return $this->request;
    }
}

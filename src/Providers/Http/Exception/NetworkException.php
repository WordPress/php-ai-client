<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Exception;

use WordPress\AiClient\Common\Exception\RuntimeException;

/**
 * Exception thrown for network-related errors.
 *
 * This includes HTTP transport errors, connection failures,
 * timeouts, and other network-related issues.
 *
 * @since n.e.x.t
 */
class NetworkException extends RuntimeException
{
    /**
     * Creates a NetworkException from a PSR-18 network exception.
     *
     * @since n.e.x.t
     *
     * @param string $uri The URI that was being requested.
     * @param \Throwable $networkException The PSR-18 network exception.
     * @return self
     */
    public static function fromPsr18NetworkException(string $uri, \Throwable $networkException): self
    {
        $message = sprintf(
            'Network error occurred while sending request to %s: %s',
            $uri,
            $networkException->getMessage()
        );

        return new self($message, 0, $networkException);
    }
}

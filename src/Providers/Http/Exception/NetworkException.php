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
     * Creates a NetworkException for connection failures.
     *
     * @since n.e.x.t
     *
     * @param string $uri The URI that failed to connect.
     * @param string $reason The reason for connection failure.
     * @param \Throwable|null $previous The underlying network exception.
     * @return self
     */
    public static function fromConnectionFailure(
        string $uri,
        string $reason = 'Connection failed',
        ?\Throwable $previous = null
    ): self {
        $message = sprintf('Network connection failed for %s: %s', $uri, $reason);

        return new self($message, 0, $previous);
    }

    /**
     * Creates a NetworkException for timeout errors.
     *
     * @since n.e.x.t
     *
     * @param string $uri The URI that timed out.
     * @param string $timeoutType Type of timeout (e.g., 'connection', 'read', 'request').
     * @param int|null $timeoutSeconds The timeout duration if known.
     * @param \Throwable|null $previous The underlying timeout exception.
     * @return self
     */
    public static function fromTimeout(
        string $uri,
        string $timeoutType = 'request',
        ?int $timeoutSeconds = null,
        ?\Throwable $previous = null
    ): self {
        $message = sprintf('Network %s timeout for %s', $timeoutType, $uri);
        if ($timeoutSeconds !== null) {
            $message .= sprintf(' (after %d seconds)', $timeoutSeconds);
        }

        return new self($message, 0, $previous);
    }

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

    /**
     * Creates a NetworkException for DNS resolution failures.
     *
     * @since n.e.x.t
     *
     * @param string $hostname The hostname that failed to resolve.
     * @param \Throwable|null $previous The underlying DNS exception.
     * @return self
     */
    public static function fromDnsFailure(string $hostname, ?\Throwable $previous = null): self
    {
        $message = sprintf('Failed to resolve hostname: %s', $hostname);

        return new self($message, 0, $previous);
    }

    /**
     * Creates a NetworkException for SSL/TLS errors.
     *
     * @since n.e.x.t
     *
     * @param string $uri The URI with SSL/TLS issues.
     * @param string $sslError Description of the SSL/TLS error.
     * @param \Throwable|null $previous The underlying SSL exception.
     * @return self
     */
    public static function fromSslError(string $uri, string $sslError, ?\Throwable $previous = null): self
    {
        $message = sprintf('SSL/TLS error for %s: %s', $uri, $sslError);

        return new self($message, 0, $previous);
    }
}

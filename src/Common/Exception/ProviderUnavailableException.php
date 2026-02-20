<?php

declare(strict_types=1);

namespace WordPress\AiClient\Common\Exception;

/**
 * Exception thrown when a provider is temporarily unavailable.
 *
 * Providers should throw this exception when the upstream API returns a
 * transient server error such as a 529 Overloaded, 500, 502, or 503 response,
 * indicating that the request may succeed if retried later.
 *
 * @since n.e.x.t
 */
class ProviderUnavailableException extends RuntimeException
{
    /**
     * The HTTP status code returned by the provider, if known.
     *
     * @since n.e.x.t
     *
     * @var int|null
     */
    private $httpStatusCode;

    /**
     * The error type returned by the provider API, if known.
     *
     * @since n.e.x.t
     *
     * @var string|null
     */
    private $errorType;

    /**
     * Creates a new ProviderUnavailableException.
     *
     * @since n.e.x.t
     *
     * @param string          $message        The exception message.
     * @param int|null        $httpStatusCode The HTTP status code returned by the provider, if known.
     * @param string|null     $errorType      The error type returned by the provider API, if known.
     * @param \Throwable|null $previous       The previous throwable used for exception chaining.
     */
    public function __construct(
        string $message = '',
        ?int $httpStatusCode = null,
        ?string $errorType = null,
        ?\Throwable $previous = null
        )
    {
        parent::__construct($message, 0, $previous);

        $this->httpStatusCode = $httpStatusCode;
        $this->errorType = $errorType;
    }

    /**
     * Returns the HTTP status code returned by the provider, if known.
     *
     * @since n.e.x.t
     *
     * @return int|null The HTTP status code, or null if not provided.
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * Returns the error type returned by the provider API, if known.
     *
     * @since n.e.x.t
     *
     * @return string|null The error type (e.g. "overloaded_error"), or null if not provided.
     */
    public function getErrorType(): ?string
    {
        return $this->errorType;
    }
}
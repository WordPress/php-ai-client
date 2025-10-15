<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Represents optional HTTP transport configuration for a single request.
 *
 * Provides immutable helpers for working with timeouts and redirect handling.
 *
 * @since n.e.x.t
 *
 * @phpstan-type RequestOptionsArrayShape array{
 *     timeout?: float|null,
 *     connectTimeout?: float|null,
 *     allowRedirects?: bool|null,
 *     maxRedirects?: int|null
 * }
 *
 * @extends AbstractDataTransferObject<RequestOptionsArrayShape>
 */
class RequestOptions extends AbstractDataTransferObject
{
    public const KEY_TIMEOUT = 'timeout';
    public const KEY_CONNECT_TIMEOUT = 'connectTimeout';
    public const KEY_ALLOW_REDIRECTS = 'allowRedirects';
    public const KEY_MAX_REDIRECTS = 'maxRedirects';

    /**
     * @var float|null Maximum duration in seconds to wait for the full response.
     */
    protected ?float $timeout;

    /**
     * @var float|null Maximum duration in seconds to wait for the initial connection.
     */
    protected ?float $connectTimeout;

    /**
     * @var bool|null Whether HTTP redirects should be automatically followed.
     */
    protected ?bool $allowRedirects;

    /**
     * @var int|null Maximum number of redirects to follow when enabled.
     */
    protected ?int $maxRedirects;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param float|null $timeout Maximum duration in seconds to wait for the response.
     * @param float|null $connectTimeout Maximum duration in seconds to wait for the connection.
     * @param bool|null $allowRedirects Whether redirects should be followed.
     * @param int|null $maxRedirects Maximum redirects to follow when enabled.
     *
     * @throws InvalidArgumentException When timeout values or redirect limits are invalid.
     */
    public function __construct(
        ?float $timeout = null,
        ?float $connectTimeout = null,
        ?bool $allowRedirects = null,
        ?int $maxRedirects = null
    ) {
        $this->validateTimeout($timeout, self::KEY_TIMEOUT);
        $this->validateTimeout($connectTimeout, self::KEY_CONNECT_TIMEOUT);
        $this->validateRedirectLimit($allowRedirects, $maxRedirects);

        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->allowRedirects = $allowRedirects;
        $this->maxRedirects = $allowRedirects === true ? $maxRedirects : null;
    }

    /**
     * Returns a new instance with an updated request timeout.
     *
     * @since n.e.x.t
     *
     * @param float|null $timeout Timeout in seconds.
     * @return self Options instance with updated timeout.
     */
    public function withTimeout(?float $timeout): self
    {
        $this->validateTimeout($timeout, self::KEY_TIMEOUT);

        $clone = clone $this;
        $clone->timeout = $timeout;
        return $clone;
    }

    /**
     * Returns a new instance with an updated connection timeout.
     *
     * @since n.e.x.t
     *
     * @param float|null $timeout Connection timeout in seconds.
     * @return self Options instance with updated connection timeout.
     */
    public function withConnectTimeout(?float $timeout): self
    {
        $this->validateTimeout($timeout, self::KEY_CONNECT_TIMEOUT);

        $clone = clone $this;
        $clone->connectTimeout = $timeout;
        return $clone;
    }

    /**
     * Returns a new instance with redirects enabled.
     *
     * @since n.e.x.t
     *
     * @param int|null $maxRedirects Maximum redirects to follow, or null to leave unspecified.
     * @return self Options instance with redirects enabled.
     */
    public function withRedirects(?int $maxRedirects = null): self
    {
        $limit = $maxRedirects ?? $this->maxRedirects;
        $this->validateRedirectLimit(true, $limit);

        $clone = clone $this;
        $clone->allowRedirects = true;
        $clone->maxRedirects = $limit;
        return $clone;
    }

    /**
     * Returns a new instance with redirects disabled.
     *
     * @since n.e.x.t
     *
     * @return self Options instance with redirects disabled.
     */
    public function withoutRedirects(): self
    {
        $clone = clone $this;
        $clone->allowRedirects = false;
        $clone->maxRedirects = null;
        return $clone;
    }

    /**
     * Returns a new instance with an updated redirect limit.
     *
     * @since n.e.x.t
     *
     * @param int|null $maxRedirects Maximum redirects to follow.
     * @return self Options instance with updated redirect limit.
     */
    public function withMaxRedirects(?int $maxRedirects): self
    {
        $this->validateRedirectLimit($this->allowRedirects, $maxRedirects);

        $clone = clone $this;

        if ($this->allowRedirects === true) {
            $clone->maxRedirects = $maxRedirects;
        } else {
            $clone->maxRedirects = null;
        }

        return $clone;
    }

    /**
     * Gets the request timeout in seconds.
     *
     * @since n.e.x.t
     *
     * @return float|null Timeout in seconds.
     */
    public function getTimeout(): ?float
    {
        return $this->timeout;
    }

    /**
     * Gets the connection timeout in seconds.
     *
     * @since n.e.x.t
     *
     * @return float|null Connection timeout in seconds.
     */
    public function getConnectTimeout(): ?float
    {
        return $this->connectTimeout;
    }

    /**
     * Checks whether redirects are allowed.
     *
     * @since n.e.x.t
     *
     * @return bool|null True when redirects are allowed, false when disabled, null when unspecified.
     */
    public function allowsRedirects(): ?bool
    {
        return $this->allowRedirects;
    }

    /**
     * Gets the maximum number of redirects to follow.
     *
     * @since n.e.x.t
     *
     * @return int|null Maximum redirects or null when not specified.
     */
    public function getMaxRedirects(): ?int
    {
        return $this->maxRedirects;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return RequestOptionsArrayShape
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->timeout !== null) {
            $data[self::KEY_TIMEOUT] = $this->timeout;
        }

        if ($this->connectTimeout !== null) {
            $data[self::KEY_CONNECT_TIMEOUT] = $this->connectTimeout;
        }

        if ($this->allowRedirects !== null) {
            $data[self::KEY_ALLOW_REDIRECTS] = $this->allowRedirects;
        }

        if ($this->maxRedirects !== null) {
            $data[self::KEY_MAX_REDIRECTS] = $this->maxRedirects;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        $timeout = $array[self::KEY_TIMEOUT] ?? null;
        $connectTimeout = $array[self::KEY_CONNECT_TIMEOUT] ?? null;
        $allowRedirects = $array[self::KEY_ALLOW_REDIRECTS] ?? null;
        $maxRedirects = $array[self::KEY_MAX_REDIRECTS] ?? null;

        return new self(
            $timeout !== null ? (float) $timeout : null,
            $connectTimeout !== null ? (float) $connectTimeout : null,
            $allowRedirects !== null ? (bool) $allowRedirects : null,
            $maxRedirects !== null ? (int) $maxRedirects : null
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                self::KEY_TIMEOUT => [
                    'type' => ['number', 'null'],
                    'minimum' => 0,
                    'description' => 'Maximum duration in seconds to wait for the full response.',
                ],
                self::KEY_CONNECT_TIMEOUT => [
                    'type' => ['number', 'null'],
                    'minimum' => 0,
                    'description' => 'Maximum duration in seconds to wait for the initial connection.',
                ],
                self::KEY_ALLOW_REDIRECTS => [
                    'type' => ['boolean', 'null'],
                    'description' => 'Whether HTTP redirects should be automatically followed.',
                ],
                self::KEY_MAX_REDIRECTS => [
                    'type' => ['integer', 'null'],
                    'minimum' => 0,
                    'description' => 'Maximum number of redirects to follow when enabled.',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    /**
     * Validates timeout values.
     *
     * @since n.e.x.t
     *
     * @param float|null $value Timeout to validate.
     * @param string $fieldName Field name for the error message.
     *
     * @throws InvalidArgumentException When timeout is negative.
     */
    private function validateTimeout(?float $value, string $fieldName): void
    {
        if ($value !== null && $value < 0) {
            throw new InvalidArgumentException(
                sprintf('Request option "%s" must be greater than or equal to 0.', $fieldName)
            );
        }
    }

    /**
     * Validates redirect configuration.
     *
     * @since n.e.x.t
     *
     * @param bool|null $allowRedirects Whether redirects are enabled.
     * @param int|null $maxRedirects Maximum redirects.
     *
     * @throws InvalidArgumentException When redirect count is invalid.
     */
    private function validateRedirectLimit(?bool $allowRedirects, ?int $maxRedirects): void
    {
        if ($allowRedirects === true) {
            if ($maxRedirects !== null && $maxRedirects < 0) {
                throw new InvalidArgumentException(
                    'Request option "maxRedirects" must be greater than or equal to 0 when redirects are enabled.'
                );
            }

            return;
        }

        if ($maxRedirects !== null) {
            throw new InvalidArgumentException(
                'Request option "maxRedirects" can only be set when redirects are enabled.'
            );
        }
    }
}

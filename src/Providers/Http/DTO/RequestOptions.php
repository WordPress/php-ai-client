<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Represents optional HTTP transport configuration for a single request.
 *
 * Provides mutable setters for working with timeouts and redirect handling.
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
    protected ?float $timeout = null;

    /**
     * @var float|null Maximum duration in seconds to wait for the initial connection.
     */
    protected ?float $connectTimeout = null;

    /**
     * @var bool|null Whether HTTP redirects should be automatically followed.
     */
    protected ?bool $allowRedirects = null;

    /**
     * @var int|null Maximum number of redirects to follow when enabled.
     */
    protected ?int $maxRedirects = null;

    /**
     * Sets the request timeout in seconds.
     *
     * @since n.e.x.t
     *
     * @param float|null $timeout Timeout in seconds.
     * @return void
     *
     * @throws InvalidArgumentException When timeout is negative.
     */
    public function setTimeout(?float $timeout): void
    {
        $this->validateTimeout($timeout, self::KEY_TIMEOUT);
        $this->timeout = $timeout;
    }

    /**
     * Sets the connection timeout in seconds.
     *
     * @since n.e.x.t
     *
     * @param float|null $timeout Connection timeout in seconds.
     * @return void
     *
     * @throws InvalidArgumentException When timeout is negative.
     */
    public function setConnectTimeout(?float $timeout): void
    {
        $this->validateTimeout($timeout, self::KEY_CONNECT_TIMEOUT);
        $this->connectTimeout = $timeout;
    }

    /**
     * Sets whether redirects should be automatically followed.
     *
     * @since n.e.x.t
     *
     * @param bool $allowRedirects Whether redirects should be followed.
     * @return void
     */
    public function setAllowRedirects(bool $allowRedirects): void
    {
        $this->allowRedirects = $allowRedirects;

        // Clear maxRedirects when disabling redirects
        if ($allowRedirects === false) {
            $this->maxRedirects = null;
        }
    }

    /**
     * Sets the maximum number of redirects to follow.
     *
     * @since n.e.x.t
     *
     * @param int|null $maxRedirects Maximum redirects to follow when enabled.
     * @return void
     *
     * @throws InvalidArgumentException When redirect count is invalid.
     */
    public function setMaxRedirects(?int $maxRedirects): void
    {
        $this->validateRedirectLimit($this->allowRedirects, $maxRedirects);

        if ($this->allowRedirects === true) {
            $this->maxRedirects = $maxRedirects;
        } else {
            $this->maxRedirects = null;
        }
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
        $instance = new self();

        if (isset($array[self::KEY_TIMEOUT])) {
            $instance->setTimeout((float) $array[self::KEY_TIMEOUT]);
        }

        if (isset($array[self::KEY_CONNECT_TIMEOUT])) {
            $instance->setConnectTimeout((float) $array[self::KEY_CONNECT_TIMEOUT]);
        }

        if (isset($array[self::KEY_ALLOW_REDIRECTS])) {
            $instance->setAllowRedirects((bool) $array[self::KEY_ALLOW_REDIRECTS]);
        }

        if (isset($array[self::KEY_MAX_REDIRECTS])) {
            $instance->setMaxRedirects((int) $array[self::KEY_MAX_REDIRECTS]);
        }

        return $instance;
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

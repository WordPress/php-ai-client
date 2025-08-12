<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataTransferObject;

/**
 * Represents an HTTP request.
 *
 * This class encapsulates HTTP request data that can be converted
 * to PSR-7 requests by the HTTP transporter.
 *
 * @since n.e.x.t
 *
 * @phpstan-type RequestArrayShape array{
 *     method: string,
 *     uri: string,
 *     headers: array<string, string|list<string>>,
 *     body?: string|null
 * }
 *
 * @extends AbstractDataTransferObject<RequestArrayShape>
 */
class Request extends AbstractDataTransferObject
{
    public const KEY_METHOD = 'method';
    public const KEY_URI = 'uri';
    public const KEY_HEADERS = 'headers';
    public const KEY_BODY = 'body';

    /**
     * @var string The HTTP method (GET, POST, etc.).
     */
    protected string $method;

    /**
     * @var string The request URI.
     */
    protected string $uri;

    /**
     * @var array<string, string|list<string>> The request headers.
     */
    protected array $headers;

    /**
     * @var string|null The request body.
     */
    protected ?string $body;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $method The HTTP method.
     * @param string $uri The request URI.
     * @param array<string, string|list<string>> $headers The request headers.
     * @param string|null $body The request body.
     *
     * @throws InvalidArgumentException If the method is empty.
     */
    public function __construct(string $method, string $uri, array $headers = [], ?string $body = null)
    {
        if (empty($method)) {
            throw new InvalidArgumentException('HTTP method cannot be empty.');
        }

        if (empty($uri)) {
            throw new InvalidArgumentException('URI cannot be empty.');
        }

        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * Gets the HTTP method.
     *
     * @since n.e.x.t
     *
     * @return string The HTTP method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Gets the request URI.
     *
     * @since n.e.x.t
     *
     * @return string The URI.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Gets the request headers.
     *
     * @since n.e.x.t
     *
     * @return array<string, string|list<string>> The headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Gets the request body.
     *
     * @since n.e.x.t
     *
     * @return string|null The body.
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Returns a new instance with the specified header.
     *
     * @since n.e.x.t
     *
     * @param string $name The header name.
     * @param string|list<string> $value The header value(s).
     * @return self A new instance with the header.
     */
    public function withHeader(string $name, $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;

        return new self($this->method, $this->uri, $headers, $this->body);
    }

    /**
     * Returns a new instance with the specified body.
     *
     * @since n.e.x.t
     *
     * @param string $body The request body.
     * @return self A new instance with the body.
     */
    public function withBody(string $body): self
    {
        return new self($this->method, $this->uri, $this->headers, $body);
    }

    /**
     * Gets the request data as an array.
     *
     * Attempts to decode the body as JSON. Returns null if the body
     * is empty or not valid JSON.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed>|null The decoded data or null.
     */
    public function getData(): ?array
    {
        if ($this->body === null || $this->body === '') {
            return null;
        }

        $data = json_decode($this->body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        /** @var array<string, mixed>|null $data */
        return is_array($data) ? $data : null;
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
                self::KEY_METHOD => [
                    'type' => 'string',
                    'description' => 'The HTTP method.',
                ],
                self::KEY_URI => [
                    'type' => 'string',
                    'description' => 'The request URI.',
                ],
                self::KEY_HEADERS => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'oneOf' => [
                            ['type' => 'string'],
                            [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'description' => 'The request headers.',
                ],
                self::KEY_BODY => [
                    'type' => ['string', 'null'],
                    'description' => 'The request body.',
                ],
            ],
            'required' => [self::KEY_METHOD, self::KEY_URI, self::KEY_HEADERS],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return RequestArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_METHOD => $this->method,
            self::KEY_URI => $this->uri,
            self::KEY_HEADERS => $this->headers,
        ];

        if ($this->body !== null) {
            $data[self::KEY_BODY] = $this->body;
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
        static::validateFromArrayData($array, [self::KEY_METHOD, self::KEY_URI, self::KEY_HEADERS]);

        return new self(
            $array[self::KEY_METHOD],
            $array[self::KEY_URI],
            $array[self::KEY_HEADERS],
            $array[self::KEY_BODY] ?? null
        );
    }
}

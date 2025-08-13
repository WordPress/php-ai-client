<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;

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
 *     headers: array<string, list<string>>,
 *     data?: string|array<string, mixed>|null
 * }
 *
 * @extends AbstractDataTransferObject<RequestArrayShape>
 */
class Request extends AbstractDataTransferObject
{
    public const KEY_METHOD = 'method';
    public const KEY_URI = 'uri';
    public const KEY_HEADERS = 'headers';
    public const KEY_DATA = 'data';

    /**
     * @var HttpMethodEnum The HTTP method.
     */
    protected HttpMethodEnum $method;

    /**
     * @var string The request URI.
     */
    protected string $uri;

    /**
     * @var array<string, list<string>> The request headers.
     */
    protected array $headers;

    /**
     * @var string|array<string, mixed>|null The request data.
     */
    protected $data;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param HttpMethodEnum $method The HTTP method.
     * @param string $uri The request URI.
     * @param array<string, string|list<string>> $headers The request headers.
     * @param string|array<string, mixed>|null $data The request data.
     *
     * @throws InvalidArgumentException If the URI is empty.
     */
    public function __construct(HttpMethodEnum $method, string $uri, array $headers = [], $data = null)
    {
        if (empty($uri)) {
            throw new InvalidArgumentException('URI cannot be empty.');
        }

        $this->method = $method;
        $this->uri = $uri;
        $this->headers = $this->normalizeHeaders($headers);
        $this->data = $data;
    }

    /**
     * Gets the HTTP method.
     *
     * @since n.e.x.t
     *
     * @return HttpMethodEnum The HTTP method.
     */
    public function getMethod(): HttpMethodEnum
    {
        return $this->method;
    }

    /**
     * Gets the request URI.
     *
     * For GET requests with array data, appends the data as query parameters.
     *
     * @since n.e.x.t
     *
     * @return string The URI.
     */
    public function getUri(): string
    {
        // If GET request with array data, append as query parameters
        if ($this->method === HttpMethodEnum::GET() && is_array($this->data) && !empty($this->data)) {
            $separator = strpos($this->uri, '?') === false ? '?' : '&';
            return $this->uri . $separator . http_build_query($this->data);
        }

        return $this->uri;
    }

    /**
     * Gets the request headers.
     *
     * @since n.e.x.t
     *
     * @return array<string, list<string>> The headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Gets the request body.
     *
     * For GET requests, returns null.
     * For POST/PUT/PATCH requests:
     * - If data is a string, returns it as-is
     * - If data is an array and Content-Type is JSON, returns JSON-encoded data
     * - If data is an array and Content-Type is form, returns URL-encoded data
     *
     * @since n.e.x.t
     *
     * @return string|null The body.
     * @throws JsonException If the data cannot be encoded to JSON.
     */
    public function getBody(): ?string
    {
        // GET requests don't have a body
        if ($this->method === HttpMethodEnum::GET()) {
            return null;
        }

        // If data is null, return null
        if ($this->data === null) {
            return null;
        }

        // If data is already a string, return it as-is
        if (is_string($this->data)) {
            return $this->data;
        }

        // If data is an array, encode based on content type
        if (is_array($this->data)) {
            $contentType = $this->getContentType();

            // JSON encoding
            if ($contentType !== null && stripos($contentType, 'application/json') !== false) {
                return json_encode($this->data, JSON_THROW_ON_ERROR);
            }

            // Default to URL encoding for forms
            return http_build_query($this->data);
        }

        return null;
    }

    /**
     * Gets the Content-Type header value.
     *
     * @since n.e.x.t
     *
     * @return string|null The Content-Type header value or null if not set.
     */
    private function getContentType(): ?string
    {
        foreach ($this->headers as $name => $values) {
            if (strcasecmp($name, 'Content-Type') === 0) {
                return $values[0] ?? null;
            }
        }
        return null;
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
        $headers[$name] = is_array($value) ? array_values($value) : [$value];

        return new self($this->method, $this->uri, $headers, $this->data);
    }

    /**
     * Returns a new instance with the specified data.
     *
     * @since n.e.x.t
     *
     * @param string|array<string, mixed> $data The request data.
     * @return self A new instance with the data.
     */
    public function withData($data): self
    {
        return new self($this->method, $this->uri, $this->headers, $data);
    }

    /**
     * Normalizes headers to ensure they are all arrays.
     *
     * @since n.e.x.t
     *
     * @param array<string, string|list<string>> $headers The headers to normalize.
     * @return array<string, list<string>> The normalized headers.
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[$name] = is_array($value) ? array_values($value) : [$value];
        }
        return $normalized;
    }

    /**
     * Gets the request data.
     *
     * @since n.e.x.t
     *
     * @return string|array<string, mixed>|null The request data.
     */
    public function getData()
    {
        return $this->data;
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
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'description' => 'The request headers.',
                ],
                self::KEY_DATA => [
                    'type' => ['string', 'array', 'null'],
                    'description' => 'The request data.',
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
        $array = [
            self::KEY_METHOD => $this->method->value,
            self::KEY_URI => $this->uri,
            self::KEY_HEADERS => $this->headers,
        ];

        if ($this->data !== null) {
            $array[self::KEY_DATA] = $this->data;
        }

        return $array;
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
            HttpMethodEnum::from($array[self::KEY_METHOD]),
            $array[self::KEY_URI],
            $array[self::KEY_HEADERS] ?? [],
            $array[self::KEY_DATA] ?? null
        );
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;

/**
 * Class for HTTP request authentication using a bearer token.
 *
 * @since n.e.x.t
 *
 * @phpstan-type BearerTokenRequestAuthenticationArrayShape array{
 *     bearerToken: string
 * }
 *
 * @extends AbstractDataTransferObject<BearerTokenRequestAuthenticationArrayShape>
 */
class BearerTokenRequestAuthentication extends AbstractDataTransferObject implements RequestAuthenticationInterface
{
    public const KEY_BEARER_TOKEN = 'bearerToken';

    /**
     * @var string The bearer token used for authentication.
     */
    protected string $bearerToken;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $bearerToken The bearer token used for authentication.
     */
    public function __construct(string $bearerToken)
    {
        $this->bearerToken = $bearerToken;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function authenticateRequest(Request $request): Request
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->bearerToken);
    }

    /**
     * Gets the bearer token.
     *
     * @since n.e.x.t
     *
     * @return string The bearer token.
     */
    public function getBearerToken(): string
    {
        return $this->bearerToken;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return BearerTokenRequestAuthenticationArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_BEARER_TOKEN => $this->bearerToken,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_BEARER_TOKEN]);

        return new self($array[self::KEY_BEARER_TOKEN]);
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
                self::KEY_BEARER_TOKEN => [
                    'type' => 'string',
                    'title' => 'Bearer Token',
                    'description' => 'The bearer token used for authentication.',
                ],
            ],
            'required' => [self::KEY_BEARER_TOKEN],
        ];
    }
}

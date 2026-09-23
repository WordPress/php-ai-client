<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Enums;

use WordPress\AiClient\Common\AbstractEnum;
use WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\BearerTokenRequestAuthentication;

/**
 * Enum for request authentication methods.
 *
 * @since 0.4.0
 *
 * @method static self apiKey() Creates an instance for API_KEY method.
 * @method static self bearerToken() Creates an instance for BEARER_TOKEN method.
 * @method bool isApiKey() Checks if the method is API_KEY.
 * @method bool isBearerToken() Checks if the method is BEARER_TOKEN.
 */
class RequestAuthenticationMethod extends AbstractEnum
{
    /**
     * API key authentication.
     */
    public const API_KEY = 'api_key';

    /**
     * Bearer token authentication.
     *
     * @since n.e.x.t
     */
    public const BEARER_TOKEN = 'bearer_token';

    /**
     * Gets the implementation class for the authentication method.
     *
     * @since 0.4.0
     *
     * @return class-string<RequestAuthenticationInterface&WithArrayTransformationInterface> The implementation class.
     *
     * @phpstan-ignore missingType.generics
     */
    public function getImplementationClass(): string
    {
        if ($this->isBearerToken()) {
            return BearerTokenRequestAuthentication::class;
        }

        return ApiKeyRequestAuthentication::class;
    }
}

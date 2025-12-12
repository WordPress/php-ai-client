<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Enums;

use WordPress\AiClient\Common\AbstractEnum;
use WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;

/**
 * Enum for request authentication methods.
 *
 * @since n.e.x.t
 *
 * @method static self apiKey() Creates an instance for API_KEY method.
 * @method bool isApiKey() Checks if the method is API_KEY.
 */
class RequestAuthenticationMethod extends AbstractEnum
{
    /**
     * API key authentication.
     */
    public const API_KEY = 'api_key';

    /**
     * Gets the implementation class for the authentication method.
     *
     * @since n.e.x.t
     *
     * @return class-string<RequestAuthenticationInterface&WithArrayTransformationInterface>|null The implementation
     *                                                                                            class, or null if
     *                                                                                            none.
     * @phpstan-ignore missingType.generics
     */
    public function getImplementationClass(): ?string
    {
        if ($this->isApiKey()) {
            return ApiKeyRequestAuthentication::class;
        }

        return null;
    }
}

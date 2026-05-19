<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Contracts;

use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;

/**
 * Interface for providers that supply their own request authentication.
 *
 * @since n.e.x.t
 */
interface ProviderWithRequestAuthenticationInterface
{
    /**
     * Gets the request authentication instance for the provider.
     *
     * @since n.e.x.t
     *
     * @return RequestAuthenticationInterface|null The request authentication instance, or null if not configured.
     */
    public static function requestAuthentication(): ?RequestAuthenticationInterface;
}

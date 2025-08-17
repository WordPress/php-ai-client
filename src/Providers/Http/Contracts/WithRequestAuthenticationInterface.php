<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Contracts;

/**
 * Interface for models that support request authentication.
 *
 * @since n.e.x.t
 */
interface WithRequestAuthenticationInterface
{
    /**
     * Sets the request authentication.
     *
     * @since n.e.x.t
     *
     * @param RequestAuthenticationInterface $authentication The authentication instance.
     * @return void
     */
    public function setRequestAuthentication(RequestAuthenticationInterface $authentication): void;

    /**
     * Returns the request authentication.
     *
     * @since n.e.x.t
     *
     * @return RequestAuthenticationInterface The authentication instance.
     */
    public function getRequestAuthentication(): RequestAuthenticationInterface;
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Traits;

use RuntimeException;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;

/**
 * Trait for a class that implements WithRequestAuthenticationInterface.
 *
 * @since n.e.x.t
 */
trait WithRequestAuthenticationTrait
{
    /**
     * @var RequestAuthenticationInterface|null The request authentication instance.
     */
    private ?RequestAuthenticationInterface $requestAuthentication = null;

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function setRequestAuthentication(RequestAuthenticationInterface $requestAuthentication): void
    {
        $this->requestAuthentication = $requestAuthentication;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        if ($this->requestAuthentication === null) {
            throw new RuntimeException(
                'RequestAuthenticationInterface instance not set. ' .
                'Make sure you use the AiClient class for all requests.'
            );
        }
        return $this->requestAuthentication;
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Traits;

use RuntimeException;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;

/**
 * Trait for a class that implements WithHttpTransporterInterface.
 *
 * @since n.e.x.t
 */
trait WithHttpTransporterTrait
{
    /**
     * @var HttpTransporterInterface|null The HTTP transporter instance.
     */
    private ?HttpTransporterInterface $httpTransporter = null;

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function setHttpTransporter(HttpTransporterInterface $httpTransporter): void
    {
        $this->httpTransporter = $httpTransporter;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getHttpTransporter(): HttpTransporterInterface
    {
        if ($this->httpTransporter === null) {
            throw new RuntimeException(
                'HttpTransporterInterface instance not set. Make sure you use the AiClient class for all requests.'
            );
        }
        return $this->httpTransporter;
    }
}

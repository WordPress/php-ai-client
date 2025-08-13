<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Contracts;

/**
 * Interface for models that require HTTP transport capabilities.
 *
 * @since n.e.x.t
 */
interface WithHttpTransporterInterface
{
    /**
     * Sets the HTTP transporter.
     *
     * @since n.e.x.t
     *
     * @param HttpTransporterInterface $transporter The HTTP transporter instance.
     * @return void
     */
    public function setHttpTransporter(HttpTransporterInterface $transporter): void;

    /**
     * Returns the HTTP transporter.
     *
     * @since n.e.x.t
     *
     * @return HttpTransporterInterface The HTTP transporter instance.
     */
    public function getHttpTransporter(): HttpTransporterInterface;
}

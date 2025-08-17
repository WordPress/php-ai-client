<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Contracts;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;

/**
 * Interface for HTTP transport implementations.
 *
 * Handles sending HTTP requests and receiving responses using
 * PSR-7, PSR-17, and PSR-18 standards internally.
 *
 * @since n.e.x.t
 */
interface HttpTransporterInterface
{
    /**
     * Sends an HTTP request and returns the response.
     *
     * @since n.e.x.t
     *
     * @param Request $request The request to send.
     * @return Response The response received.
     */
    public function send(Request $request): Response;
}

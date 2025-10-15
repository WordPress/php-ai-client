<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;

/**
 * Interface for HTTP clients that support per-request transport options.
 *
 * Extends the capabilities of PSR-18 clients by allowing custom transport
 * configuration such as timeouts and redirect handling on each request.
 *
 * @since n.e.x.t
 */
interface ClientWithOptionsInterface
{
    /**
     * Sends an HTTP request with the given transport options.
     *
     * @since n.e.x.t
     *
     * @param RequestInterface $request The PSR-7 request to send.
     * @param RequestOptions|null $options The request transport options.
     * @return ResponseInterface The PSR-7 response received.
     */
    public function sendRequestWithOptions(
        RequestInterface $request,
        ?RequestOptions $options
    ): ResponseInterface;
}

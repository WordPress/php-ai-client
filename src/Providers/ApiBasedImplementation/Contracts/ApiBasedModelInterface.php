<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\ApiBasedImplementation\Contracts;

use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;

/**
 * Interface for API-based AI models that support HTTP transport configuration.
 *
 * This interface extends ModelInterface to add request options support
 * for models that communicate with external APIs via HTTP.
 *
 * @since n.e.x.t
 */
interface ApiBasedModelInterface extends ModelInterface
{
    /**
     * Sets the request options for HTTP transport.
     *
     * @since n.e.x.t
     *
     * @param RequestOptions $requestOptions The request options to use.
     * @return void
     */
    public function setRequestOptions(RequestOptions $requestOptions): void;

    /**
     * Gets the request options for HTTP transport.
     *
     * @since n.e.x.t
     *
     * @return RequestOptions|null The request options, or null if not set.
     */
    public function getRequestOptions(): ?RequestOptions;
}

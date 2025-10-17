<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Http\DTO\Response;

/**
 * Mock HTTP transporter for testing.
 */
class MockHttpTransporter implements HttpTransporterInterface
{
    /**
     * @var Request|null The last request that was sent.
     */
    private ?Request $lastRequest = null;

    /**
     * @var RequestOptions|null The last options that were provided.
     */
    private ?RequestOptions $lastOptions = null;

    /**
     * @var Response|null The response to return.
     */
    private ?Response $responseToReturn = null;

    /**
     * {@inheritDoc}
     */
    public function send(Request $request, ?RequestOptions $options = null): Response
    {
        $this->lastRequest = $request;
        $this->lastOptions = $options;
        return $this->responseToReturn ?? new Response(200, [], '{"status":"success"}');
    }

    /**
     * Gets the last request that was sent.
     *
     * @return Request|null
     */
    public function getLastRequest(): ?Request
    {
        return $this->lastRequest;
    }

    /**
     * Gets the last request options that were provided.
     *
     * @return RequestOptions|null
     */
    public function getLastOptions(): ?RequestOptions
    {
        return $this->lastOptions;
    }

    /**
     * Sets the response to return for subsequent requests.
     *
     * @param Response $response
     */
    public function setResponseToReturn(Response $response): void
    {
        $this->responseToReturn = $response;
    }
}

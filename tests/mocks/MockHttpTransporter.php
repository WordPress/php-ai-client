<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;
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
     * @var Response|null The response to return.
     */
    private ?Response $responseToReturn = null;

    /**
     * @inheritDoc
     */
    public function send(Request $request): Response
    {
        $this->lastRequest = $request;
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
     * Sets the response to return for subsequent requests.
     *
     * @param Response $response
     */
    public function setResponseToReturn(Response $response): void
    {
        $this->responseToReturn = $response;
    }
}

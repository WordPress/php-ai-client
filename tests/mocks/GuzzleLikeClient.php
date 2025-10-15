<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Minimal mock emulating Guzzle's client signature for testing.
 */
class GuzzleLikeClient implements ClientInterface
{
    /**
     * @var ResponseInterface Response returned by the client.
     */
    private ResponseInterface $response;

    /**
     * @var RequestInterface|null The last request passed to send.
     */
    private ?RequestInterface $lastRequest = null;

    /**
     * @var array<string, mixed>|null The last options passed to send.
     */
    private ?array $lastOptions = null;

    /**
     * @var bool Whether sendRequest was used instead of send.
     */
    private bool $sendRequestCalled = false;

    /**
     * Constructor.
     *
     * @param ResponseInterface $response The response to return.
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * {@inheritDoc}
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;
        $this->lastOptions = null;
        $this->sendRequestCalled = true;

        return $this->response;
    }

    /**
     * Emulates Guzzle's send method that accepts options.
     *
     * @param RequestInterface $request The request being sent.
     * @param array<string, mixed> $options The request options.
     * @return ResponseInterface The response instance.
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        $this->lastRequest = $request;
        $this->lastOptions = $options;
        $this->sendRequestCalled = false;

        return $this->response;
    }

    /**
     * Gets the last options provided to the client.
     *
     * @return array<string, mixed>|null The options or null when sendRequest was used.
     */
    public function getLastOptions(): ?array
    {
        return $this->lastOptions;
    }

    /**
     * Determines whether sendRequest was called instead of send.
     *
     * @return bool True when sendRequest was called.
     */
    public function wasSendRequestCalled(): bool
    {
        return $this->sendRequestCalled;
    }
}

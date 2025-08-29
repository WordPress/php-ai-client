<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;

/**
 * HTTP transporter implementation using HTTPlug.
 *
 * This class handles the conversion between custom Request/Response
 * objects and PSR-7 messages, using HTTPlug for client abstraction
 * and PSR-17 factories for message creation.
 *
 * @since 0.1.0
 */
class HttpTransporter implements HttpTransporterInterface
{
    /**
     * @var RequestFactoryInterface PSR-17 request factory.
     */
    private RequestFactoryInterface $requestFactory;

    /**
     * @var StreamFactoryInterface PSR-17 stream factory.
     */
    private StreamFactoryInterface $streamFactory;

    /**
     * @var ClientInterface PSR-18 HTTP client.
     */
    private ClientInterface $client;

    /**
     * Constructor.
     *
     * @since 0.1.0
     *
     * @param ClientInterface|null $client PSR-18 HTTP client.
     * @param RequestFactoryInterface|null $requestFactory PSR-17 request factory.
     * @param StreamFactoryInterface|null $streamFactory PSR-17 stream factory.
     */
    public function __construct(
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $this->client = $client ?: Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?: Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?: Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    public function send(Request $request): Response
    {
        $psr7Request = $this->convertToPsr7Request($request);
        $psr7Response = $this->client->sendRequest($psr7Request);

        return $this->convertFromPsr7Response($psr7Response);
    }

    /**
     * Converts a custom Request to a PSR-7 request.
     *
     * @since 0.1.0
     *
     * @param Request $request The custom request.
     * @return RequestInterface The PSR-7 request.
     */
    private function convertToPsr7Request(Request $request): RequestInterface
    {
        $psr7Request = $this->requestFactory->createRequest(
            $request->getMethod()->value,
            $request->getUri()
        );

        // Add headers
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $psr7Request = $psr7Request->withAddedHeader($name, $value);
            }
        }

        // Add body if present
        $body = $request->getBody();
        if ($body !== null) {
            $stream = $this->streamFactory->createStream($body);
            $psr7Request = $psr7Request->withBody($stream);
        }

        return $psr7Request;
    }

    /**
     * Converts a PSR-7 response to a custom Response.
     *
     * @since 0.1.0
     *
     * @param ResponseInterface $psr7Response The PSR-7 response.
     * @return Response The custom response.
     */
    private function convertFromPsr7Response(ResponseInterface $psr7Response): Response
    {
        $body = (string) $psr7Response->getBody();

        // PSR-7 always returns headers as arrays, but HeadersCollection handles this
        return new Response(
            $psr7Response->getStatusCode(),
            $psr7Response->getHeaders(), // @phpstan-ignore-line
            $body === '' ? null : $body
        );
    }
}

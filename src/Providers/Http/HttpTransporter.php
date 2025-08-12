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
 * @since n.e.x.t
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
     * @since n.e.x.t
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
     * @since n.e.x.t
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
     * @since n.e.x.t
     *
     * @param Request $request The custom request.
     * @return RequestInterface The PSR-7 request.
     */
    private function convertToPsr7Request(Request $request): RequestInterface
    {
        $psr7Request = $this->requestFactory->createRequest(
            $request->getMethod(),
            $request->getUri()
        );

        // Add headers
        foreach ($request->getHeaders() as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $psr7Request = $psr7Request->withAddedHeader($name, $v);
                }
            } else {
                $psr7Request = $psr7Request->withHeader($name, $value);
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
     * @since n.e.x.t
     *
     * @param ResponseInterface $psr7Response The PSR-7 response.
     * @return Response The custom response.
     */
    private function convertFromPsr7Response(ResponseInterface $psr7Response): Response
    {
        /** @var array<string, string|list<string>> $headers */
        $headers = [];
        foreach ($psr7Response->getHeaders() as $name => $values) {
            $headers[(string) $name] = count($values) === 1 ? $values[0] : array_values($values);
        }

        $body = (string) $psr7Response->getBody();

        return new Response(
            $psr7Response->getStatusCode(),
            $headers,
            $body === '' ? null : $body,
            $psr7Response->getReasonPhrase()
        );
    }
}

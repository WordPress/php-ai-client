<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Http\Abstracts;

use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;

/**
 * Abstract discovery strategy for HTTP client implementations.
 *
 * Provides a base for registering custom HTTP client implementations
 * with HTTPlug's discovery mechanism. Subclasses must implement
 * the createClient() method to provide their specific PSR-18
 * HTTP client instance using the provided Psr17Factory.
 *
 * @since 1.1.0
 */
abstract class AbstractClientDiscoveryStrategy implements DiscoveryStrategy
{
    /**
     * Initializes and registers the discovery strategy.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public static function init(): void
    {
        if (!class_exists('\Http\Discovery\Psr18ClientDiscovery')) {
            return;
        }

        Psr18ClientDiscovery::prependStrategy(static::class);
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.1.0
     *
     * @param string $type The type of discovery.
     * @return array<array<string, mixed>> The discovery candidates.
     */
    public static function getCandidates($type)
    {
        if (ClientInterface::class === $type) {
            return [
                [
                    'class' => static function () {
                        $psr17Factory = new Psr17Factory();
                        return static::createClient($psr17Factory);
                    },
                ],
            ];
        }

        $psr17Factories = [
            'Psr\Http\Message\RequestFactoryInterface',
            'Psr\Http\Message\ResponseFactoryInterface',
            'Psr\Http\Message\ServerRequestFactoryInterface',
            'Psr\Http\Message\StreamFactoryInterface',
            'Psr\Http\Message\UploadedFileFactoryInterface',
            'Psr\Http\Message\UriFactoryInterface',
        ];

        if (in_array($type, $psr17Factories, true)) {
            return [
                [
                    'class' => Psr17Factory::class,
                ],
            ];
        }

        return [];
    }

    /**
     * Creates an instance of the HTTP client.
     *
     * Subclasses must implement this method to return their specific
     * PSR-18 HTTP client instance. The provided Psr17Factory implements
     * all PSR-17 interfaces (RequestFactory, ResponseFactory, StreamFactory,
     * etc.) and can be used to satisfy client constructor dependencies.
     *
     * @since 1.1.0
     *
     * @param Psr17Factory $psr17Factory The PSR-17 factory for creating HTTP messages.
     * @return ClientInterface The PSR-18 HTTP client.
     */
    abstract protected static function createClient(Psr17Factory $psr17Factory): ClientInterface;
}

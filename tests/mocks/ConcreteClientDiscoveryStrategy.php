<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use WordPress\AiClient\Providers\Http\Abstracts\AbstractClientDiscoveryStrategy;

/**
 * Concrete implementation of AbstractClientDiscoveryStrategy for testing.
 */
class ConcreteClientDiscoveryStrategy extends AbstractClientDiscoveryStrategy
{
    /**
     * @var ClientInterface|null The client to return from createClient().
     */
    private static ?ClientInterface $clientToReturn = null;

    /**
     * @var Psr17Factory|null The last Psr17Factory received by createClient().
     */
    private static ?Psr17Factory $lastPsr17Factory = null;

    /**
     * {@inheritDoc}
     *
     * @param Psr17Factory $psr17Factory The PSR-17 factory for creating HTTP messages.
     * @return ClientInterface The PSR-18 HTTP client.
     */
    protected static function createClient(Psr17Factory $psr17Factory): ClientInterface
    {
        self::$lastPsr17Factory = $psr17Factory;

        /** @var ClientInterface $client */
        $client = self::$clientToReturn;

        return $client;
    }

    /**
     * Sets the client instance that createClient() will return.
     *
     * @param ClientInterface $client The client to return.
     * @return void
     */
    public static function setClientToReturn(ClientInterface $client): void
    {
        self::$clientToReturn = $client;
    }

    /**
     * Returns the last Psr17Factory passed to createClient().
     *
     * @return Psr17Factory|null The last factory instance, or null if not called.
     */
    public static function getLastPsr17Factory(): ?Psr17Factory
    {
        return self::$lastPsr17Factory;
    }

    /**
     * Resets the static state for test isolation.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$clientToReturn = null;
        self::$lastPsr17Factory = null;
    }
}

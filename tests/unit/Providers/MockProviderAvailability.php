<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers;

use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

/**
 * Mock provider availability for testing.
 *
 * @since n.e.x.t
 */
class MockProviderAvailability implements ProviderAvailabilityInterface
{
    /**
     * @var bool Whether the provider is configured.
     */
    private bool $configured;

    /**
     * Constructor.
     *
     * @param bool $configured Whether the provider is configured.
     */
    public function __construct(bool $configured = true)
    {
        $this->configured = $configured;
    }

    /**
     * {@inheritDoc}
     */
    public function isConfigured(): bool
    {
        return $this->configured;
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Http\Contracts\WithHttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\WithRequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\Traits\WithHttpTransporterTrait;
use WordPress\AiClient\Providers\Http\Traits\WithRequestAuthenticationTrait;

/**
 * Mock provider availability for testing.
 */
class MockProviderAvailability implements
    ProviderAvailabilityInterface,
    WithHttpTransporterInterface,
    WithRequestAuthenticationInterface
{
    use WithHttpTransporterTrait;
    use WithRequestAuthenticationTrait;

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

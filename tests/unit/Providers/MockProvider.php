<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers;

use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;

/**
 * Mock provider for testing purposes.
 *
 * @since n.e.x.t
 */
class MockProvider
{
    /**
     * Returns provider metadata.
     *
     * @since n.e.x.t
     *
     * @return ProviderMetadata The provider metadata.
     */
    public function metadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'mock',
            'Mock Provider',
            ProviderTypeEnum::CLOUD()
        );
    }

    /**
     * Checks if the provider is available and configured.
     *
     * @since n.e.x.t
     *
     * @return bool True if available.
     */
    public function availability(): bool
    {
        return true;
    }
}
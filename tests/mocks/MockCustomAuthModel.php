<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\DTO\ProviderMetadata;

/**
 * Mock model for the custom authentication provider.
 */
class MockCustomAuthModel extends MockModel
{
    /**
     * {@inheritDoc}
     */
    public function providerMetadata(): ProviderMetadata
    {
        return MockCustomAuthProvider::metadata();
    }
}

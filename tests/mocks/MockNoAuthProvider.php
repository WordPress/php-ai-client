<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Contracts\ProviderInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Mock provider that does not require authentication.
 */
class MockNoAuthProvider implements ProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public static function metadata(): ProviderMetadata
    {
        return new ProviderMetadata('no-auth', 'No Auth Provider', ['type' => ProviderTypeEnum::server()]);
    }

    /**
     * {@inheritDoc}
     */
    public static function availability(): ProviderAvailabilityInterface
    {
        return new MockProviderAvailability();
    }

    /**
     * {@inheritDoc}
     */
    public static function modelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new MockModelMetadataDirectory([]);
    }

    /**
     * {@inheritDoc}
     */
    public static function model(string $modelId, ?ModelConfig $modelConfig = null): ModelInterface
    {
        return new MockModel(new ModelMetadata('model', 'Model', [], []), new ModelConfig());
    }
}

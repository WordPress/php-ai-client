<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\AbstractProvider;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

class MockAbstractProvider extends AbstractProvider
{
    /**
     * @var ModelInterface|null
     */
    public static ?ModelInterface $mockModel = null;

    /**
     * @var ProviderMetadata|null
     */
    public static ?ProviderMetadata $mockProviderMetadata = null;

    /**
     * @var ProviderAvailabilityInterface|null
     */
    public static ?ProviderAvailabilityInterface $mockProviderAvailability = null;

    /**
     * @var ModelMetadataDirectoryInterface|null
     */
    public static ?ModelMetadataDirectoryInterface $mockModelMetadataDirectory = null;

    /**
     * @inheritdoc
     */
    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        return static::$mockModel ?? new MockModel();
    }

    /**
     * @inheritdoc
     */
    protected static function createProviderMetadata(): ProviderMetadata
    {
        return static::$mockProviderMetadata ?? new ProviderMetadata('mock-provider', 'Mock Provider', '1.0.0');
    }

    /**
     * @inheritdoc
     */
    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        return static::$mockProviderAvailability ?? new MockProviderAvailability();
    }

    /**
     * @inheritdoc
     */
    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return static::$mockModelMetadataDirectory ?? new MockModelMetadataDirectory();
    }

    /**
     * Reset all mock properties.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$mockModel = null;
        static::$mockProviderMetadata = null;
        static::$mockProviderAvailability = null;
        static::$mockModelMetadataDirectory = null;
    }
}

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
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Secondary mock provider for provider-order testing.
 */
class MockSecondaryProvider implements ProviderInterface
{
    /**
     * @var MockModelMetadataDirectory|null Static instance of model metadata directory.
     */
    private static ?MockModelMetadataDirectory $modelMetadataDirectory = null;

    /**
     * @var MockProviderAvailability|null Static instance of availability checker.
     */
    private static ?MockProviderAvailability $availability = null;

    /**
     * {@inheritDoc}
     */
    public static function metadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'mock-secondary',
            'Mock Secondary Provider',
            ProviderTypeEnum::cloud()
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function model(string $modelId, ?ModelConfig $modelConfig = null): ModelInterface
    {
        $modelMetadata = static::modelMetadataDirectory()->getModelMetadata($modelId);

        $config = $modelConfig ?? new ModelConfig();

        return new MockModel($modelMetadata, $config);
    }

    /**
     * {@inheritDoc}
     */
    public static function availability(): ProviderAvailabilityInterface
    {
        if (static::$availability === null) {
            static::$availability = new MockProviderAvailability(true);
        }

        return static::$availability;
    }

    /**
     * {@inheritDoc}
     */
    public static function modelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        if (static::$modelMetadataDirectory === null) {
            $mockModels = [
                'mock-secondary-text-model' => new ModelMetadata(
                    'mock-secondary-text-model',
                    'Mock Secondary Text Model',
                    [CapabilityEnum::textGeneration()],
                    []
                )
            ];

            static::$modelMetadataDirectory = new MockModelMetadataDirectory($mockModels);
        }

        return static::$modelMetadataDirectory;
    }

    /**
     * Sets the availability checker for testing.
     *
     * @param MockProviderAvailability $availability The availability checker.
     */
    public static function setAvailability(MockProviderAvailability $availability): void
    {
        static::$availability = $availability;
    }

    /**
     * Sets the model metadata directory for testing.
     *
     * @param MockModelMetadataDirectory $directory The model metadata directory.
     */
    public static function setModelMetadataDirectory(MockModelMetadataDirectory $directory): void
    {
        static::$modelMetadataDirectory = $directory;
    }

    /**
     * Resets static state for testing.
     */
    public static function reset(): void
    {
        static::$availability = null;
        static::$modelMetadataDirectory = null;
    }
}

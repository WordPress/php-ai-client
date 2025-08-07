<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers;

use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Contracts\ProviderInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Mock provider for testing purposes.
 *
 * @since n.e.x.t
 */
class MockProvider implements ProviderInterface
{
    /**
     * @var MockModelMetadataDirectory Static instance of model metadata directory.
     */
    private static ?MockModelMetadataDirectory $modelMetadataDirectory = null;

    /**
     * @var MockProviderAvailability Static instance of availability checker.
     */
    private static ?MockProviderAvailability $availability = null;

    /**
     * {@inheritDoc}
     */
    public static function metadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'mock',
            'Mock Provider',
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
            // Create some mock models for testing
            $mockModels = [
                'mock-text-model' => new \WordPress\AiClient\Providers\Models\DTO\ModelMetadata(
                    'mock-text-model',
                    'Mock Text Model',
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
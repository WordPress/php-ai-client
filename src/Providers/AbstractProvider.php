<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers;

use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Contracts\ProviderInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Base class for a provider.
 *
 * @since n.e.x.t
 */
abstract class AbstractProvider implements ProviderInterface
{
    /**
     * @var array<string, ProviderMetadata> Cache for provider metadata per class.
     */
    private static array $metadataCache = [];

    /**
     * @var array<string, ProviderAvailabilityInterface> Cache for provider availability per class.
     */
    private static array $availabilityCache = [];

    /**
     * @var array<string, ModelMetadataDirectoryInterface> Cache for model metadata directory per class.
     */
    private static array $modelMetadataDirectoryCache = [];

    /**
     * @inheritdoc
     */
    final public static function metadata(): ProviderMetadata
    {
        $className = static::class;
        if (!isset(self::$metadataCache[$className])) {
            self::$metadataCache[$className] = static::createProviderMetadata();
        }
        return self::$metadataCache[$className];
    }

    /**
     * @inheritdoc
     */
    final public static function model(string $modelId, ?ModelConfig $modelConfig = null): ModelInterface
    {
        $providerMetadata = static::metadata();
        $modelMetadata = static::modelMetadataDirectory()->getModelMetadata($modelId);

        $model = static::createModel($modelMetadata, $providerMetadata);
        if ($modelConfig) {
            $model->setConfig($modelConfig);
        }
        return $model;
    }

    /**
     * @inheritdoc
     */
    final public static function availability(): ProviderAvailabilityInterface
    {
        $className = static::class;
        if (!isset(self::$availabilityCache[$className])) {
            self::$availabilityCache[$className] = static::createProviderAvailability();
        }
        return self::$availabilityCache[$className];
    }

    /**
     * @inheritdoc
     */
    final public static function modelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        $className = static::class;
        if (!isset(self::$modelMetadataDirectoryCache[$className])) {
            self::$modelMetadataDirectoryCache[$className] = static::createModelMetadataDirectory();
        }
        return self::$modelMetadataDirectoryCache[$className];
    }

    /**
     * Creates a model instance based on the given model metadata and provider metadata.
     *
     * @since n.e.x.t
     *
     * @param ModelMetadata $modelMetadata The model metadata.
     * @param ProviderMetadata $providerMetadata The provider metadata.
     * @return ModelInterface The new model instance.
     */
    abstract protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface;

    /**
     * Creates the provider metadata instance.
     *
     * @since n.e.x.t
     *
     * @return ProviderMetadata The provider metadata.
     */
    abstract protected static function createProviderMetadata(): ProviderMetadata;

    /**
     * Creates the provider availability instance.
     *
     * @since n.e.x.t
     *
     * @return ProviderAvailabilityInterface The provider availability.
     */
    abstract protected static function createProviderAvailability(): ProviderAvailabilityInterface;

    /**
     * Creates the model metadata directory instance.
     *
     * @since n.e.x.t
     *
     * @return ModelMetadataDirectoryInterface The model metadata directory.
     */
    abstract protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface;
}

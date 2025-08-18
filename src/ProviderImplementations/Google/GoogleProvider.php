<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\Google;

use RuntimeException;
use WordPress\AiClient\Providers\AbstractProvider;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Class for the Google provider.
 *
 * @since n.e.x.t
 */
class GoogleProvider extends AbstractProvider
{
    public const BASE_URI = 'https://generativelanguage.googleapis.com/v1beta';

    /**
     * @inheritDoc
     */
    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        $capabilities = $modelMetadata->getSupportedCapabilities();
        foreach ($capabilities as $capability) {
            if ($capability->isTextGeneration()) {
                return new GoogleTextGenerationModel($modelMetadata, $providerMetadata);
            }
            if ($capability->isImageGeneration()) {
                // TODO: Implement GoogleImageGenerationModel.
                throw new RuntimeException(
                    'Google image generation model class is not yet implemented.'
                );
            }
        }

        throw new RuntimeException(
            'Unsupported model capabilities: ' . implode(', ', $capabilities)
        );
    }

    /**
     * @inheritDoc
     */
    protected static function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'google',
            'Google',
            ProviderTypeEnum::cloud()
        );
    }

    /**
     * @inheritDoc
     */
    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        // Check valid API access by attempting to list models.
        return new ListModelsApiBasedProviderAvailability(
            static::modelMetadataDirectory()
        );
    }

    /**
     * @inheritDoc
     */
    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new GoogleModelMetadataDirectory();
    }
}

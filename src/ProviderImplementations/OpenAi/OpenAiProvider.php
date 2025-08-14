<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

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
 * Class for the OpenAI provider.
 *
 * @since n.e.x.t
 */
class OpenAiProvider extends AbstractProvider
{
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
                return new OpenAiTextGenerationModel($modelMetadata, $providerMetadata);
            }
            if ($capability->isImageGeneration()) {
                // TODO: Implement OpenAiImageGenerationModel.
                return new OpenAiImageGenerationModel($modelMetadata, $providerMetadata);
            }
            if ($capability->isTextToSpeechConversion()) {
                // TODO: Implement OpenAiTextToSpeechConversionModel.
                return new OpenAiTextToSpeechConversionModel($modelMetadata, $providerMetadata);
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
            'openai',
            'OpenAI',
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
        return new OpenAiModelMetadataDirectory();
    }
}

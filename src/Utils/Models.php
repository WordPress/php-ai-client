<?php

declare(strict_types=1);

namespace WordPress\AiClient\Utils;

use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts\SpeechGenerationModelInterface;
use WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts\SpeechGenerationOperationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionModelInterface;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionOperationModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;

/**
 * Utility class for model discovery and interface validation.
 *
 * Combines model auto-discovery capabilities with interface validation
 * to provide a unified model utility service.
 *
 * @since n.e.x.t
 */
class Models
{
    /**
     * Generic method to find a model by capability.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The provider registry to search.
     * @param CapabilityEnum $capability The required capability.
     * @param string $errorType The error description type.
     * @return ModelInterface A suitable model.
     *
     * @throws \RuntimeException If no suitable model is found.
     */
    private static function findModelByCapability(
        ProviderRegistry $registry,
        CapabilityEnum $capability,
        string $errorType
    ): ModelInterface {
        $requirements = new ModelRequirements([$capability], []);
        $providerModelsMetadata = $registry->findModelsMetadataForSupport($requirements);

        if (empty($providerModelsMetadata)) {
            throw new \RuntimeException("No {$errorType} models available");
        }

        // Get the first suitable provider and model
        $providerMetadata = $providerModelsMetadata[0];
        $models = $providerMetadata->getModels();

        if (empty($models)) {
            throw new \RuntimeException('No models available in provider');
        }

        return $registry->getProviderModel(
            $providerMetadata->getProvider()->getId(),
            $models[0]->getId()
        );
    }

    /**
     * Finds a suitable text generation model from the registry.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The provider registry to search.
     * @return ModelInterface A suitable text generation model.
     *
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function findTextModel(ProviderRegistry $registry): ModelInterface
    {
        return self::findModelByCapability($registry, CapabilityEnum::textGeneration(), 'text generation');
    }

    /**
     * Finds a suitable image generation model from the registry.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The provider registry to search.
     * @return ModelInterface A suitable image generation model.
     *
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function findImageModel(ProviderRegistry $registry): ModelInterface
    {
        return self::findModelByCapability($registry, CapabilityEnum::imageGeneration(), 'image generation');
    }

    /**
     * Finds a suitable text-to-speech conversion model from the registry.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The provider registry to search.
     * @return ModelInterface A suitable text-to-speech conversion model.
     *
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function findTextToSpeechModel(ProviderRegistry $registry): ModelInterface
    {
        return self::findModelByCapability(
            $registry,
            CapabilityEnum::textToSpeechConversion(),
            'text-to-speech conversion'
        );
    }

    /**
     * Finds a suitable speech generation model from the registry.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The provider registry to search.
     * @return ModelInterface A suitable speech generation model.
     *
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function findSpeechModel(ProviderRegistry $registry): ModelInterface
    {
        return self::findModelByCapability($registry, CapabilityEnum::speechGeneration(), 'speech generation');
    }

    /**
     * Validates that a model implements TextGenerationModelInterface.
     *
     * @since n.e.x.t
     *
     * @param ModelInterface $model The model to validate.
     * @return void
     * @phpstan-assert TextGenerationModelInterface $model
     *
     * @throws \InvalidArgumentException If the model doesn't implement the required interface.
     */
    public static function validateTextGeneration(ModelInterface $model): void
    {
        if (!$model instanceof TextGenerationModelInterface) {
            throw new \InvalidArgumentException(
                'Model must implement TextGenerationModelInterface for text generation'
            );
        }
    }

    /**
     * Validates that a model implements ImageGenerationModelInterface.
     *
     * @since n.e.x.t
     *
     * @param ModelInterface $model The model to validate.
     * @return void
     * @phpstan-assert ImageGenerationModelInterface $model
     *
     * @throws \InvalidArgumentException If the model doesn't implement the required interface.
     */
    public static function validateImageGeneration(ModelInterface $model): void
    {
        if (!$model instanceof ImageGenerationModelInterface) {
            throw new \InvalidArgumentException(
                'Model must implement ImageGenerationModelInterface for image generation'
            );
        }
    }

    /**
     * Validates that a model implements TextToSpeechConversionModelInterface.
     *
     * @since n.e.x.t
     *
     * @param ModelInterface $model The model to validate.
     * @return void
     * @phpstan-assert TextToSpeechConversionModelInterface $model
     *
     * @throws \InvalidArgumentException If the model doesn't implement the required interface.
     */
    public static function validateTextToSpeechConversion(ModelInterface $model): void
    {
        if (!$model instanceof TextToSpeechConversionModelInterface) {
            throw new \InvalidArgumentException(
                'Model must implement TextToSpeechConversionModelInterface for text-to-speech conversion'
            );
        }
    }

    /**
     * Validates that a model implements SpeechGenerationModelInterface.
     *
     * @since n.e.x.t
     *
     * @param ModelInterface $model The model to validate.
     * @return void
     * @phpstan-assert SpeechGenerationModelInterface $model
     *
     * @throws \InvalidArgumentException If the model doesn't implement the required interface.
     */
    public static function validateSpeechGeneration(ModelInterface $model): void
    {
        if (!$model instanceof SpeechGenerationModelInterface) {
            throw new \InvalidArgumentException(
                'Model must implement SpeechGenerationModelInterface for speech generation'
            );
        }
    }

    /**
     * Validates that a model implements TextGenerationModelInterface for operations.
     *
     * @since n.e.x.t
     *
     * @param ModelInterface $model The model to validate.
     * @return void
     * @phpstan-assert TextGenerationModelInterface $model
     *
     * @throws \InvalidArgumentException If the model doesn't implement the required interface.
     */
    public static function validateTextGenerationOperation(ModelInterface $model): void
    {
        if (!$model instanceof TextGenerationModelInterface) {
            throw new \InvalidArgumentException(
                'Model must implement TextGenerationModelInterface for text generation operations'
            );
        }
    }

    /**
     * Validates that a model implements ImageGenerationModelInterface for operations.
     *
     * @since n.e.x.t
     *
     * @param ModelInterface $model The model to validate.
     * @return void
     * @phpstan-assert ImageGenerationModelInterface $model
     *
     * @throws \InvalidArgumentException If the model doesn't implement the required interface.
     */
    public static function validateImageGenerationOperation(ModelInterface $model): void
    {
        if (!$model instanceof ImageGenerationModelInterface) {
            throw new \InvalidArgumentException(
                'Model must implement ImageGenerationModelInterface for image generation operations'
            );
        }
    }

    /**
     * Validates that a model implements TextToSpeechConversionOperationModelInterface for operations.
     *
     * @since n.e.x.t
     *
     * @param ModelInterface $model The model to validate.
     * @return void
     * @phpstan-assert TextToSpeechConversionOperationModelInterface $model
     *
     * @throws \InvalidArgumentException If the model doesn't implement the required interface.
     */
    public static function validateTextToSpeechConversionOperation(ModelInterface $model): void
    {
        if (!$model instanceof TextToSpeechConversionOperationModelInterface) {
            throw new \InvalidArgumentException(
                'Model must implement TextToSpeechConversionOperationModelInterface ' .
                'for text-to-speech conversion operations'
            );
        }
    }

    /**
     * Validates that a model implements SpeechGenerationOperationModelInterface for operations.
     *
     * @since n.e.x.t
     *
     * @param ModelInterface $model The model to validate.
     * @return void
     * @phpstan-assert SpeechGenerationOperationModelInterface $model
     *
     * @throws \InvalidArgumentException If the model doesn't implement the required interface.
     */
    public static function validateSpeechGenerationOperation(ModelInterface $model): void
    {
        if (!$model instanceof SpeechGenerationOperationModelInterface) {
            throw new \InvalidArgumentException(
                'Model must implement SpeechGenerationOperationModelInterface ' .
                'for speech generation operations'
            );
        }
    }
}

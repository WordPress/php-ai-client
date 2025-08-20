<?php

declare(strict_types=1);

namespace WordPress\AiClient\Utils;

use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts\SpeechGenerationModelInterface;
use WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts\SpeechGenerationOperationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionModelInterface;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionOperationModelInterface;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationOperationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationOperationModelInterface;

/**
 * Utility class for validating model interface implementations.
 *
 * Centralizes interface validation logic to reduce code duplication
 * and provide consistent error messages across the AI Client.
 *
 * @since n.e.x.t
 */
class InterfaceValidator
{
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

<?php

declare(strict_types=1);

namespace WordPress\AiClient;

use Generator;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Main AI Client class providing both fluent and traditional APIs for AI operations.
 *
 * This class serves as the primary entry point for AI operations, offering:
 * - Fluent API for easy-to-read chained method calls
 * - Traditional API for array-based configuration (WordPress style)
 * - Integration with provider registry for model discovery
 *
 * @since n.e.x.t
 */
class AiClient
{
    /**
     * @var ProviderRegistry|null The default provider registry instance.
     */
    private static ?ProviderRegistry $defaultRegistry = null;

    /**
     * Gets the default provider registry instance.
     *
     * @since n.e.x.t
     *
     * @return ProviderRegistry The default provider registry.
     */
    public static function defaultRegistry(): ProviderRegistry
    {
        if (self::$defaultRegistry === null) {
            self::$defaultRegistry = new ProviderRegistry();
        }

        return self::$defaultRegistry;
    }

    /**
     * Sets the default provider registry instance.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The provider registry to set as default.
     */
    public static function setDefaultRegistry(ProviderRegistry $registry): void
    {
        self::$defaultRegistry = $registry;
    }

    /**
     * Checks if a provider is configured and available for use.
     *
     * @since n.e.x.t
     *
     * @param ProviderAvailabilityInterface $availability The provider availability instance to check.
     * @return bool True if the provider is configured and available, false otherwise.
     */
    public static function isConfigured(ProviderAvailabilityInterface $availability): bool
    {
        return $availability->isConfigured();
    }

    /**
     * Creates a new prompt builder for fluent API usage.
     *
     * This method will be implemented once PromptBuilder is available from PR #49.
     *
     * @since n.e.x.t
     *
     * @param string|Message|null $text Optional initial prompt text or message.
     * @return object PromptBuilder instance (type will be updated when PromptBuilder is available).
     *
     * @throws \RuntimeException When PromptBuilder is not yet available.
     */
    public static function prompt($text = null)
    {
        throw new \RuntimeException(
            'PromptBuilder is not yet available. This method depends on PR #49.'
        );
    }

    /**
     * Generates content using a unified API that delegates to specific generation methods.
     *
     * This method automatically detects the model's capabilities and routes to the
     * appropriate generation method (text, image, etc.).
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content.
     * @param ModelInterface $model The model to use for generation.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid or model type is unsupported.
     */
    public static function generateResult($prompt, ModelInterface $model): GenerativeAiResult
    {
        // Delegate to text generation if model supports it
        if ($model instanceof TextGenerationModelInterface) {
            return self::generateTextResult($prompt, $model);
        }

        // Delegate to image generation if model supports it
        if ($model instanceof ImageGenerationModelInterface) {
            return self::generateImageResult($prompt, $model);
        }

        // If no supported interface is found, throw an exception
        throw new \InvalidArgumentException(
            'Model must implement at least one supported generation interface (TextGeneration, ImageGeneration)'
        );
    }

    /**
     * Generates text using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function generateTextResult($prompt, ModelInterface $model = null): GenerativeAiResult
    {
        // Convert prompt to standardized Message array format
        $messages = self::normalizePromptToMessages($prompt);

        // Get model - either provided or auto-discovered
        $resolvedModel = $model ?? self::findSuitableTextModel();

        // Ensure the model supports text generation
        if (!$resolvedModel instanceof TextGenerationModelInterface) {
            throw new \InvalidArgumentException(
                'Model must implement TextGenerationModelInterface for text generation'
            );
        }

        // Generate the result using the model
        return $resolvedModel->generateTextResult($messages);
    }

    /**
     * Streams text generation using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return Generator<GenerativeAiResult> Generator yielding partial text generation results.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function streamGenerateTextResult($prompt, ModelInterface $model = null): Generator
    {
        // Convert prompt to standardized Message array format
        $messages = self::normalizePromptToMessages($prompt);

        // Get model - either provided or auto-discovered
        $resolvedModel = $model ?? self::findSuitableTextModel();

        // Ensure the model supports text generation
        if (!$resolvedModel instanceof TextGenerationModelInterface) {
            throw new \InvalidArgumentException(
                'Model must implement TextGenerationModelInterface for text generation'
            );
        }

        // Stream the results using the model
        yield from $resolvedModel->streamGenerateTextResult($messages);
    }

    /**
     * Generates an image using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function generateImageResult($prompt, ModelInterface $model = null): GenerativeAiResult
    {
        // Convert prompt to standardized Message array format
        $messages = self::normalizePromptToMessages($prompt);

        // Get model - either provided or auto-discovered
        $resolvedModel = $model ?? self::findSuitableImageModel();

        // Ensure the model supports image generation
        if (!$resolvedModel instanceof ImageGenerationModelInterface) {
            throw new \InvalidArgumentException(
                'Model must implement ImageGenerationModelInterface for image generation'
            );
        }

        // Generate the result using the model
        return $resolvedModel->generateImageResult($messages);
    }

    /**
     * Creates a generation operation for async processing.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content.
     * @param ModelInterface $model The model to use for generation.
     * @return GenerativeAiOperation The operation for async processing.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     */
    public static function generateOperation($prompt, ModelInterface $model): GenerativeAiOperation
    {
        // Convert prompt to standardized Message array format
        $messages = self::normalizePromptToMessages($prompt);

        // Create and return the operation (starting state, no result yet)
        return new GenerativeAiOperation(
            uniqid('op_', true),
            OperationStateEnum::starting(),
            null
        );
    }

    /**
     * Normalizes various prompt formats into a standardized Message array.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt to normalize.
     * @return list<Message> Array of Message objects.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     */
    private static function normalizePromptToMessages($prompt): array
    {
        if (is_string($prompt)) {
            // Convert string to UserMessage with single text MessagePart
            return [new UserMessage([new MessagePart($prompt)])];
        }

        if ($prompt instanceof Message) {
            return [$prompt];
        }

        if ($prompt instanceof MessagePart) {
            // Convert MessagePart to UserMessage
            return [new UserMessage([$prompt])];
        }

        if (is_array($prompt)) {
            // Handle array of Messages or MessageParts
            $messages = [];
            foreach ($prompt as $item) {
                if ($item instanceof Message) {
                    $messages[] = $item;
                } elseif ($item instanceof MessagePart) {
                    $messages[] = new UserMessage([$item]);
                } else {
                    throw new \InvalidArgumentException(
                        'Array must contain only Message or MessagePart objects'
                    );
                }
            }
            return $messages;
        }

        throw new \InvalidArgumentException('Invalid prompt format provided');
    }

    /**
     * Finds a suitable text generation model.
     *
     * @since n.e.x.t
     *
     * @return ModelInterface A suitable text generation model.
     *
     * @throws \RuntimeException If no suitable model is found.
     */
    private static function findSuitableTextModel(): ModelInterface
    {
        $requirements = new ModelRequirements([CapabilityEnum::textGeneration()], []);
        $providerModelsMetadata = self::defaultRegistry()->findModelsMetadataForSupport($requirements);

        if (empty($providerModelsMetadata)) {
            throw new \RuntimeException('No text generation models available');
        }

        // Get the first suitable provider and model
        $providerMetadata = $providerModelsMetadata[0];
        $models = $providerMetadata->getModels();

        if (empty($models)) {
            throw new \RuntimeException('No models available in provider');
        }

        return self::defaultRegistry()->getProviderModel(
            $providerMetadata->getProvider()->getId(),
            $models[0]->getId()
        );
    }

    /**
     * Finds a suitable image generation model.
     *
     * @since n.e.x.t
     *
     * @return ModelInterface A suitable image generation model.
     *
     * @throws \RuntimeException If no suitable model is found.
     */
    private static function findSuitableImageModel(): ModelInterface
    {
        $requirements = new ModelRequirements([CapabilityEnum::imageGeneration()], []);
        $providerModelsMetadata = self::defaultRegistry()->findModelsMetadataForSupport($requirements);

        if (empty($providerModelsMetadata)) {
            throw new \RuntimeException('No image generation models available');
        }

        // Get the first suitable provider and model
        $providerMetadata = $providerModelsMetadata[0];
        $models = $providerMetadata->getModels();

        if (empty($models)) {
            throw new \RuntimeException('No models available in provider');
        }

        return self::defaultRegistry()->getProviderModel(
            $providerMetadata->getProvider()->getId(),
            $models[0]->getId()
        );
    }
}

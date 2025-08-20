<?php

declare(strict_types=1);

namespace WordPress\AiClient;

use Generator;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
use WordPress\AiClient\Operations\OperationFactory;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts\SpeechGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Utils\Models;
use WordPress\AiClient\Utils\PromptNormalizer;

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
     * This method will return an actual PromptBuilder instance once PR #49 is merged.
     * The traditional API methods in this class will then delegate to PromptBuilder
     * rather than implementing their own generation logic.
     *
     * @since n.e.x.t
     *
     * @param string|Message|null $text Optional initial prompt text or message.
     * @return object PromptBuilder instance (type will be updated when PromptBuilder is available).
     *
     * @throws \RuntimeException Until PromptBuilder integration is complete.
     */
    public static function prompt($text = null)
    {
        throw new \RuntimeException(
            'PromptBuilder integration pending. This method will return an actual PromptBuilder ' .
            'instance once PR #49 is merged, enabling the fluent API pattern.'
        );
    }

    /**
     * Generates content using a unified API that automatically detects model capabilities.
     *
     * This method uses simple type checking to route to the appropriate generation method.
     * In the future, this will be refactored to delegate to PromptBuilder when PR #49 is merged.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content.
     * @param ModelInterface $model The model to use for generation.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the model doesn't support any known generation type.
     */
    public static function generateResult($prompt, ModelInterface $model): GenerativeAiResult
    {
        // Simple type checking instead of over-engineered resolver
        if ($model instanceof TextGenerationModelInterface) {
            return self::generateTextResult($prompt, $model);
        }

        if ($model instanceof ImageGenerationModelInterface) {
            return self::generateImageResult($prompt, $model);
        }

        if ($model instanceof TextToSpeechConversionModelInterface) {
            return self::convertTextToSpeechResult($prompt, $model);
        }

        if ($model instanceof SpeechGenerationModelInterface) {
            return self::generateSpeechResult($prompt, $model);
        }

        throw new \InvalidArgumentException(
            'Model must implement at least one supported generation interface ' .
            '(TextGeneration, ImageGeneration, TextToSpeechConversion, SpeechGeneration)'
        );
    }

    /**
     * Creates a new message builder for fluent API usage.
     *
     * This method will be implemented once MessageBuilder is available.
     * MessageBuilder will provide a fluent interface for constructing complex
     * messages with multiple parts, attachments, and metadata.
     *
     * @since n.e.x.t
     *
     * @param string|null $text Optional initial message text.
     * @return object MessageBuilder instance (type will be updated when MessageBuilder is available).
     *
     * @throws \RuntimeException When MessageBuilder is not yet available.
     */
    public static function message(?string $text = null)
    {
        throw new \RuntimeException(
            'MessageBuilder is not yet available. This method depends on builder infrastructure. ' .
            'Use direct generation methods (generateTextResult, generateImageResult, etc.) for now.'
        );
    }

    /**
     * Template method for executing generation operations.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @param string $type The generation type.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    private static function executeGeneration($prompt, ?ModelInterface $model, string $type): GenerativeAiResult
    {
        // Convert prompt to standardized Message array format
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Map type to specific methods
        switch ($type) {
            case 'text':
                $resolvedModel = $model ?? Models::findTextModel(self::defaultRegistry());
                Models::validateTextGeneration($resolvedModel);
                return $resolvedModel->generateTextResult($messageList);

            case 'image':
                $resolvedModel = $model ?? Models::findImageModel(self::defaultRegistry());
                Models::validateImageGeneration($resolvedModel);
                return $resolvedModel->generateImageResult($messageList);

            case 'speech':
                $resolvedModel = $model ?? Models::findSpeechModel(self::defaultRegistry());
                Models::validateSpeechGeneration($resolvedModel);
                return $resolvedModel->generateSpeechResult($messageList);

            default:
                throw new \InvalidArgumentException("Unsupported generation type: {$type}");
        }
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
        return self::executeGeneration($prompt, $model, 'text');
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
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Get model - either provided or auto-discovered
        $resolvedModel = $model ?? Models::findTextModel(self::defaultRegistry());

        // Validate model supports text generation
        Models::validateTextGeneration($resolvedModel);

        // Stream the results using the model
        yield from $resolvedModel->streamGenerateTextResult($messageList);
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
        return self::executeGeneration($prompt, $model, 'image');
    }

    /**
     * Converts text to speech using the traditional API approach.
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
    public static function convertTextToSpeechResult($prompt, ModelInterface $model = null): GenerativeAiResult
    {
        // Convert prompt to standardized Message array format
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Get model - either provided or auto-discovered
        $resolvedModel = $model ?? Models::findTextToSpeechModel(self::defaultRegistry());

        // Validate model supports text-to-speech conversion
        Models::validateTextToSpeechConversion($resolvedModel);

        // Generate the result using the model
        return $resolvedModel->convertTextToSpeechResult($messageList);
    }

    /**
     * Generates speech using the traditional API approach.
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
    public static function generateSpeechResult($prompt, ModelInterface $model = null): GenerativeAiResult
    {
        return self::executeGeneration($prompt, $model, 'speech');
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
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Create operation using factory
        return OperationFactory::createGenericOperation($messageList);
    }

    /**
     * Creates a text generation operation for async processing.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content.
     * @param ModelInterface $model The model to use for text generation.
     * @return GenerativeAiOperation The operation for async text processing.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid or model doesn't support text generation.
     */
    public static function generateTextOperation($prompt, ModelInterface $model): GenerativeAiOperation
    {
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        Models::validateTextGenerationOperation($model);
        return OperationFactory::createTextOperation($messageList);
    }

    /**
     * Creates an image generation operation for async processing.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content.
     * @param ModelInterface $model The model to use for image generation.
     * @return GenerativeAiOperation The operation for async image processing.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid or model doesn't support image generation.
     */
    public static function generateImageOperation($prompt, ModelInterface $model): GenerativeAiOperation
    {
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        Models::validateImageGenerationOperation($model);
        return OperationFactory::createImageOperation($messageList);
    }

    /**
     * Creates a text-to-speech conversion operation for async processing.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content.
     * @param ModelInterface $model The model to use for text-to-speech conversion.
     * @return GenerativeAiOperation The operation for async text-to-speech processing.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid or model doesn't support text-to-speech.
     */
    public static function convertTextToSpeechOperation($prompt, ModelInterface $model): GenerativeAiOperation
    {
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        Models::validateTextToSpeechConversionOperation($model);
        return OperationFactory::createTextToSpeechOperation($messageList);
    }

    /**
     * Creates a speech generation operation for async processing.
     *
     * @since n.e.x.t
     *
     * @param string|MessagePart|MessagePart[]|Message|Message[] $prompt The prompt content.
     * @param ModelInterface $model The model to use for speech generation.
     * @return GenerativeAiOperation The operation for async speech processing.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid or model doesn't support speech generation.
     */
    public static function generateSpeechOperation($prompt, ModelInterface $model): GenerativeAiOperation
    {
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        Models::validateSpeechGenerationOperation($model);
        return OperationFactory::createSpeechOperation($messageList);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient;

use Generator;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Operations\DTO\EmbeddingOperation;
use WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
use WordPress\AiClient\Operations\OperationFactory;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Utils\EmbeddingInputNormalizer;
use WordPress\AiClient\Utils\GenerationStrategyResolver;
use WordPress\AiClient\Utils\InterfaceValidator;
use WordPress\AiClient\Utils\ModelDiscovery;
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
     * This method will be implemented once PromptBuilder is available from PR #49.
     * When available, PromptBuilder will support all generation types including:
     * - Text generation via generateTextResult()
     * - Image generation via generateImageResult()
     * - Text-to-speech via convertTextToSpeechResult()
     * - Speech generation via generateSpeechResult()
     * - Embedding generation via generateEmbeddingsResult()
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
            'PromptBuilder is not yet available. This method depends on PR #49. ' .
            'All generation methods (text, image, text-to-speech, speech, embeddings) are ready for integration.'
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
        // Use strategy resolver to determine the appropriate method
        $method = GenerationStrategyResolver::resolve($model);

        // Call the resolved method dynamically
        /** @var GenerativeAiResult */
        return self::$method($prompt, $model);
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
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Get model - either provided or auto-discovered
        $resolvedModel = $model ?? ModelDiscovery::findTextModel(self::defaultRegistry());

        // Validate model supports text generation
        InterfaceValidator::validateTextGeneration($resolvedModel);

        // Generate the result using the model
        /** @phpstan-ignore-next-line */
        return $resolvedModel->generateTextResult($messageList);
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
        $resolvedModel = $model ?? ModelDiscovery::findTextModel(self::defaultRegistry());

        // Validate model supports text generation
        InterfaceValidator::validateTextGeneration($resolvedModel);

        // Stream the results using the model
        /** @phpstan-ignore-next-line */
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
        // Convert prompt to standardized Message array format
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Get model - either provided or auto-discovered
        $resolvedModel = $model ?? ModelDiscovery::findImageModel(self::defaultRegistry());

        // Validate model supports image generation
        InterfaceValidator::validateImageGeneration($resolvedModel);

        // Generate the result using the model
        /** @phpstan-ignore-next-line */
        return $resolvedModel->generateImageResult($messageList);
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
        $resolvedModel = $model ?? ModelDiscovery::findTextToSpeechModel(self::defaultRegistry());

        // Validate model supports text-to-speech conversion
        InterfaceValidator::validateTextToSpeechConversion($resolvedModel);

        // Generate the result using the model
        /** @phpstan-ignore-next-line */
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
        // Convert prompt to standardized Message array format
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Get model - either provided or auto-discovered
        $resolvedModel = $model ?? ModelDiscovery::findSpeechModel(self::defaultRegistry());

        // Validate model supports speech generation
        InterfaceValidator::validateSpeechGeneration($resolvedModel);

        // Generate the result using the model
        /** @phpstan-ignore-next-line */
        return $resolvedModel->generateSpeechResult($messageList);
    }

    /**
     * Generates embeddings using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param string[]|Message[] $input The input data to generate embeddings for.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return EmbeddingResult The generation result.
     *
     * @throws \InvalidArgumentException If the input format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function generateEmbeddingsResult($input, ModelInterface $model = null): EmbeddingResult
    {
        // Normalize embedding input using specialized normalizer
        $messages = EmbeddingInputNormalizer::normalize($input);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Get model - either provided or auto-discovered
        $resolvedModel = $model ?? ModelDiscovery::findEmbeddingModel(self::defaultRegistry());

        // Validate model supports embedding generation
        InterfaceValidator::validateEmbeddingGeneration($resolvedModel);

        // Generate the result using the model
        /** @phpstan-ignore-next-line */
        return $resolvedModel->generateEmbeddingsResult($messageList);
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
        // Convert prompt to standardized Message array format
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Validate model supports text generation operations
        InterfaceValidator::validateTextGenerationOperation($model);

        // Create operation using factory
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
        // Convert prompt to standardized Message array format
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Validate model supports image generation operations
        InterfaceValidator::validateImageGenerationOperation($model);

        // Create operation using factory
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
        // Convert prompt to standardized Message array format
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Validate model supports text-to-speech conversion operations
        InterfaceValidator::validateTextToSpeechConversionOperation($model);

        // Create operation using factory
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
        // Convert prompt to standardized Message array format
        $messages = PromptNormalizer::normalize($prompt);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Validate model supports speech generation operations
        InterfaceValidator::validateSpeechGenerationOperation($model);

        // Create operation using factory
        return OperationFactory::createSpeechOperation($messageList);
    }

    /**
     * Creates an embedding generation operation for async processing.
     *
     * @since n.e.x.t
     *
     * @param string[]|Message[] $input The input data to generate embeddings for.
     * @param ModelInterface $model The model to use for embedding generation.
     * @return EmbeddingOperation The operation for async embedding processing.
     *
     * @throws \InvalidArgumentException If the input format is invalid or model doesn't support embedding generation.
     */
    public static function generateEmbeddingsOperation($input, ModelInterface $model): EmbeddingOperation
    {
        // Normalize embedding input using specialized normalizer
        $messages = EmbeddingInputNormalizer::normalize($input);
        /** @var list<Message> $messageList */
        $messageList = array_values($messages);

        // Validate model supports embedding generation operations
        InterfaceValidator::validateEmbeddingGenerationOperation($model);

        // Delegate to the model's operation method with proper list type
        /** @phpstan-ignore-next-line */
        return $model->generateEmbeddingsOperation($messageList);
    }
}

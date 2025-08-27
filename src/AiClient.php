<?php

declare(strict_types=1);

namespace WordPress\AiClient;

use Generator;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
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
 *
 * @phpstan-import-type MessageArrayShape from Message
 * @phpstan-import-type Prompt from PromptBuilder
 *
 * phpcs:ignore Generic.Files.LineLength.TooLong
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
            $registry = new ProviderRegistry();

            // TODO: Uncomment this once provider implementation PR is merged.
            //$registry->setHttpTransporter(HttpTransporterFactory::createTransporter());
            //$registry->registerProvider(AnthropicProvider::class);
            //$registry->registerProvider(GoogleProvider::class);
            //$registry->registerProvider(OpenAiProvider::class);

            self::$defaultRegistry = $registry;
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
     * @param Prompt $prompt Optional initial prompt content.
     * @return object PromptBuilder instance (type will be updated when PromptBuilder is available).
     *
     * @throws \RuntimeException Until PromptBuilder integration is complete.
     */
    public static function prompt($prompt = null)
    {
        return new PromptBuilder(self::defaultRegistry(), $prompt);
    }

    /**
     * Generates content using a unified API that automatically detects model capabilities.
     *
     * This method uses simple type checking to route to the appropriate generation method.
     * In the future, this will be refactored to delegate to PromptBuilder when PR #49 is merged.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the model doesn't support any known generation type.
     */
    public static function generateResult($prompt, ?ModelInterface $model = null): GenerativeAiResult
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
     * NOTE: This method currently uses PromptNormalizer directly, but will be refactored
     * to delegate to PromptBuilder once PR #49 is merged, following the architectural
     * pattern where traditional API methods wrap the fluent builder API.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @param string $type The generation type.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    private static function executeGeneration($prompt, ?ModelInterface $model, string $type): GenerativeAiResult
    {
        // TODO: Replace with PromptBuilder delegation once PR #49 is merged
        // This should become: return self::prompt($prompt)->usingModel($model)->generate();

        // Check if it's already a list of Messages
        if (PromptNormalizer::isMessagesList($prompt)) {
            $messages = $prompt;
        } else {
            // Otherwise normalize to a single Message and wrap in array
            // PHPStan needs help narrowing the type after isMessagesList() check
            /** @var string|MessagePart|Message|MessageArrayShape|list<string|MessagePart> $prompt */
            $message = PromptNormalizer::normalize($prompt);
            $messages = [$message];
        }

        // Map type to specific methods
        switch ($type) {
            case 'text':
                $resolvedModel = $model ?? Models::findTextModel(self::defaultRegistry());
                Models::validateTextGeneration($resolvedModel);
                return $resolvedModel->generateTextResult($messages);

            case 'image':
                $resolvedModel = $model ?? Models::findImageModel(self::defaultRegistry());
                Models::validateImageGeneration($resolvedModel);
                return $resolvedModel->generateImageResult($messages);

            case 'speech':
                $resolvedModel = $model ?? Models::findSpeechModel(self::defaultRegistry());
                Models::validateSpeechGeneration($resolvedModel);
                return $resolvedModel->generateSpeechResult($messages);

            default:
                throw new \InvalidArgumentException("Unsupported generation type: {$type}");
        }
    }

    /**
     * Generates text using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function generateTextResult($prompt, ?ModelInterface $model = null): GenerativeAiResult
    {
        return self::executeGeneration($prompt, $model, 'text');
    }

    /**
     * Streams text generation using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return Generator<GenerativeAiResult> Generator yielding partial text generation results.
     *
     * @throws \RuntimeException Always throws - streaming is not implemented yet.
     */
    public static function streamGenerateTextResult($prompt, ?ModelInterface $model = null): Generator
    {
        throw new \RuntimeException(
            'Text streaming is not implemented yet. Use generateTextResult() for non-streaming text generation.'
        );
    }

    /**
     * Generates an image using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function generateImageResult($prompt, ?ModelInterface $model = null): GenerativeAiResult
    {
        return self::executeGeneration($prompt, $model, 'image');
    }

    /**
     * Converts text to speech using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function convertTextToSpeechResult($prompt, ?ModelInterface $model = null): GenerativeAiResult
    {
        // TODO: Replace with PromptBuilder delegation once PR #49 is merged

        // Check if it's already a list of Messages
        if (PromptNormalizer::isMessagesList($prompt)) {
            $messages = $prompt;
        } else {
            // Otherwise normalize to a single Message and wrap in array
            // PHPStan needs help narrowing the type after isMessagesList() check
            /** @var string|MessagePart|Message|MessageArrayShape|list<string|MessagePart> $prompt */
            $message = PromptNormalizer::normalize($prompt);
            $messages = [$message];
        }

        // Get model - either provided or auto-discovered
        $resolvedModel = $model ?? Models::findTextToSpeechModel(self::defaultRegistry());

        // Validate model supports text-to-speech conversion
        Models::validateTextToSpeechConversion($resolvedModel);

        // Generate the result using the model
        return $resolvedModel->convertTextToSpeechResult($messages);
    }

    /**
     * Generates speech using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function generateSpeechResult($prompt, ?ModelInterface $model = null): GenerativeAiResult
    {
        return self::executeGeneration($prompt, $model, 'speech');
    }



    /**
     * Creates a generation operation for async processing.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiOperation The operation for async processing.
     *
     * @throws \RuntimeException Operations are not implemented yet.
     */
    public static function generateOperation($prompt, ?ModelInterface $model = null): GenerativeAiOperation
    {
        throw new \RuntimeException(
            'Operations are not implemented yet. This functionality is planned for a future release.'
        );
    }

    /**
     * Creates a text generation operation for async processing.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiOperation The operation for async text processing.
     *
     * @throws \RuntimeException Operations are not implemented yet.
     */
    public static function generateTextOperation($prompt, ?ModelInterface $model = null): GenerativeAiOperation
    {
        throw new \RuntimeException(
            'Text generation operations are not implemented yet. This functionality is planned for a future release.'
        );
    }

    /**
     * Creates an image generation operation for async processing.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiOperation The operation for async image processing.
     *
     * @throws \RuntimeException Operations are not implemented yet.
     */
    public static function generateImageOperation($prompt, ?ModelInterface $model = null): GenerativeAiOperation
    {
        throw new \RuntimeException(
            'Image generation operations are not implemented yet. This functionality is planned for a future release.'
        );
    }

    /**
     * Creates a text-to-speech conversion operation for async processing.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiOperation The operation for async text-to-speech processing.
     *
     * @throws \RuntimeException Operations are not implemented yet.
     */
    public static function convertTextToSpeechOperation($prompt, ?ModelInterface $model = null): GenerativeAiOperation
    {
        throw new \RuntimeException(
            'Text-to-speech conversion operations are not implemented yet. ' .
            'This functionality is planned for a future release.'
        );
    }

    /**
     * Creates a speech generation operation for async processing.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|null $model Optional specific model to use.
     * @return GenerativeAiOperation The operation for async speech processing.
     *
     * @throws \RuntimeException Operations are not implemented yet.
     */
    public static function generateSpeechOperation($prompt, ?ModelInterface $model = null): GenerativeAiOperation
    {
        throw new \RuntimeException(
            'Speech generation operations are not implemented yet. This functionality is planned for a future release.'
        );
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient;

use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts\SpeechGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Main AI Client class providing both fluent and traditional APIs for AI operations.
 *
 * This class serves as the primary entry point for AI operations, offering:
 * - Fluent API for easy-to-read chained method calls
 * - Traditional API for array-based configuration (WordPress style)
 * - Integration with provider registry for model discovery
 * - Support for three model specification approaches
 *
 * All model requirements analysis and capability matching is handled
 * automatically by the PromptBuilder, which provides intelligent model
 * discovery based on prompt content and configuration.
 *
 * ## Model Specification Approaches
 *
 * ### 1. Specific Model Instance
 * Use a specific ModelInterface instance when you know exactly which model to use:
 * ```php
 * $model = $registry->getProvider('openai')->getModel('gpt-4');
 * $result = AiClient::generateTextResult('What is PHP?', $model);
 * ```
 *
 * ### 2. ModelConfig for Auto-Discovery
 * Use ModelConfig to specify requirements and let the system discover the best model:
 * ```php
 * $config = new ModelConfig();
 * $config->setTemperature(0.7);
 * $config->setMaxTokens(150);
 *
 * $result = AiClient::generateTextResult('What is PHP?', $config);
 * ```
 *
 * ### 3. Automatic Discovery (Default)
 * Pass null or omit the parameter for intelligent model discovery based on prompt content:
 * ```php
 * // System analyzes prompt and selects appropriate model automatically
 * $result = AiClient::generateTextResult('What is PHP?');
 * $imageResult = AiClient::generateImageResult('A sunset over mountains');
 * ```
 *
 * ## Fluent API Examples
 * ```php
 * // Fluent API with automatic model discovery
 * $result = AiClient::prompt('Generate an image of a sunset')
 *     ->usingTemperature(0.7)
 *     ->generateImageResult();
 *
 * // Fluent API with specific model
 * $result = AiClient::prompt('What is PHP?')
 *     ->usingModel($specificModel)
 *     ->usingTemperature(0.5)
 *     ->generateTextResult();
 *
 * // Fluent API with model configuration
 * $result = AiClient::prompt('Explain quantum physics')
 *     ->usingModelConfig($config)
 *     ->generateTextResult();
 * ```
 *
 * @since n.e.x.t
 *
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
     * Validates that parameter is ModelInterface, ModelConfig, or null.
     *
     * @param mixed $modelOrConfig The parameter to validate.
     * @return void
     * @throws \InvalidArgumentException If parameter is invalid type.
     */
    private static function validateModelOrConfigParameter($modelOrConfig): void
    {
        if (
            $modelOrConfig !== null
            && !$modelOrConfig instanceof ModelInterface
            && !$modelOrConfig instanceof ModelConfig
        ) {
            throw new \InvalidArgumentException(
                'Parameter must be a ModelInterface instance (specific model), ' .
                'ModelConfig instance (for auto-discovery), or null (default auto-discovery). ' .
                sprintf('Received: %s', is_object($modelOrConfig) ? get_class($modelOrConfig) : gettype($modelOrConfig))
            );
        }
    }

    /**
     * Configures PromptBuilder based on model/config parameter type.
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|ModelConfig|null $modelOrConfig The model or config parameter.
     * @return PromptBuilder Configured prompt builder.
     */
    private static function configurePromptBuilder($prompt, $modelOrConfig): PromptBuilder
    {
        $builder = self::prompt($prompt);

        if ($modelOrConfig instanceof ModelInterface) {
            $builder->usingModel($modelOrConfig);
        } elseif ($modelOrConfig instanceof ModelConfig) {
            $builder->usingModelConfig($modelOrConfig);
        }
        // null case: use default model discovery

        return $builder;
    }

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

            // Provider registration will be enabled once concrete provider implementations are available.
            // This follows the pattern established in the provider registry architecture.
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
     * @return PromptBuilder The prompt builder instance.
     */
    public static function prompt($prompt = null): PromptBuilder
    {
        return new PromptBuilder(self::defaultRegistry(), $prompt);
    }

    /**
     * Generates content using a unified API that automatically detects model capabilities.
     *
     * When no model is provided, this method delegates to PromptBuilder for intelligent
     * model discovery based on prompt content and configuration. When a model is provided,
     * it infers the capability from the model's interfaces and delegates to the capability-based method.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|ModelConfig|null $modelOrConfig Optional specific model to use,
     *                                                        or model configuration for auto-discovery,
     *                                                        or null for defaults.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the provided model doesn't support any known generation type.
     * @throws \RuntimeException If no suitable model can be found for the prompt.
     */
    public static function generateResult($prompt, $modelOrConfig = null): GenerativeAiResult
    {
        self::validateModelOrConfigParameter($modelOrConfig);

        // Route to PromptBuilder for ModelConfig and null cases
        if ($modelOrConfig instanceof ModelConfig || $modelOrConfig === null) {
            return self::configurePromptBuilder($prompt, $modelOrConfig)->generateResult();
        }

        // Specific model provided: Infer capability from model interfaces and delegate
        $model = $modelOrConfig;
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
            sprintf(
                'Model "%s" must implement at least one supported generation interface ' .
                '(TextGeneration, ImageGeneration, TextToSpeechConversion, SpeechGeneration)',
                $model->metadata()->getId()
            )
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
     * Generates text using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|ModelConfig|null $modelOrConfig Optional specific model to use,
     *                                                        or model configuration for auto-discovery,
     *                                                        or null for defaults.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function generateTextResult($prompt, $modelOrConfig = null): GenerativeAiResult
    {
        self::validateModelOrConfigParameter($modelOrConfig);
        return self::configurePromptBuilder($prompt, $modelOrConfig)->generateTextResult();
    }


    /**
     * Generates an image using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|ModelConfig|null $modelOrConfig Optional specific model to use,
     *                                                        or model configuration for auto-discovery,
     *                                                        or null for defaults.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function generateImageResult($prompt, $modelOrConfig = null): GenerativeAiResult
    {
        self::validateModelOrConfigParameter($modelOrConfig);
        return self::configurePromptBuilder($prompt, $modelOrConfig)->generateImageResult();
    }

    /**
     * Converts text to speech using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|ModelConfig|null $modelOrConfig Optional specific model to use,
     *                                                        or model configuration for auto-discovery,
     *                                                        or null for defaults.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function convertTextToSpeechResult($prompt, $modelOrConfig = null): GenerativeAiResult
    {
        self::validateModelOrConfigParameter($modelOrConfig);
        return self::configurePromptBuilder($prompt, $modelOrConfig)->convertTextToSpeechResult();
    }

    /**
     * Generates speech using the traditional API approach.
     *
     * @since n.e.x.t
     *
     * @param Prompt $prompt The prompt content.
     * @param ModelInterface|ModelConfig|null $modelOrConfig Optional specific model to use,
     *                                                        or model configuration for auto-discovery,
     *                                                        or null for defaults.
     * @return GenerativeAiResult The generation result.
     *
     * @throws \InvalidArgumentException If the prompt format is invalid.
     * @throws \RuntimeException If no suitable model is found.
     */
    public static function generateSpeechResult($prompt, $modelOrConfig = null): GenerativeAiResult
    {
        self::validateModelOrConfigParameter($modelOrConfig);
        return self::configurePromptBuilder($prompt, $modelOrConfig)->generateSpeechResult();
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers;

use InvalidArgumentException;
use WordPress\AiClient\Providers\Contracts\ProviderInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\DTO\ProviderModelsMetadata;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;

/**
 * Registry for managing AI providers and their models.
 *
 * This class provides a centralized way to register AI providers, discover
 * their capabilities, and find suitable models based on requirements.
 *
 * @since n.e.x.t
 */
class ProviderRegistry
{
    /**
     * @var array<string, class-string<ProviderInterface>> Mapping of provider IDs to class names.
     */
    private array $providerClassNames = [];


    /**
     * Registers a provider class with the registry.
     *
     * @since n.e.x.t
     *
     * @param class-string<ProviderInterface> $className The fully qualified provider class name implementing the
     * ProviderInterface
     * @throws InvalidArgumentException If the class doesn't exist or implement the required interface.
     */
    public function registerProvider(string $className): void
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException(
                sprintf('Provider class does not exist: %s', $className)
            );
        }

        // Validate that class implements ProviderInterface
        if (!is_subclass_of($className, ProviderInterface::class)) {
            throw new InvalidArgumentException(
                sprintf('Provider class must implement %s: %s', ProviderInterface::class, $className)
            );
        }

        $metadata = $className::metadata();

        if (!$metadata instanceof ProviderMetadata) {
            throw new InvalidArgumentException(
                sprintf('Provider must return ProviderMetadata from metadata() method: %s', $className)
            );
        }

        $this->providerClassNames[$metadata->getId()] = $className;
    }

    /**
     * Checks if a provider is registered.
     *
     * @since n.e.x.t
     *
     * @param string|class-string<ProviderInterface> $idOrClassName The provider ID or class name to check.
     * @return bool True if the provider is registered.
     */
    public function hasProvider(string $idOrClassName): bool
    {
        return isset($this->providerClassNames[$idOrClassName]) ||
            in_array($idOrClassName, $this->providerClassNames, true);
    }

    /**
     * Gets the class name for a registered provider.
     *
     * @since n.e.x.t
     *
     * @param string $id The provider ID.
     * @return string The provider class name.
     * @throws InvalidArgumentException If the provider is not registered.
     */
    public function getProviderClassName(string $id): string
    {
        if (!isset($this->providerClassNames[$id])) {
            throw new InvalidArgumentException(
                sprintf('Provider not registered: %s', $id)
            );
        }

        return $this->providerClassNames[$id];
    }

    /**
     * Checks if a provider is properly configured.
     *
     * @since n.e.x.t
     *
     * @param string|class-string<ProviderInterface> $idOrClassName The provider ID or class name.
     * @return bool True if the provider is configured and ready to use.
     */
    public function isProviderConfigured(string $idOrClassName): bool
    {
        try {
            $className = $this->resolveProviderClassName($idOrClassName);

            // Use static method from ProviderInterface
            /** @var class-string<ProviderInterface> $className */
            $availability = $className::availability();

            return $availability->isConfigured();
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Finds models across all providers that support the given requirements.
     *
     * @since n.e.x.t
     *
     * @param ModelRequirements $modelRequirements The requirements to match against.
     * @return list<ProviderModelsMetadata> List of provider models metadata that match requirements.
     */
    public function findModelsMetadataForSupport(ModelRequirements $modelRequirements): array
    {
        $results = [];

        foreach ($this->providerClassNames as $providerId => $className) {
            $providerResults = $this->findProviderModelsMetadataForSupport($providerId, $modelRequirements);
            if (!empty($providerResults)) {
                // Use static method from ProviderInterface
                /** @var class-string<ProviderInterface> $className */
                $providerMetadata = $className::metadata();

                $results[] = new ProviderModelsMetadata(
                    $providerMetadata,
                    $providerResults
                );
            }
        }

        return $results;
    }

    /**
     * Finds models within a specific provider that support the given requirements.
     *
     * @since n.e.x.t
     *
     * @param string $idOrClassName The provider ID or class name.
     * @param ModelRequirements $modelRequirements The requirements to match against.
     * @return list<ModelMetadata> List of model metadata that match requirements.
     */
    public function findProviderModelsMetadataForSupport(
        string $idOrClassName,
        ModelRequirements $modelRequirements
    ): array {
        $className = $this->resolveProviderClassName($idOrClassName);

        $modelMetadataDirectory = $className::modelMetadataDirectory();

        // Filter models that meet requirements
        $matchingModels = [];
        foreach ($modelMetadataDirectory->listModelMetadata() as $modelMetadata) {
            if ($modelMetadata->meetsRequirements($modelRequirements)) {
                $matchingModels[] = $modelMetadata;
            }
        }

        return $matchingModels;
    }

    /**
     * Gets a configured model instance from a provider.
     *
     * @since n.e.x.t
     *
     * @param string|class-string<ProviderInterface> $idOrClassName The provider ID or class name.
     * @param string $modelId The model identifier.
     * @param ModelConfig|null $modelConfig The model configuration.
     * @return ModelInterface The configured model instance.
     * @throws InvalidArgumentException If provider or model is not found.
     */
    public function getProviderModel(
        string $idOrClassName,
        string $modelId,
        ?ModelConfig $modelConfig = null
    ): ModelInterface {
        $className = $this->resolveProviderClassName($idOrClassName);

        // Use static method from ProviderInterface
        /** @var class-string<ProviderInterface> $className */
        return $className::model($modelId, $modelConfig);
    }

    /**
     * Gets the class name for a registered provider (handles both ID and class name input).
     *
     * @param string|class-string<ProviderInterface> $idOrClassName The provider ID or class name.
     * @return class-string<ProviderInterface> The provider class name.
     * @throws InvalidArgumentException If provider is not registered.
     */
    private function resolveProviderClassName(string $idOrClassName): string
    {
        // Handle both ID and class name
        $className = $this->providerClassNames[$idOrClassName] ?? $idOrClassName;

        if (!$this->hasProvider($idOrClassName)) {
            throw new InvalidArgumentException(
                sprintf('Provider not registered: %s', $idOrClassName)
            );
        }

        // Validate that class implements ProviderInterface (for PHPStan type safety)
        if (!is_subclass_of($className, ProviderInterface::class)) {
            throw new InvalidArgumentException(
                sprintf('Provider class must implement %s: %s', ProviderInterface::class, $className)
            );
        }

        return $className;
    }
}

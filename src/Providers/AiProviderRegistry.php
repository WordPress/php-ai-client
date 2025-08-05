<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers;

use InvalidArgumentException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\DTO\ProviderModelsMetadata;
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
class AiProviderRegistry
{
    /**
     * @var array<string, string> Mapping of provider IDs to class names.
     */
    private array $providerClassNames = [];

    /**
     * @var array<string, object> Cache of instantiated provider instances.
     */
    private array $providerInstances = [];

    /**
     * Registers a provider class with the registry.
     *
     * @since n.e.x.t
     *
     * @param string $className The fully qualified provider class name.
     * @throws InvalidArgumentException If the class doesn't exist or implement required interface.
     */
    public function registerProvider(string $className): void
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException(
                sprintf('Provider class does not exist: %s', $className)
            );
        }

        // TODO: Add interface validation when ProviderInterface is available

        // Get provider metadata to extract ID
        $instance = new $className();

        // Check if provider has metadata method
        if (!method_exists($instance, 'metadata')) {
            throw new InvalidArgumentException(
                sprintf('Provider must implement metadata() method: %s', $className)
            );
        }

        $metadata = $instance->metadata();

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
     * @param string $idOrClassName The provider ID or class name to check.
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
     * @param string $idOrClassName The provider ID or class name.
     * @return bool True if the provider is configured and ready to use.
     */
    public function isProviderConfigured(string $idOrClassName): bool
    {
        try {
            $this->getProviderInstance($idOrClassName);

            // TODO: Call availability() method when ProviderInterface is available
            // For now, assume configured if we can instantiate without exception
            return true;
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
                $providerInstance = $this->getProviderInstance($providerId);

                // Validate that provider has metadata method
                if (!method_exists($providerInstance, 'metadata')) {
                    continue;
                }

                $providerMetadata = $providerInstance->metadata();
                if (!$providerMetadata instanceof ProviderMetadata) {
                    continue;
                }

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
        $instance = $this->getProviderInstance($idOrClassName);

        // TODO: Get model metadata directory when ProviderInterface is available
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Gets a configured model instance from a provider.
     *
     * @since n.e.x.t
     *
     * @param string $idOrClassName The provider ID or class name.
     * @param string $modelId The model identifier.
     * @param ModelConfig $modelConfig The model configuration.
     * @return object The configured model instance.
     * @throws InvalidArgumentException If provider or model is not found.
     */
    public function getProviderModel(string $idOrClassName, string $modelId, ModelConfig $modelConfig): object
    {
        $instance = $this->getProviderInstance($idOrClassName);

        // TODO: Call model() method when ProviderInterface is available
        throw new InvalidArgumentException('Model instantiation not yet implemented');
    }

    /**
     * Gets or creates a provider instance.
     *
     * @param string $idOrClassName The provider ID or class name.
     * @return object The provider instance.
     * @throws InvalidArgumentException If provider is not registered.
     */
    private function getProviderInstance(string $idOrClassName): object
    {
        // Handle both ID and class name
        $className = $this->providerClassNames[$idOrClassName] ?? $idOrClassName;

        if (!$this->hasProvider($idOrClassName)) {
            throw new InvalidArgumentException(
                sprintf('Provider not registered: %s', $idOrClassName)
            );
        }

        // Use cached instance if available
        if (isset($this->providerInstances[$className])) {
            return $this->providerInstances[$className];
        }

        // Create and cache new instance
        $this->providerInstances[$className] = new $className();

        return $this->providerInstances[$className];
    }
}

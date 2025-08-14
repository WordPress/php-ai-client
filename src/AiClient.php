<?php

declare(strict_types=1);

namespace WordPress\AiClient;

use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\ProviderRegistry;

/**
 * Main entry point for the AI Client SDK.
 *
 * This class provides static methods for accessing AI functionality,
 * managing providers, and checking availability.
 *
 * @since n.e.x.t
 */
class AiClient
{
    /**
     * @var ProviderRegistry|null The singleton provider registry instance.
     */
    protected static ?ProviderRegistry $registry = null;

    /**
     * Gets the default provider registry.
     *
     * The registry manages all registered AI providers and models,
     * allowing for provider discovery and model selection based on capabilities.
     *
     * @since n.e.x.t
     *
     * @return ProviderRegistry The provider registry instance.
     */
    public static function defaultRegistry(): ProviderRegistry
    {
        if (self::$registry === null) {
            self::$registry = new ProviderRegistry();
        }

        return self::$registry;
    }

    /**
     * Checks if a provider is configured and available.
     *
     * This method delegates to the provider's availability checker
     * to determine if it has been properly configured and is ready for use.
     *
     * @since n.e.x.t
     *
     * @param ProviderAvailabilityInterface $availability The availability checker.
     * @return bool True if the provider is configured, false otherwise.
     */
    public static function isConfigured(ProviderAvailabilityInterface $availability): bool
    {
        return $availability->isConfigured();
    }

    /**
     * Sets a custom provider registry.
     *
     * This method allows replacing the default registry with a custom one,
     * useful for testing or custom provider configurations.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The registry to use.
     * @return void
     */
    public static function setRegistry(ProviderRegistry $registry): void
    {
        self::$registry = $registry;
    }

    /**
     * Resets the registry to null.
     *
     * This forces a new registry to be created on the next access,
     * useful for testing or clearing provider registrations.
     *
     * @since n.e.x.t
     *
     * @return void
     */
    public static function resetRegistry(): void
    {
        self::$registry = null;
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Contracts;

/**
 * Interface for checking provider availability.
 *
 * Determines whether a provider is configured and available
 * for use based on API keys, credentials, or other requirements.
 *
 * @since n.e.x.t
 */
interface ProviderAvailabilityInterface
{
    /**
     * Checks if the provider is configured.
     *
     * @since n.e.x.t
     *
     * @return bool True if the provider is configured and available, false otherwise.
     */
    public function isConfigured(): bool;
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers;

use Exception;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

/**
 * Class to check availability for an API-based provider via a test request to the endpoint to list models.
 *
 * @since n.e.x.t
 */
class ListModelsApiBasedProviderAvailability implements ProviderAvailabilityInterface
{
    /**
     * @var ModelMetadataDirectoryInterface The model metadata directory to use for checking availability.
     */
    private ModelMetadataDirectoryInterface $modelMetadataDirectory;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param ModelMetadataDirectoryInterface $modelMetadataDirectory The model metadata directory to use for checking
     *                                                                availability.
     */
    public function __construct(ModelMetadataDirectoryInterface $modelMetadataDirectory)
    {
        $this->modelMetadataDirectory = $modelMetadataDirectory;
    }

    /**
     * @inheritdoc
     */
    public function isConfigured(): bool
    {
        try {
            // Attempt to list models to check if the provider is available.
            $this->modelMetadataDirectory->listModelMetadata();
            return true;
        } catch (Exception $e) {
            // If an exception occurs, the provider is not available.
            return false;
        }
    }
}

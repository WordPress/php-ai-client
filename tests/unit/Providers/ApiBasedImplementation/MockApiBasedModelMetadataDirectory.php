<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\ApiBasedImplementation;

use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Mock class for testing AbstractApiBasedModelMetadataDirectory.
 */
class MockApiBasedModelMetadataDirectory extends AbstractApiBasedModelMetadataDirectory
{
    /**
     * @var array<string, ModelMetadata>
     */
    private array $mockModels;

    /**
     * @var ModelMetadata|null
     */
    private ?ModelMetadata $explicitModelMetadata;

    /**
     * @var int
     */
    private int $listRequestCount = 0;

    /**
     * @var int
     */
    private int $explicitModelMetadataLookupCount = 0;

    /**
     * Constructor.
     *
     * @param array<string, ModelMetadata> $mockModels
     */
    public function __construct(array $mockModels = [], ?ModelMetadata $explicitModelMetadata = null)
    {
        $this->mockModels = $mockModels;
        $this->explicitModelMetadata = $explicitModelMetadata;
    }

    /**
     * @inheritdoc
     */
    protected function sendListModelsRequest(): array
    {
        ++$this->listRequestCount;

        return $this->mockModels;
    }

    /**
     * @inheritdoc
     */
    protected function createModelMetadataForExplicitModelId(string $modelId): ?ModelMetadata
    {
        ++$this->explicitModelMetadataLookupCount;

        if ($this->explicitModelMetadata !== null && $this->explicitModelMetadata->getId() === $modelId) {
            return $this->explicitModelMetadata;
        }

        return parent::createModelMetadataForExplicitModelId($modelId);
    }

    /**
     * Returns the number of list request callbacks.
     *
     * @return int
     */
    public function getListRequestCount(): int
    {
        return $this->listRequestCount;
    }

    /**
     * Returns the number of explicit model metadata lookups.
     *
     * @return int
     */
    public function getExplicitModelMetadataLookupCount(): int
    {
        return $this->explicitModelMetadataLookupCount;
    }
}

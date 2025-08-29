<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use InvalidArgumentException;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Http\Contracts\WithHttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\WithRequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\Traits\WithHttpTransporterTrait;
use WordPress\AiClient\Providers\Http\Traits\WithRequestAuthenticationTrait;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Mock model metadata directory for testing.
 */
class MockModelMetadataDirectory implements
    ModelMetadataDirectoryInterface,
    WithHttpTransporterInterface,
    WithRequestAuthenticationInterface
{
    use WithHttpTransporterTrait;
    use WithRequestAuthenticationTrait;

    /**
     * @var array<string, ModelMetadata> Available models.
     */
    private array $models = [];

    /**
     * Constructor.
     *
     * @param array<string, ModelMetadata> $models Available models.
     */
    public function __construct(array $models = [])
    {
        $this->models = $models;
    }

    /**
     * {@inheritDoc}
     */
    public function listModelMetadata(): array
    {
        return array_values($this->models);
    }

    /**
     * {@inheritDoc}
     */
    public function hasModelMetadata(string $modelId): bool
    {
        return isset($this->models[$modelId]);
    }

    /**
     * {@inheritDoc}
     */
    public function getModelMetadata(string $modelId): ModelMetadata
    {
        if (!isset($this->models[$modelId])) {
            throw new InvalidArgumentException(
                sprintf('Model not found: %s', $modelId)
            );
        }

        return $this->models[$modelId];
    }
}

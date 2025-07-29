<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\DTO;

use WordPress\AiClient\Common\AbstractDataValueObject;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Represents metadata about a provider and its available models.
 *
 * This class combines provider information with the models that
 * the provider offers, facilitating model discovery and selection.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type ProviderMetadataArrayShape from ProviderMetadata
 * @phpstan-import-type ModelMetadataArrayShape from ModelMetadata
 *
 * @phpstan-type ProviderModelsMetadataArrayShape array{
 *     provider: ProviderMetadataArrayShape,
 *     models: array<int, ModelMetadataArrayShape>
 * }
 *
 * @extends AbstractDataValueObject<ProviderModelsMetadataArrayShape>
 */
final class ProviderModelsMetadata extends AbstractDataValueObject
{
    public const KEY_PROVIDER = 'provider';
    public const KEY_MODELS = 'models';

    /**
     * @var ProviderMetadata The provider metadata.
     */
    protected ProviderMetadata $provider;

    /**
     * @var ModelMetadata[] The available models.
     */
    protected array $models;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param ProviderMetadata $provider The provider metadata.
     * @param ModelMetadata[] $models The available models.
     */
    public function __construct(ProviderMetadata $provider, array $models)
    {
        $this->provider = $provider;
        $this->models = $models;
    }

    /**
     * Gets the provider metadata.
     *
     * @since n.e.x.t
     *
     * @return ProviderMetadata The provider metadata.
     */
    public function getProvider(): ProviderMetadata
    {
        return $this->provider;
    }

    /**
     * Gets the available models.
     *
     * @since n.e.x.t
     *
     * @return ModelMetadata[] The available models.
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                self::KEY_PROVIDER => ProviderMetadata::getJsonSchema(),
                self::KEY_MODELS => [
                    'type' => 'array',
                    'items' => ModelMetadata::getJsonSchema(),
                    'description' => 'The available models for this provider.',
                ],
            ],
            'required' => [self::KEY_PROVIDER, self::KEY_MODELS],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return ProviderModelsMetadataArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_PROVIDER => $this->provider->toArray(),
            self::KEY_MODELS => array_values(
                array_map(static fn(ModelMetadata $model): array => $model->toArray(), $this->models)
            ),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        return new self(
            ProviderMetadata::fromArray($array[self::KEY_PROVIDER]),
            array_map(
                static fn(array $modelData): ModelMetadata => ModelMetadata::fromArray($modelData),
                $array[self::KEY_MODELS]
            )
        );
    }
}

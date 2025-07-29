<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\DTO;

use WordPress\AiClient\Common\AbstractDataValueObject;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;

/**
 * Represents metadata about an AI provider.
 *
 * This class contains information about an AI provider, including its
 * unique identifier, display name, and type (cloud, server, or client).
 *
 * @since n.e.x.t
 *
 * @phpstan-type ProviderMetadataArrayShape array{
 *     id: string,
 *     name: string,
 *     type: string
 * }
 *
 * @extends AbstractDataValueObject<ProviderMetadataArrayShape>
 */
final class ProviderMetadata extends AbstractDataValueObject
{
    /**
     * @var string The provider's unique identifier.
     */
    protected string $id;

    /**
     * @var string The provider's display name.
     */
    protected string $name;

    /**
     * @var ProviderTypeEnum The provider type.
     */
    protected ProviderTypeEnum $type;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $id The provider's unique identifier.
     * @param string $name The provider's display name.
     * @param ProviderTypeEnum $type The provider type.
     */
    public function __construct(string $id, string $name, ProviderTypeEnum $type)
    {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Gets the provider's unique identifier.
     *
     * @since n.e.x.t
     *
     * @return string The provider ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the provider's display name.
     *
     * @since n.e.x.t
     *
     * @return string The provider name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the provider type.
     *
     * @since n.e.x.t
     *
     * @return ProviderTypeEnum The provider type.
     */
    public function getType(): ProviderTypeEnum
    {
        return $this->type;
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
                'id' => [
                    'type' => 'string',
                    'description' => 'The provider\'s unique identifier.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'The provider\'s display name.',
                ],
                'type' => [
                    'type' => 'string',
                    'enum' => ProviderTypeEnum::getValues(),
                    'description' => 'The provider type (cloud, server, or client).',
                ],
            ],
            'required' => ['id', 'name', 'type'],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return ProviderMetadataArrayShape
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, ['id', 'name', 'type']);

        return new self(
            $array['id'],
            $array['name'],
            ProviderTypeEnum::from($array['type'])
        );
    }
}

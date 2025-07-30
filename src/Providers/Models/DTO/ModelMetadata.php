<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\DTO;

use WordPress\AiClient\Common\AbstractDataValueObject;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Represents metadata about an AI model.
 *
 * This class contains information about a specific AI model, including
 * its identifier, display name, supported capabilities, and configuration options.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type SupportedOptionArrayShape from SupportedOption
 *
 * @phpstan-type ModelMetadataArrayShape array{
 *     id: string,
 *     name: string,
 *     supportedCapabilities: list<string>,
 *     supportedOptions: list<SupportedOptionArrayShape>
 * }
 *
 * @extends AbstractDataValueObject<ModelMetadataArrayShape>
 */
class ModelMetadata extends AbstractDataValueObject
{
    public const KEY_ID = 'id';
    public const KEY_NAME = 'name';
    public const KEY_SUPPORTED_CAPABILITIES = 'supportedCapabilities';
    public const KEY_SUPPORTED_OPTIONS = 'supportedOptions';

    /**
     * @var string The model's unique identifier.
     */
    protected string $id;

    /**
     * @var string The model's display name.
     */
    protected string $name;

    /**
     * @var CapabilityEnum[] The model's supported capabilities.
     */
    protected array $supportedCapabilities;

    /**
     * @var SupportedOption[] The model's supported configuration options.
     */
    protected array $supportedOptions;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $id The model's unique identifier.
     * @param string $name The model's display name.
     * @param CapabilityEnum[] $supportedCapabilities The model's supported capabilities.
     * @param SupportedOption[] $supportedOptions The model's supported configuration options.
     */
    public function __construct(string $id, string $name, array $supportedCapabilities, array $supportedOptions)
    {
        $this->id = $id;
        $this->name = $name;
        $this->supportedCapabilities = $supportedCapabilities;
        $this->supportedOptions = $supportedOptions;
    }

    /**
     * Gets the model's unique identifier.
     *
     * @since n.e.x.t
     *
     * @return string The model ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the model's display name.
     *
     * @since n.e.x.t
     *
     * @return string The model name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the model's supported capabilities.
     *
     * @since n.e.x.t
     *
     * @return CapabilityEnum[] The supported capabilities.
     */
    public function getSupportedCapabilities(): array
    {
        return $this->supportedCapabilities;
    }

    /**
     * Gets the model's supported configuration options.
     *
     * @since n.e.x.t
     *
     * @return SupportedOption[] The supported options.
     */
    public function getSupportedOptions(): array
    {
        return $this->supportedOptions;
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
                self::KEY_ID => [
                    'type' => 'string',
                    'description' => 'The model\'s unique identifier.',
                ],
                self::KEY_NAME => [
                    'type' => 'string',
                    'description' => 'The model\'s display name.',
                ],
                self::KEY_SUPPORTED_CAPABILITIES => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => CapabilityEnum::getValues(),
                    ],
                    'description' => 'The model\'s supported capabilities.',
                ],
                self::KEY_SUPPORTED_OPTIONS => [
                    'type' => 'array',
                    'items' => SupportedOption::getJsonSchema(),
                    'description' => 'The model\'s supported configuration options.',
                ],
            ],
            'required' => [self::KEY_ID, self::KEY_NAME, self::KEY_SUPPORTED_CAPABILITIES, self::KEY_SUPPORTED_OPTIONS],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return ModelMetadataArrayShape
     */
    public function toArray(): array
    {
        /** @phpstan-ignore-next-line return.type (array_map doesn't guarantee list, validation will be added later) */
        return [
            self::KEY_ID => $this->id,
            self::KEY_NAME => $this->name,
            self::KEY_SUPPORTED_CAPABILITIES => array_map(
                static fn(CapabilityEnum $capability): string => $capability->value,
                $this->supportedCapabilities
            ),
            self::KEY_SUPPORTED_OPTIONS => array_map(
                static fn(SupportedOption $option): array => $option->toArray(),
                $this->supportedOptions
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
        static::validateFromArrayData($array, [
            self::KEY_ID,
            self::KEY_NAME,
            self::KEY_SUPPORTED_CAPABILITIES,
            self::KEY_SUPPORTED_OPTIONS,
        ]);

        return new self(
            $array[self::KEY_ID],
            $array[self::KEY_NAME],
            array_map(
                static fn(string $capability): CapabilityEnum => CapabilityEnum::from($capability),
                $array[self::KEY_SUPPORTED_CAPABILITIES]
            ),
            array_map(
                static fn(array $optionData): SupportedOption => SupportedOption::fromArray($optionData),
                $array[self::KEY_SUPPORTED_OPTIONS]
            )
        );
    }
}

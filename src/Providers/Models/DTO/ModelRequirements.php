<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\DTO;

use WordPress\AiClient\Common\AbstractDataValueObject;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Represents requirements for selecting an AI model.
 *
 * This class defines the capabilities and options that are required
 * when selecting a model for a specific task.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type RequiredOptionArrayShape from RequiredOption
 *
 * @phpstan-type ModelRequirementsArrayShape array{
 *     requiredCapabilities: array<int, string>,
 *     requiredOptions: array<int, RequiredOptionArrayShape>
 * }
 *
 * @extends AbstractDataValueObject<ModelRequirementsArrayShape>
 */
class ModelRequirements extends AbstractDataValueObject
{
    public const KEY_REQUIRED_CAPABILITIES = 'requiredCapabilities';
    public const KEY_REQUIRED_OPTIONS = 'requiredOptions';

    /**
     * @var CapabilityEnum[] The required capabilities.
     */
    protected array $requiredCapabilities;

    /**
     * @var RequiredOption[] The required options.
     */
    protected array $requiredOptions;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param CapabilityEnum[] $requiredCapabilities The required capabilities.
     * @param RequiredOption[] $requiredOptions The required options.
     */
    public function __construct(array $requiredCapabilities, array $requiredOptions)
    {
        $this->requiredCapabilities = $requiredCapabilities;
        $this->requiredOptions = $requiredOptions;
    }

    /**
     * Gets the required capabilities.
     *
     * @since n.e.x.t
     *
     * @return CapabilityEnum[] The required capabilities.
     */
    public function getRequiredCapabilities(): array
    {
        return $this->requiredCapabilities;
    }

    /**
     * Gets the required options.
     *
     * @since n.e.x.t
     *
     * @return RequiredOption[] The required options.
     */
    public function getRequiredOptions(): array
    {
        return $this->requiredOptions;
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
                self::KEY_REQUIRED_CAPABILITIES => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => CapabilityEnum::getValues(),
                    ],
                    'description' => 'The required capabilities.',
                ],
                self::KEY_REQUIRED_OPTIONS => [
                    'type' => 'array',
                    'items' => RequiredOption::getJsonSchema(),
                    'description' => 'The required options.',
                ],
            ],
            'required' => [self::KEY_REQUIRED_CAPABILITIES, self::KEY_REQUIRED_OPTIONS],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return ModelRequirementsArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_REQUIRED_CAPABILITIES => array_values(array_map(
                static fn(CapabilityEnum $capability): string => $capability->value,
                $this->requiredCapabilities
            )),
            self::KEY_REQUIRED_OPTIONS => array_values(array_map(
                static fn(RequiredOption $option): array => $option->toArray(),
                $this->requiredOptions
            )),
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
            array_map(
                static fn(string $capability): CapabilityEnum => CapabilityEnum::from($capability),
                $array[self::KEY_REQUIRED_CAPABILITIES]
            ),
            array_map(
                static fn(array $optionData): RequiredOption => RequiredOption::fromArray($optionData),
                $array[self::KEY_REQUIRED_OPTIONS]
            )
        );
    }
}

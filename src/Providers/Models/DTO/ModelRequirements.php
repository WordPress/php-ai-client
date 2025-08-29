<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Represents requirements that implementing code has for AI model selection.
 *
 * This class defines the capabilities and options that a model must support
 * in order to be considered suitable for the implementing code's needs.
 *
 * @since 0.1.0
 *
 * @phpstan-import-type RequiredOptionArrayShape from RequiredOption
 *
 * @phpstan-type ModelRequirementsArrayShape array{
 *     requiredCapabilities: list<string>,
 *     requiredOptions: list<RequiredOptionArrayShape>
 * }
 *
 * @extends AbstractDataTransferObject<ModelRequirementsArrayShape>
 */
class ModelRequirements extends AbstractDataTransferObject
{
    public const KEY_REQUIRED_CAPABILITIES = 'requiredCapabilities';
    public const KEY_REQUIRED_OPTIONS = 'requiredOptions';

    /**
     * @var list<CapabilityEnum> The capabilities that the model must support.
     */
    protected array $requiredCapabilities;

    /**
     * @var list<RequiredOption> The options that the model must support with specific values.
     */
    protected array $requiredOptions;

    /**
     * Constructor.
     *
     * @since 0.1.0
     *
     * @param list<CapabilityEnum> $requiredCapabilities The capabilities that the model must support.
     * @param list<RequiredOption> $requiredOptions The options that the model must support with specific values.
     *
     * @throws InvalidArgumentException If arrays are not lists.
     */
    public function __construct(array $requiredCapabilities, array $requiredOptions)
    {
        if (!array_is_list($requiredCapabilities)) {
            throw new InvalidArgumentException('Required capabilities must be a list array.');
        }

        if (!array_is_list($requiredOptions)) {
            throw new InvalidArgumentException('Required options must be a list array.');
        }

        $this->requiredCapabilities = $requiredCapabilities;
        $this->requiredOptions = $requiredOptions;
    }

    /**
     * Gets the capabilities that the model must support.
     *
     * @since 0.1.0
     *
     * @return list<CapabilityEnum> The required capabilities.
     */
    public function getRequiredCapabilities(): array
    {
        return $this->requiredCapabilities;
    }

    /**
     * Gets the options that the model must support with specific values.
     *
     * @since 0.1.0
     *
     * @return list<RequiredOption> The required options.
     */
    public function getRequiredOptions(): array
    {
        return $this->requiredOptions;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
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
                    'description' => 'The capabilities that the model must support.',
                ],
                self::KEY_REQUIRED_OPTIONS => [
                    'type' => 'array',
                    'items' => RequiredOption::getJsonSchema(),
                    'description' => 'The options that the model must support with specific values.',
                ],
            ],
            'required' => [self::KEY_REQUIRED_CAPABILITIES, self::KEY_REQUIRED_OPTIONS],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     *
     * @return ModelRequirementsArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_REQUIRED_CAPABILITIES => array_map(
                static fn(CapabilityEnum $capability): string => $capability->value,
                $this->requiredCapabilities
            ),
            self::KEY_REQUIRED_OPTIONS => array_map(
                static fn(RequiredOption $option): array => $option->toArray(),
                $this->requiredOptions
            ),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_REQUIRED_CAPABILITIES, self::KEY_REQUIRED_OPTIONS]);

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

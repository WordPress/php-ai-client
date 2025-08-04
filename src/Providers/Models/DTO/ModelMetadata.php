<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\DTO;

use InvalidArgumentException;
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
     * @var list<CapabilityEnum> The model's supported capabilities.
     */
    protected array $supportedCapabilities;

    /**
     * @var list<SupportedOption> The model's supported configuration options.
     */
    protected array $supportedOptions;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $id The model's unique identifier.
     * @param string $name The model's display name.
     * @param list<CapabilityEnum> $supportedCapabilities The model's supported capabilities.
     * @param list<SupportedOption> $supportedOptions The model's supported configuration options.
     *
     * @throws InvalidArgumentException If arrays are not lists.
     */
    public function __construct(string $id, string $name, array $supportedCapabilities, array $supportedOptions)
    {
        if (!array_is_list($supportedCapabilities)) {
            throw new InvalidArgumentException('Supported capabilities must be a list array.');
        }

        if (!array_is_list($supportedOptions)) {
            throw new InvalidArgumentException('Supported options must be a list array.');
        }

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
     * @return list<CapabilityEnum> The supported capabilities.
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
     * @return list<SupportedOption> The supported options.
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
     * Checks whether this model meets the specified requirements.
     *
     * @since n.e.x.t
     *
     * @param ModelRequirements $requirements The requirements to check against.
     * @return bool True if the model meets all requirements, false otherwise.
     */
    public function meetsRequirements(ModelRequirements $requirements): bool
    {
        // Check if all required capabilities are supported
        foreach ($requirements->getRequiredCapabilities() as $requiredCapability) {
            if (!in_array($requiredCapability, $this->supportedCapabilities, true)) {
                return false;
            }
        }

        // Check if all required options are supported with the specified values
        foreach ($requirements->getRequiredOptions() as $requiredOption) {
            $supportedOption = $this->findSupportedOption($requiredOption->getName());

            // If the option is not supported at all, fail
            if ($supportedOption === null) {
                return false;
            }

            // Check if the required value is supported by this option
            if (!$supportedOption->isSupportedValue($requiredOption->getValue())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Finds a supported option by name.
     *
     * @since n.e.x.t
     *
     * @param string $name The option name to find.
     * @return SupportedOption|null The supported option, or null if not found.
     */
    private function findSupportedOption(string $name): ?SupportedOption
    {
        foreach ($this->supportedOptions as $supportedOption) {
            if ($supportedOption->getName() === $name) {
                return $supportedOption;
            }
        }

        return null;
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

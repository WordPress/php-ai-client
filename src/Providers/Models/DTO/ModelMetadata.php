<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Represents metadata about an AI model.
 *
 * This class contains information about a specific AI model, including
 * its identifier, display name, supported capabilities, and configuration options.
 *
 * @since 0.1.0
 *
 * @phpstan-import-type SupportedOptionArrayShape from SupportedOption
 *
 * @phpstan-type ModelMetadataArrayShape array{
 *     id: string,
 *     name: string,
 *     supportedCapabilities: list<string>,
 *     supportedOptions: list<SupportedOptionArrayShape>,
 *     contextWindow?: int
 * }
 *
 * @extends AbstractDataTransferObject<ModelMetadataArrayShape>
 */
class ModelMetadata extends AbstractDataTransferObject
{
    public const KEY_ID = 'id';
    public const KEY_NAME = 'name';
    public const KEY_SUPPORTED_CAPABILITIES = 'supportedCapabilities';
    public const KEY_SUPPORTED_OPTIONS = 'supportedOptions';
    public const KEY_CONTEXT_WINDOW = 'contextWindow';

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
     * @var int|null The model's context window, in tokens, or null if unknown.
     */
    protected ?int $contextWindow;


    /**
     * Constructor.
     *
     * @since 0.1.0
     *
     * @param string $id The model's unique identifier.
     * @param string $name The model's display name.
     * @param list<CapabilityEnum> $supportedCapabilities The model's supported capabilities.
     * @param list<SupportedOption> $supportedOptions The model's supported configuration options.
     * @param int|null $contextWindow The model's context window, in tokens, or null if unknown. This is the
     *                                total number of tokens (input plus output) the model can process for a
     *                                single request, distinct from the per-request output cap configured via
     *                                {@see \WordPress\AiClient\Providers\Models\DTO\ModelConfig::setMaxTokens()}.
     *
     * @throws InvalidArgumentException If arrays are not lists, or if the context window is not positive.
     */
    public function __construct(
        string $id,
        string $name,
        array $supportedCapabilities,
        array $supportedOptions,
        ?int $contextWindow = null
    ) {
        if (!array_is_list($supportedCapabilities)) {
            throw new InvalidArgumentException('Supported capabilities must be a list array.');
        }

        if (!array_is_list($supportedOptions)) {
            throw new InvalidArgumentException('Supported options must be a list array.');
        }

        if ($contextWindow !== null && $contextWindow < 1) {
            throw new InvalidArgumentException('Context window must be a positive integer.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->supportedCapabilities = $supportedCapabilities;
        $this->supportedOptions = $supportedOptions;
        $this->contextWindow = $contextWindow;
    }

    /**
     * Gets the model's unique identifier.
     *
     * @since 0.1.0
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
     * @since 0.1.0
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
     * @since 0.1.0
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
     * @since 0.1.0
     *
     * @return list<SupportedOption> The supported options.
     */
    public function getSupportedOptions(): array
    {
        return $this->supportedOptions;
    }

    /**
     * Gets the model's context window, in tokens.
     *
     * The context window is the total number of tokens (input plus output) the model can process
     * for a single request. It is distinct from the per-request output cap configured via
     * {@see \WordPress\AiClient\Providers\Models\DTO\ModelConfig::setMaxTokens()}, which limits only
     * the number of tokens the model may generate. Providers that do not publish a fixed context
     * window (for example self-hosted, user-configurable servers) return null.
     *
     * @since n.e.x.t
     *
     * @return int|null The context window in tokens, or null if unknown.
     */
    public function getContextWindow(): ?int
    {
        return $this->contextWindow;
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
                self::KEY_CONTEXT_WINDOW => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'The model\'s context window, in tokens (total input plus output).',
                ],
            ],
            'required' => [self::KEY_ID, self::KEY_NAME, self::KEY_SUPPORTED_CAPABILITIES, self::KEY_SUPPORTED_OPTIONS],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     *
     * @return ModelMetadataArrayShape
     */
    public function toArray(): array
    {
        $data = [
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

        if ($this->contextWindow !== null) {
            $data[self::KEY_CONTEXT_WINDOW] = $this->contextWindow;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
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
            ),
            $array[self::KEY_CONTEXT_WINDOW] ?? null
        );
    }

    /**
     * Performs a deep clone of the model metadata.
     *
     * This method ensures that supported option objects are cloned to prevent
     * modifications to the cloned metadata from affecting the original.
     *
     * @since 0.4.2
     */
    public function __clone()
    {
        $clonedOptions = [];
        foreach ($this->supportedOptions as $option) {
            $clonedOptions[] = clone $option;
        }
        $this->supportedOptions = $clonedOptions;
    }
}

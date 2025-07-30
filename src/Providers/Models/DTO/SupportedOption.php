<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\DTO;

use WordPress\AiClient\Common\AbstractDataValueObject;

/**
 * Represents a supported configuration option for an AI model.
 *
 * This class defines an option that a model supports, including its name
 * and the values that are valid for that option.
 *
 * @since n.e.x.t
 *
 * @phpstan-type SupportedOptionArrayShape array{
 *     name: string,
 *     supportedValues?: list<mixed>
 * }
 *
 * @extends AbstractDataValueObject<SupportedOptionArrayShape>
 */
class SupportedOption extends AbstractDataValueObject
{
    public const KEY_NAME = 'name';
    public const KEY_SUPPORTED_VALUES = 'supportedValues';

    /**
     * @var string The option name.
     */
    protected string $name;

    /**
     * @var mixed[]|null The supported values for this option.
     */
    protected ?array $supportedValues;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $name The option name.
     * @param mixed[]|null $supportedValues The supported values for this option, or null if any value is supported.
     */
    public function __construct(string $name, ?array $supportedValues = null)
    {
        $this->name = $name;
        $this->supportedValues = $supportedValues;
    }

    /**
     * Gets the option name.
     *
     * @since n.e.x.t
     *
     * @return string The option name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Checks if a value is supported for this option.
     *
     * @since n.e.x.t
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is supported, false otherwise.
     */
    public function isSupportedValue($value): bool
    {
        // If supportedValues is null, any value is supported
        if ($this->supportedValues === null) {
            return true;
        }

        return in_array($value, $this->supportedValues, true);
    }

    /**
     * Gets the supported values for this option.
     *
     * @since n.e.x.t
     *
     * @return mixed[]|null The supported values, or null if any value is supported.
     */
    public function getSupportedValues(): ?array
    {
        return $this->supportedValues;
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
                self::KEY_NAME => [
                    'type' => 'string',
                    'description' => 'The option name.',
                ],
                self::KEY_SUPPORTED_VALUES => [
                    'type' => 'array',
                    'items' => [
                        'oneOf' => [
                            ['type' => 'string'],
                            ['type' => 'number'],
                            ['type' => 'boolean'],
                            ['type' => 'null'],
                            ['type' => 'array'],
                            ['type' => 'object'],
                        ],
                    ],
                    'description' => 'The supported values for this option.',
                ],
            ],
            'required' => [self::KEY_NAME],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return SupportedOptionArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_NAME => $this->name,
        ];

        if ($this->supportedValues !== null) {
            $data[self::KEY_SUPPORTED_VALUES] = $this->supportedValues;
        }

        /** @phpstan-ignore-next-line return.type (array doesn't guarantee list, validation will be added later) */
        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_NAME]);

        return new self(
            $array[self::KEY_NAME],
            $array[self::KEY_SUPPORTED_VALUES] ?? null
        );
    }
}

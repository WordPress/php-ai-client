<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;

/**
 * Represents an option that the implementing code requires the model to support.
 *
 * This class defines an option that the model must support with a specific value
 * for it to be considered suitable for the implementing code's requirements.
 *
 * @since n.e.x.t
 *
 * @phpstan-type RequiredOptionArrayShape array{
 *     name: string,
 *     value: mixed
 * }
 *
 * @extends AbstractDataTransferObject<RequiredOptionArrayShape>
 */
class RequiredOption extends AbstractDataTransferObject
{
    public const KEY_NAME = 'name';
    public const KEY_VALUE = 'value';

    /**
     * @var string The option name.
     */
    protected string $name;

    /**
     * @var mixed The value that the model must support for this option.
     */
    protected $value;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $name The option name.
     * @param mixed $value The value that the model must support for this option.
     */
    public function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
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
     * Gets the value that the model must support for this option.
     *
     * @since n.e.x.t
     *
     * @return mixed The value that the model must support.
     */
    public function getValue()
    {
        return $this->value;
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
                self::KEY_VALUE => [
                    'oneOf' => [
                        ['type' => 'string'],
                        ['type' => 'number'],
                        ['type' => 'boolean'],
                        ['type' => 'null'],
                        ['type' => 'array'],
                        ['type' => 'object'],
                    ],
                    'description' => 'The value that the model must support for this option.',
                ],
            ],
            'required' => [self::KEY_NAME, self::KEY_VALUE],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return RequiredOptionArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_NAME => $this->name,
            self::KEY_VALUE => $this->value,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_NAME, self::KEY_VALUE]);

        return new self(
            $array[self::KEY_NAME],
            $array[self::KEY_VALUE]
        );
    }
}

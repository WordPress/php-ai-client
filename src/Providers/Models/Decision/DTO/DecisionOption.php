<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Decision\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Represents an option of a choice question.
 *
 * An option has a value, which a choice answer returns when the option is chosen, and optionally a
 * description of when it applies. An option without a description is interpreted by its value alone.
 *
 * @since n.e.x.t
 *
 * @phpstan-type DecisionOptionArrayShape array{
 *     value: string,
 *     description?: string
 * }
 *
 * @extends AbstractDataTransferObject<DecisionOptionArrayShape>
 */
class DecisionOption extends AbstractDataTransferObject
{
    public const KEY_VALUE = 'value';
    public const KEY_DESCRIPTION = 'description';

    /**
     * @var string The value identifying the option.
     */
    private string $value;

    /**
     * @var string|null The description of when the option applies, if any.
     */
    private ?string $description;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $value The value identifying the option.
     * @param string|null $description Optional description of when the option applies.
     *
     * @throws InvalidArgumentException If the value or the description is empty.
     */
    public function __construct(string $value, ?string $description = null)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Decision option values cannot be empty.');
        }

        if ($description !== null && trim($description) === '') {
            throw new InvalidArgumentException(
                'Decision option descriptions cannot be empty. Omit the description instead.'
            );
        }

        $this->value = $value;
        $this->description = $description;
    }

    /**
     * Gets the value identifying the option.
     *
     * @since n.e.x.t
     *
     * @return string The option value.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Gets the description of when the option applies.
     *
     * @since n.e.x.t
     *
     * @return string|null The option description, or null if the option has none.
     */
    public function getDescription(): ?string
    {
        return $this->description;
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
                self::KEY_VALUE => [
                    'type' => 'string',
                    'description' => 'The value identifying the option.',
                ],
                self::KEY_DESCRIPTION => [
                    'type' => 'string',
                    'description' => 'The description of when the option applies.',
                ],
            ],
            'required' => [self::KEY_VALUE],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return DecisionOptionArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_VALUE => $this->value,
        ];

        if ($this->description !== null) {
            $data[self::KEY_DESCRIPTION] = $this->description;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_VALUE]);

        return new self($array[self::KEY_VALUE], $array[self::KEY_DESCRIPTION] ?? null);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Decision\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\Models\Decision\Util\DescriptionUtil;

/**
 * Represents an option of a choice question.
 *
 * An option has a value, which a choice answer returns when the option is chosen, and optionally a
 * description of when it applies. An option without a description is interpreted by its value alone. A
 * description is a sentence, or an array for when structure makes it clearer, see {@see DescriptionUtil}.
 *
 * @since n.e.x.t
 *
 * @phpstan-type DecisionOptionArrayShape array{
 *     value: string,
 *     description?: string|array<mixed>
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
     * @var string|array<mixed>|null The description of when the option applies, if any.
     */
    private $description;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $value The value identifying the option.
     * @param string|array<mixed>|null $description Optional description of when the option applies: a sentence,
     *                                              or an array of JSON-compatible values.
     *
     * @throws InvalidArgumentException If the value is empty, or the description is neither null, a non-empty
     *                                  string, nor a non-empty array of JSON-compatible values.
     */
    public function __construct(string $value, $description = null)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Decision option values cannot be empty.');
        }

        if ($description !== null) {
            $description = DescriptionUtil::validate($description, 'A decision option description');
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
     * @return string|array<mixed>|null The option description, or null if the option has none.
     */
    public function getDescription()
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
                    'type' => ['string', 'object', 'array'],
                    'description' => 'The description of when the option applies: a sentence, or structured '
                        . 'JSON.',
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

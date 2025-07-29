<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\DTO;

use WordPress\AiClient\Common\AbstractDataValueObject;

/**
 * Represents a function call request from an AI model.
 *
 * This DTO encapsulates information about a function that the AI model
 * wants to invoke, including the function name and its arguments.
 *
 * @since n.e.x.t
 *
 * @phpstan-type FunctionCallArrayShape array{id?: string, name?: string, args?: array<string, mixed>}
 *
 * @extends AbstractDataValueObject<FunctionCallArrayShape>
 */
final class FunctionCall extends AbstractDataValueObject
{
    public const KEY_ID = 'id';
    public const KEY_NAME = 'name';
    public const KEY_ARGS = 'args';
    /**
     * @var string|null Unique identifier for this function call.
     */
    private ?string $id;

    /**
     * @var string|null The name of the function to call.
     */
    private ?string $name;

    /**
     * @var array<string, mixed> The arguments to pass to the function.
     */
    private array $args;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string|null $id Unique identifier for this function call.
     * @param string|null $name The name of the function to call.
     * @param array<string, mixed> $args The arguments to pass to the function.
     * @throws \InvalidArgumentException If neither id nor name is provided.
     */
    public function __construct(?string $id = null, ?string $name = null, array $args = [])
    {
        if ($id === null && $name === null) {
            throw new \InvalidArgumentException('At least one of id or name must be provided.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->args = $args;
    }

    /**
     * Gets the function call ID.
     *
     * @since n.e.x.t
     *
     * @return string|null The unique identifier.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Gets the function name.
     *
     * @since n.e.x.t
     *
     * @return string|null The function name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Gets the function arguments.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The function arguments.
     */
    public function getArgs(): array
    {
        return $this->args;
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
                    'description' => 'Unique identifier for this function call.',
                ],
                self::KEY_NAME => [
                    'type' => 'string',
                    'description' => 'The name of the function to call.',
                ],
                self::KEY_ARGS => [
                    'type' => 'object',
                    'description' => 'The arguments to pass to the function.',
                    'additionalProperties' => true,
                ],
            ],
            'oneOf' => [
                [
                    'required' => [self::KEY_ID],
                ],
                [
                    'required' => [self::KEY_NAME],
                ],
                [
                    'required' => [self::KEY_ID, self::KEY_NAME],
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return FunctionCallArrayShape
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->id !== null) {
            $data[self::KEY_ID] = $this->id;
        }

        if ($this->name !== null) {
            $data[self::KEY_NAME] = $this->name;
        }

        if (!empty($this->args)) {
            $data[self::KEY_ARGS] = $this->args;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): FunctionCall
    {
        return new self(
            $array[self::KEY_ID] ?? null,
            $array[self::KEY_NAME] ?? null,
            $array[self::KEY_ARGS] ?? []
        );
    }
}

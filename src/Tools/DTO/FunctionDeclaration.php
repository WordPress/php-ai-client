<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataValueObject;

/**
 * Represents a function declaration for AI models.
 *
 * This DTO describes a function that can be called by the AI model,
 * including its name, description, and parameter schema.
 *
 * @since n.e.x.t
 *
 * @phpstan-type FunctionDeclarationArrayShape array{name: string, description: string, parameters?: mixed}
 *
 * @extends AbstractDataValueObject<FunctionDeclarationArrayShape>
 */
final class FunctionDeclaration extends AbstractDataValueObject
{
    public const KEY_NAME = 'name';
    public const KEY_DESCRIPTION = 'description';
    public const KEY_PARAMETERS = 'parameters';
    /**
     * @var string The name of the function.
     */
    private string $name;

    /**
     * @var string A description of what the function does.
     */
    private string $description;

    /**
     * @var mixed|null The JSON schema for the function parameters.
     */
    private $parameters;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $name The name of the function.
     * @param string $description A description of what the function does.
     * @param mixed $parameters The JSON schema for the function parameters.
     */
    public function __construct(string $name, string $description, $parameters = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->parameters = $parameters;
    }

    /**
     * Gets the function name.
     *
     * @since n.e.x.t
     *
     * @return string The function name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the function description.
     *
     * @since n.e.x.t
     *
     * @return string The function description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Gets the function parameters schema.
     *
     * @since n.e.x.t
     *
     * @return mixed|null The parameters schema.
     */
    public function getParameters()
    {
        return $this->parameters;
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
                    'description' => 'The name of the function.',
                ],
                self::KEY_DESCRIPTION => [
                    'type' => 'string',
                    'description' => 'A description of what the function does.',
                ],
                self::KEY_PARAMETERS => [
                    'type' => ['string', 'number', 'boolean', 'object', 'array', 'null'],
                    'description' => 'The JSON schema for the function parameters.',
                ],
            ],
            'required' => [self::KEY_NAME, self::KEY_DESCRIPTION],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return FunctionDeclarationArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_NAME => $this->name,
            self::KEY_DESCRIPTION => $this->description,
        ];

        if ($this->parameters !== null) {
            $data[self::KEY_PARAMETERS] = $this->parameters;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): FunctionDeclaration
    {
        static::validateFromArrayData($array, [self::KEY_NAME, self::KEY_DESCRIPTION]);

        return new self(
            $array[self::KEY_NAME],
            $array[self::KEY_DESCRIPTION],
            $array[self::KEY_PARAMETERS] ?? null
        );
    }
}

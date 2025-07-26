<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;

/**
 * Represents a function declaration for AI models.
 *
 * This DTO describes a function that can be called by the AI model,
 * including its name, description, and parameter schema.
 *
 * @since n.e.x.t
 */
class FunctionDeclaration implements WithJsonSchemaInterface
{
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
                'name' => [
                    'type' => 'string',
                    'description' => 'The name of the function.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'A description of what the function does.',
                ],
                'parameters' => [
                    'type' => ['string', 'number', 'boolean', 'object', 'array', 'null'],
                    'description' => 'The JSON schema for the function parameters.',
                ],
            ],
            'required' => ['name', 'description'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchema;

/**
 * Represents a function call request from an AI model
 *
 * This DTO encapsulates information about a function that the AI model
 * wants to invoke, including the function name and its arguments.
 *
 * @since n.e.x.t
 */
class FunctionCall implements WithJsonSchema
{
    /**
     * @var string Unique identifier for this function call
     */
    private string $id;

    /**
     * @var string The name of the function to call
     */
    private string $name;

    /**
     * @var array<string, mixed> The arguments to pass to the function
     */
    private array $args;

    /**
     * Constructor
     *
     * @since n.e.x.t
     * @param string $id Unique identifier for this function call
     * @param string $name The name of the function to call
     * @param array<string, mixed> $args The arguments to pass to the function
     */
    public function __construct(string $id, string $name, array $args)
    {
        $this->id = $id;
        $this->name = $name;
        $this->args = $args;
    }

    /**
     * Get the function call ID
     *
     * @since n.e.x.t
     * @return string The unique identifier
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the function name
     *
     * @since n.e.x.t
     * @return string The function name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the function arguments
     *
     * @since n.e.x.t
     * @return array<string, mixed> The function arguments
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
                'id' => [
                    'type' => 'string',
                    'description' => 'Unique identifier for this function call',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'The name of the function to call',
                ],
                'args' => [
                    'type' => 'object',
                    'description' => 'The arguments to pass to the function',
                    'additionalProperties' => true,
                ],
            ],
            'required' => ['id', 'name', 'args'],
        ];
    }
}

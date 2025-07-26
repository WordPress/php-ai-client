<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;

/**
 * Represents a response to a function call.
 *
 * This DTO encapsulates the result of executing a function that was
 * requested by the AI model through a FunctionCall.
 *
 * @since n.e.x.t
 */
class FunctionResponse implements WithJsonSchemaInterface
{
    /**
     * @var string The ID of the function call this is responding to.
     */
    private string $id;

    /**
     * @var string The name of the function that was called.
     */
    private string $name;

    /**
     * @var mixed The response data from the function.
     */
    private $response;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $id The ID of the function call this is responding to.
     * @param string $name The name of the function that was called.
     * @param mixed $response The response data from the function.
     */
    public function __construct(string $id, string $name, $response)
    {
        $this->id = $id;
        $this->name = $name;
        $this->response = $response;
    }

    /**
     * Gets the function call ID.
     *
     * @since n.e.x.t
     *
     * @return string The function call ID.
     */
    public function getId(): string
    {
        return $this->id;
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
     * Gets the function response.
     *
     * @since n.e.x.t
     *
     * @return mixed The response data.
     */
    public function getResponse()
    {
        return $this->response;
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
                    'description' => 'The ID of the function call this is responding to.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'The name of the function that was called.',
                ],
                'response' => [
                    'type' => ['string', 'number', 'boolean', 'object', 'array', 'null'],
                    'description' => 'The response data from the function.',
                ],
            ],
            'required' => ['id', 'name', 'response'],
        ];
    }
}

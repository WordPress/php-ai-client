<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\DTO;

use WordPress\AiClient\Common\AbstractDataValueObject;

/**
 * Represents a response to a function call.
 *
 * This DTO encapsulates the result of executing a function that was
 * requested by the AI model through a FunctionCall.
 *
 * @since n.e.x.t
 *
 * @phpstan-type FunctionResponseArrayShape array{id: string, name: string, response: mixed}
 *
 * @extends AbstractDataValueObject<FunctionResponseArrayShape>
 */
final class FunctionResponse extends AbstractDataValueObject
{
    public const KEY_ID = 'id';
    public const KEY_NAME = 'name';
    public const KEY_RESPONSE = 'response';
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
                self::KEY_ID => [
                    'type' => 'string',
                    'description' => 'The ID of the function call this is responding to.',
                ],
                self::KEY_NAME => [
                    'type' => 'string',
                    'description' => 'The name of the function that was called.',
                ],
                self::KEY_RESPONSE => [
                    'type' => ['string', 'number', 'boolean', 'object', 'array', 'null'],
                    'description' => 'The response data from the function.',
                ],
            ],
            'required' => [self::KEY_ID, self::KEY_NAME, self::KEY_RESPONSE],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return FunctionResponseArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_ID => $this->id,
            self::KEY_NAME => $this->name,
            self::KEY_RESPONSE => $this->response,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): FunctionResponse
    {
        static::validateFromArrayData($array, [self::KEY_ID, self::KEY_NAME, self::KEY_RESPONSE]);

        return new self(
            $array[self::KEY_ID],
            $array[self::KEY_NAME],
            $array[self::KEY_RESPONSE]
        );
    }
}

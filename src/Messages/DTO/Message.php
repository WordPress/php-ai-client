<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;

/**
 * Represents a message in an AI conversation.
 *
 * Messages are the fundamental unit of communication with AI models,
 * containing a role and one or more parts with different content types.
 *
 * @since n.e.x.t
 */
class Message implements WithJsonSchemaInterface
{
    /**
     * @var MessageRoleEnum The role of the message sender.
     */
    protected MessageRoleEnum $role;

    /**
     * @var MessagePart[] The parts that make up this message.
     */
    protected array $parts;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param MessageRoleEnum $role The role of the message sender.
     * @param MessagePart[] $parts The parts that make up this message.
     */
    public function __construct(MessageRoleEnum $role, array $parts)
    {
        $this->role = $role;
        $this->parts = $parts;
    }

    /**
     * Creates a message from a simple text string.
     *
     * @since n.e.x.t
     *
     * @param MessageRoleEnum $role The role of the message sender.
     * @param string $text The text content.
     * @return self
     */
    public static function fromText(MessageRoleEnum $role, string $text): self
    {
        return new self($role, [MessagePart::text($text)]);
    }

    /**
     * Gets the role of the message sender.
     *
     * @since n.e.x.t
     *
     * @return MessageRoleEnum The role.
     */
    public function getRole(): MessageRoleEnum
    {
        return $this->role;
    }

    /**
     * Gets the message parts.
     *
     * @since n.e.x.t
     *
     * @return MessagePart[] The message parts.
     */
    public function getParts(): array
    {
        return $this->parts;
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
                'role' => [
                    'type' => 'string',
                    'enum' => ['user', 'model', 'system'],
                    'description' => 'The role of the message sender.',
                ],
                'parts' => [
                    'type' => 'array',
                    'items' => MessagePart::getJsonSchema(),
                    'minItems' => 1,
                    'description' => 'The parts that make up this message.',
                ],
            ],
            'required' => ['role', 'parts'],
        ];
    }
}

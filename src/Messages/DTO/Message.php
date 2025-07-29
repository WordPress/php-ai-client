<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataValueObject;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;

/**
 * Represents a message in an AI conversation.
 *
 * Messages are the fundamental unit of communication with AI models,
 * containing a role and one or more parts with different content types.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type MessagePartArrayShape from MessagePart
 *
 * @phpstan-type MessageArrayShape array{
 *     role: string,
 *     parts: array<MessagePartArrayShape>
 * }
 *
 * @extends AbstractDataValueObject<MessageArrayShape>
 */
class Message extends AbstractDataValueObject
{
    public const KEY_ROLE = 'role';
    public const KEY_PARTS = 'parts';
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
                self::KEY_ROLE => [
                    'type' => 'string',
                    'enum' => MessageRoleEnum::getValues(),
                    'description' => 'The role of the message sender.',
                ],
                self::KEY_PARTS => [
                    'type' => 'array',
                    'items' => MessagePart::getJsonSchema(),
                    'minItems' => 1,
                    'description' => 'The parts that make up this message.',
                ],
            ],
            'required' => [self::KEY_ROLE, self::KEY_PARTS],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return MessageArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_ROLE => $this->role->value,
            self::KEY_PARTS => array_map(function (MessagePart $part) {
                return $part->toArray();
            }, $this->parts),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return Message The specific message class based on the role.
     */
    final public static function fromArray(array $array): Message
    {
        static::validateFromArrayData($array, [self::KEY_ROLE, self::KEY_PARTS]);

        $role = MessageRoleEnum::from($array[self::KEY_ROLE]);
        $partsData = $array[self::KEY_PARTS];
        $parts = array_map(function (array $partData) {
            return MessagePart::fromArray($partData);
        }, $partsData);

        // Determine which concrete class to instantiate based on role
        if ($role->isUser()) {
            return new UserMessage($parts);
        } elseif ($role->isModel()) {
            return new ModelMessage($parts);
        } else {
            // System is the only remaining option
            return new SystemMessage($parts);
        }
    }
}

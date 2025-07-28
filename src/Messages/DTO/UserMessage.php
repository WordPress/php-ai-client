<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use WordPress\AiClient\Messages\Enums\MessageRoleEnum;

/**
 * Represents a message from a user.
 *
 * This is a convenience class that automatically sets the role to USER.
 *
 * @since n.e.x.t
 */
class UserMessage extends Message
{
    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param MessagePart[] $parts The parts that make up this message.
     */
    public function __construct(array $parts)
    {
        parent::__construct(MessageRoleEnum::user(), $parts);
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromJson(array $json): UserMessage
    {
        /** @var array<array{type: string, text?: string, file?: array<string, mixed>, functionCall?: array<string, mixed>, functionResponse?: array<string, mixed>}> $partsData */
        $partsData = $json['parts'];
        $parts = array_map(function (array $partData) {
            return MessagePart::fromJson($partData);
        }, $partsData);

        return new self($parts);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use WordPress\AiClient\Messages\Enums\MessageRoleEnum;

/**
 * Represents a message from the AI model.
 *
 * This is a convenience class that automatically sets the role to MODEL.
 * Model messages contain the AI's responses.
 *
 * @since n.e.x.t
 */
class ModelMessage extends Message
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
        parent::__construct(MessageRoleEnum::model(), $parts);
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromJson(array $json): ModelMessage
    {
        /** @var array<array{type: string, text?: string, file?: array<string, mixed>, functionCall?: array<string, mixed>, functionResponse?: array<string, mixed>}> $partsData */
        $partsData = $json['parts'];
        $parts = array_map(function (array $partData) {
            return MessagePart::fromJson($partData);
        }, $partsData);

        return new self($parts);
    }
}

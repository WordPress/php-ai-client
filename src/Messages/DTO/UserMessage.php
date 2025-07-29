<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Common\Traits\HasJsonSerialization;

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
}

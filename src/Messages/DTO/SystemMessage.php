<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use WordPress\AiClient\Messages\Enums\MessageRoleEnum;

/**
 * Represents a system instruction message.
 *
 * This is a convenience class that automatically sets the role to SYSTEM.
 * System messages are typically used to provide instructions or context to the AI model.
 *
 * @since n.e.x.t
 */
class SystemMessage extends Message
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
        parent::__construct(MessageRoleEnum::system(), $parts);
    }
}

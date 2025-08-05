<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\Contracts;

use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;

/**
 * Interface for message content types.
 *
 * This interface defines the contract that all message content value objects
 * must implement, ensuring consistent behavior across different content types.
 *
 * @since n.e.x.t
 */
interface MessageContentInterface
{
    /**
     * Gets the message part type for this content.
     *
     * @since n.e.x.t
     *
     * @return MessagePartTypeEnum The enum instance representing the content type.
     */
    public function getMessagePartType(): MessagePartTypeEnum;

    /**
     * Converts the content to array representation.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The array representation of the content.
     */
    public function toArray(): array;
}

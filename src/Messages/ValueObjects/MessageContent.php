<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\ValueObjects;

use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Abstract base class for message content types.
 *
 * This abstract class provides default implementations for all content getters,
 * allowing concrete content classes to only override the methods they need.
 *
 * @since n.e.x.t
 */
abstract class MessageContent
{
    /**
     * Gets the message part type for this content.
     *
     * @since n.e.x.t
     *
     * @return MessagePartTypeEnum The enum instance representing the content type.
     */
    abstract public function getMessagePartType(): MessagePartTypeEnum;

    /**
     * Converts the content to array representation.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The array representation of the content.
     */
    abstract public function toArray(): array;

    /**
     * Gets the text content.
     *
     * @since n.e.x.t
     *
     * @return string|null The text content or null if not a text part.
     */
    public function getText(): ?string
    {
        return null;
    }

    /**
     * Gets the file content.
     *
     * @since n.e.x.t
     *
     * @return File|null The file content or null if not a file part.
     */
    public function getFile(): ?File
    {
        return null;
    }

    /**
     * Gets the function call content.
     *
     * @since n.e.x.t
     *
     * @return FunctionCall|null The function call content or null if not a function call part.
     */
    public function getFunctionCall(): ?FunctionCall
    {
        return null;
    }

    /**
     * Gets the function response content.
     *
     * @since n.e.x.t
     *
     * @return FunctionResponse|null The function response content or null if not a function response part.
     */
    public function getFunctionResponse(): ?FunctionResponse
    {
        return null;
    }
}

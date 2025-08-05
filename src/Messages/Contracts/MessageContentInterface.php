<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\Contracts;

use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Interface for message content types.
 *
 * This interface defines the contract that all message content value objects
 * must implement, ensuring consistent behavior across different content types.
 *
 * @mixin ContentGettersTrait
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

    /**
     * Gets the text content.
     *
     * @since n.e.x.t
     *
     * @return string|null The text content or null if not a text part.
     */
    public function getText(): ?string;

    /**
     * Gets the file content.
     *
     * @since n.e.x.t
     *
     * @return File|null The file content or null if not a file part.
     */
    public function getFile(): ?File;

    /**
     * Gets the function call content.
     *
     * @since n.e.x.t
     *
     * @return FunctionCall|null The function call content or null if not a function call part.
     */
    public function getFunctionCall(): ?FunctionCall;

    /**
     * Gets the function response content.
     *
     * @since n.e.x.t
     *
     * @return FunctionResponse|null The function response content or null if not a function response part.
     */
    public function getFunctionResponse(): ?FunctionResponse;
}

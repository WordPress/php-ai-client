<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\ValueObjects;

use WordPress\AiClient\Messages\Contracts\MessageContentInterface;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\ValueObjects\ContentGettersTrait;

/**
 * Value object representing text content.
 *
 * This immutable value object encapsulates text content and provides
 * convenient methods for accessing and manipulating it.
 *
 * @since n.e.x.t
 */
final class TextContent implements MessageContentInterface
{
    use ContentGettersTrait;

    /**
     * The text content.
     *
     * @since n.e.x.t
     */
    private string $text;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $text The text content.
     */
    public function __construct(string $text)
    {
        $this->text = $text;
    }

    /**
     * Gets the type of the text content.
     *
     * @since n.e.x.t
     *
     * @return MessagePartTypeEnum Instance of the 'TEXT' type.
     */
    public function getMessagePartType(): MessagePartTypeEnum
    {
        return MessagePartTypeEnum::text();
    }

    /**
     * Gets the text content.
     *
     * @since n.e.x.t
     *
     * @return string The text content.
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Converts the text content to an array.
     *
     * @since n.e.x.t
     *
     * @return array<string, string> The text content as an array.
     */
    public function toArray(): array
    {
        return [MessagePart::KEY_TEXT => $this->text];
    }
}

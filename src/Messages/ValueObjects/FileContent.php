<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\ValueObjects;

use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\Contracts\MessageContentInterface;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\ValueObjects\ContentGettersTrait;

/**
 * Value object representing file content.
 *
 * This immutable value object encapsulates file content and provides
 * convenient methods for accessing and manipulating it.
 *
 * @since n.e.x.t
 */
final class FileContent implements MessageContentInterface
{
    /**
     * @use ContentGettersTrait
     */
    use ContentGettersTrait;

    /**
     * The file content.
     *
     * @since n.e.x.t
     */
    private File $file;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param File $file The file content.
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Gets the type of the file content.
     *
     * @since n.e.x.t
     *
     * @return MessagePartTypeEnum Instance of the 'FILE' type.
     */
    public function getMessagePartType(): MessagePartTypeEnum
    {
        return MessagePartTypeEnum::file();
    }

    /**
     * Gets the file content.
     *
     * @since n.e.x.t
     *
     * @return File The file content.
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * Converts the file content to an array.
     *
     * @since n.e.x.t
     *
     * @return array The file content as an array.
     */
    public function toArray(): array
    {
        return [MessagePart::KEY_FILE => $this->file->toArray()];
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Files\Traits;

/**
 * Provides MIME type functionality for file objects.
 *
 * This trait can be used by any class that needs to store and retrieve
 * a MIME type property.
 *
 * @since 1.0.0
 */
trait HasMimeType
{
    /**
     * The MIME type of the file.
     *
     * @var string
     */
    protected string $mimeType;

    /**
     * Gets the MIME type of the file.
     *
     * @return string The MIME type.
     *
     * @since 1.0.0
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}

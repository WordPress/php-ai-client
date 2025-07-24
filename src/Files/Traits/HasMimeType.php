<?php

declare(strict_types=1);

namespace WordPress\AiClient\Files\Traits;

use WordPress\AiClient\Files\ValueObjects\MimeType;

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
     * @var MimeType
     */
    protected MimeType $mimeType;

    /**
     * Gets the MIME type of the file.
     *
     * @return MimeType The MIME type.
     *
     * @since 1.0.0
     */
    public function getMimeType(): MimeType
    {
        return $this->mimeType;
    }

    /**
     * Gets the JSON schema for the MIME type property.
     *
     * @return array{type: string, description: string, pattern: string} The JSON schema for the mimeType property.
     *
     * @since n.e.x.t
     */
    protected static function getMimeTypePropertySchema(): array
    {
        return [
            'type' => 'string',
            'description' => 'The MIME type of the file.',
            'pattern' => '^[a-zA-Z0-9][a-zA-Z0-9!#$&\\-\\^_+.]*\\/[a-zA-Z0-9][a-zA-Z0-9!#$&\\-\\^_+.]*$',
        ];
    }
}

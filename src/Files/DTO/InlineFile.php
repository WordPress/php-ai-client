<?php

declare(strict_types=1);

namespace WordPress\AiClient\Files\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Files\Contracts\FileInterface;
use WordPress\AiClient\Files\Traits\HasMimeType;

/**
 * Represents a file with inline base64-encoded data.
 *
 * This DTO is used for files that are embedded directly in the request as base64 data,
 * commonly used for small files or when direct data transfer is preferred.
 *
 * @since n.e.x.t
 */
class InlineFile implements FileInterface, WithJsonSchemaInterface
{
    use HasMimeType;

    /**
     * @var string The base64-encoded file data.
     */
    private string $base64Data;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $base64Data The base64-encoded file data.
     * @param string|null $mimeType The MIME type of the file.
     */
    public function __construct(string $base64Data, string $mimeType = null)
    {
        // RFC 2397: dataurl := "data:" [ mediatype ] ";base64," data
        // mediatype is optional; if omitted, defaults to text/plain;charset=US-ASCII
        // We'll be more permissive and accept data URLs with or without MIME type
        $pattern = '/^data:(?:([a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_+.]*\/[a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_+.]*'
            . '(?:;[a-zA-Z0-9\-]+=[a-zA-Z0-9\-]+)*)?;)?base64,([A-Za-z0-9+\/]*={0,2})$/';

        if (!preg_match($pattern, $base64Data, $matches)) {
            throw new \InvalidArgumentException(
                'Invalid base64 data provided. Expected format: data:[mimeType];base64,[data]'
            );
        }

        $this->base64Data = $base64Data;

        if ($mimeType === null) {
            // Extract MIME type from data URL if present
            if (!empty($matches[1])) {
                // MIME type was provided in the data URL
                $this->mimeType = $matches[1];
            } else {
                // No MIME type provided; default to text/plain per RFC 2397
                $this->mimeType = 'text/plain';
            }
        } else {
            $this->mimeType = $mimeType;
        }
    }

    /**
     * Gets the base64-encoded data.
     *
     * @since n.e.x.t
     *
     * @return string The base64-encoded data.
     */
    public function getBase64Data(): string
    {
        return $this->base64Data;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'mimeType' => [
                    'type' => 'string',
                    'description' => 'The MIME type of the file.',
                ],
                'base64Data' => [
                    'type' => 'string',
                    'description' => 'The base64-encoded file data.',
                ],
            ],
            'required' => ['mimeType', 'base64Data'],
        ];
    }
}

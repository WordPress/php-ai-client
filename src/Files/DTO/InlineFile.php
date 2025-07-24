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
     * @param string $mimeType The MIME type of the file.
     * @param string $base64Data The base64-encoded file data.
     */
    public function __construct(string $mimeType, string $base64Data)
    {
        $this->mimeType = $mimeType;
        $this->base64Data = $base64Data;
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

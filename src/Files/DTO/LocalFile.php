<?php

declare(strict_types=1);

namespace WordPress\AiClient\Files\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Files\Contracts\FileInterface;
use WordPress\AiClient\Files\Traits\HasMimeType;
use WordPress\AiClient\Files\Utilities\MimeTypeUtil;

/**
 * Represents a file stored locally on the filesystem.
 *
 * This DTO is used for files that are referenced by their local path,
 * typically used when working with files already present on the server.
 *
 * @since n.e.x.t
 */
class LocalFile implements FileInterface, WithJsonSchemaInterface
{
    use HasMimeType;

    /**
     * @var string The local filesystem path to the file.
     */
    private string $path;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $path The local filesystem path to the file.
     * @param string|null $mimeType The MIME type of the file.
     */
    public function __construct(string $path, string $mimeType = null)
    {
        $this->path = $path;

        if ($mimeType !== null) {
            $this->mimeType = $mimeType;
        } else {
            // Extract extension from path
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            if (!empty($extension)) {
                $this->mimeType = MimeTypeUtil::getMimeTypeForExtension($extension);
            } else {
                // No extension found, default to text/plain
                $this->mimeType = 'text/plain';
            }
        }
    }

    /**
     * Gets the local filesystem path.
     *
     * @since n.e.x.t
     *
     * @return string The local path.
     */
    public function getPath(): string
    {
        return $this->path;
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
                'path' => [
                    'type' => 'string',
                    'description' => 'The local filesystem path to the file.',
                ],
            ],
            'required' => ['mimeType', 'path'],
        ];
    }
}

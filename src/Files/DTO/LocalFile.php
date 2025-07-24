<?php

declare(strict_types=1);

namespace WordPress\AiClient\Files\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Files\Contracts\FileInterface;
use WordPress\AiClient\Files\Traits\HasMimeType;
use WordPress\AiClient\Files\ValueObjects\MimeType;

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
     * @param MimeType|string|null $mimeType The MIME type of the file.
     */
    public function __construct(string $path, $mimeType = null)
    {
        $this->path = $path;

        if ($mimeType instanceof MimeType) {
            $this->mimeType = $mimeType;
        } elseif (is_string($mimeType)) {
            $this->mimeType = new MimeType($mimeType);
        } else {
            $this->mimeType = $this->getMimeTypeFromExtension($path);
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
     * Extracts MIME type from file extension.
     *
     * @since n.e.x.t
     *
     * @param string $path The file path.
     * @return MimeType The MIME type.
     */
    private function getMimeTypeFromExtension(string $path): MimeType
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if (!empty($extension)) {
            try {
                return MimeType::fromExtension($extension);
            } catch (\InvalidArgumentException $e) {
                // Unknown extension, default to text/plain
                return new MimeType('text/plain');
            }
        }

        // No extension found, default to text/plain
        return new MimeType('text/plain');
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
                    'pattern' => '^[a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_+.]*\/[a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_+.]*$',
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

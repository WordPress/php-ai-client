<?php

declare(strict_types=1);

namespace WordPress\AiClient\Files\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Files\Contracts\FileInterface;
use WordPress\AiClient\Files\Traits\HasMimeType;
use WordPress\AiClient\Files\ValueObjects\MimeType;

/**
 * Represents a file accessible via a remote URL.
 *
 * This DTO is used for files that are hosted remotely and accessed via HTTP/HTTPS,
 * commonly used for media files stored on CDNs or external services.
 *
 * @since n.e.x.t
 */
class RemoteFile implements FileInterface, WithJsonSchemaInterface
{
    use HasMimeType;

    /**
     * @var string The URL to the remote file.
     */
    private string $url;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $url The URL to the remote file.
     * @param MimeType|string|null $mimeType The MIME type of the file.
     */
    public function __construct(string $url, $mimeType = null)
    {
        $this->url = $url;

        if ($mimeType instanceof MimeType) {
            $this->mimeType = $mimeType;
        } elseif (is_string($mimeType)) {
            $this->mimeType = new MimeType($mimeType);
        } else {
            $this->mimeType = $this->getMimeTypeFromExtension($url);
        }
    }

    /**
     * Gets the URL to the remote file.
     *
     * @since n.e.x.t
     *
     * @return string The URL.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Extracts MIME type from URL extension.
     *
     * @since n.e.x.t
     *
     * @param string $url The file URL.
     * @return MimeType The MIME type.
     */
    private function getMimeTypeFromExtension(string $url): MimeType
    {
        // Parse URL to extract filename and extension
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        // Remove query string and fragment if present in the path
        $cleanPath = strtok($path, '?#');

        if ($cleanPath === false) {
            $cleanPath = $path;
        }

        // Extract extension from the path
        $extension = pathinfo($cleanPath, PATHINFO_EXTENSION);

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
                'url' => [
                    'type' => 'string',
                    'format' => 'uri',
                    'description' => 'The URL to the remote file.',
                ],
            ],
            'required' => ['mimeType', 'url'],
        ];
    }
}

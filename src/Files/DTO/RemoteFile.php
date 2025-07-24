<?php

declare(strict_types=1);

namespace WordPress\AiClient\Files\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Files\Contracts\FileInterface;
use WordPress\AiClient\Files\Traits\HasMimeType;

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
     * @param string $mimeType The MIME type of the file.
     * @param string $url The URL to the remote file.
     */
    public function __construct(string $mimeType, string $url)
    {
        $this->mimeType = $mimeType;
        $this->url = $url;
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

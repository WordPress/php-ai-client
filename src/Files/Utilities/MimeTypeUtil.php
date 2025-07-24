<?php

declare(strict_types=1);

namespace WordPress\AiClient\Files\Utilities;

/**
 * Utility class for MIME type operations.
 *
 * Provides static methods for working with MIME types, including
 * determining MIME types from file extensions.
 *
 * @since n.e.x.t
 */
class MimeTypeUtil
{
    /**
     * Common MIME type mappings for file extensions.
     *
     * @var array<string, string>
     */
    private static array $mimeTypes = [
        // Text
        'txt' => 'text/plain',
        'html' => 'text/html',
        'htm' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'csv' => 'text/csv',
        'md' => 'text/markdown',

        // Images
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',

        // Documents
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',

        // Archives
        'zip' => 'application/zip',
        'tar' => 'application/x-tar',
        'gz' => 'application/gzip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',

        // Audio
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'flac' => 'audio/flac',
        'm4a' => 'audio/m4a',

        // Video
        'mp4' => 'video/mp4',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'wmv' => 'video/x-ms-wmv',
        'flv' => 'video/x-flv',
        'webm' => 'video/webm',
        'mkv' => 'video/x-matroska',

        // Fonts
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',

        // Other
        'php' => 'application/x-httpd-php',
        'sh' => 'application/x-sh',
        'exe' => 'application/x-msdownload',
    ];

    /**
     * Gets the MIME type for a given file extension.
     *
     * @since n.e.x.t
     *
     * @param string $extension The file extension (without the dot).
     * @return string The MIME type, or 'text/plain' if unknown.
     */
    public static function getMimeTypeForExtension(string $extension): string
    {
        $extension = strtolower($extension);

        return self::$mimeTypes[$extension] ?? 'text/plain';
    }
}

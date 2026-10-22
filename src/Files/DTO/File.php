<?php

declare(strict_types=1);

namespace WordPress\AiClient\Files\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Files\ValueObjects\MimeType;

/**
 * Represents a file in the AI client.
 *
 * A file is either remote (an http(s) URL that the provider fetches) or inline (base64-encoded data).
 *
 * The constructor accepts an http(s) URL, a data URI, or plain base64 data with a MIME type. It never
 * reads from the filesystem. Local files must be loaded explicitly via {@see File::fromLocalPath()},
 * which is the only method of this class that accesses the filesystem. The named constructors
 * {@see File::fromUrl()}, {@see File::fromDataUri()} and {@see File::fromBase64()} should be preferred
 * whenever the kind of input is known, especially for data that comes from an untrusted source such as
 * an API response or user input.
 *
 * @since 0.1.0
 * @since n.e.x.t The constructor no longer detects local file paths; use File::fromLocalPath() instead.
 *
 * @phpstan-type FileArrayShape array{
 *     fileType: string,
 *     url?: string,
 *     mimeType: string,
 *     base64Data?: string
 * }
 *
 * @extends AbstractDataTransferObject<FileArrayShape>
 */
class File extends AbstractDataTransferObject
{
    public const KEY_FILE_TYPE = 'fileType';
    public const KEY_MIME_TYPE = 'mimeType';
    public const KEY_URL = 'url';
    public const KEY_BASE64_DATA = 'base64Data';

    /**
     * Pattern matching a data URI with base64 payload.
     *
     * Group 1 is the optional MIME type (with parameters), group 2 is the base64 payload.
     *
     * @since n.e.x.t
     */
    private const DATA_URI_PATTERN = '/^data:(?:([a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_+.]*\/[a-zA-Z0-9][a-zA-Z0-9!#$&\-\^_+.]*'
        . '(?:;[a-zA-Z0-9\-]+=[a-zA-Z0-9\-]+)*)?;)?base64,([A-Za-z0-9+\/]*={0,2})\z/';

    /**
     * Pattern matching plain (non-empty) base64 data.
     *
     * @since n.e.x.t
     */
    private const BASE64_PATTERN = '/^[A-Za-z0-9+\/]+={0,2}\z/';

    /**
     * @var MimeType The MIME type of the file.
     */
    private MimeType $mimeType;

    /**
     * @var FileTypeEnum The type of file storage.
     */
    private FileTypeEnum $fileType;

    /**
     * @var string|null The URL for remote files.
     */
    private ?string $url = null;

    /**
     * @var string|null The base64 data for inline files.
     */
    private ?string $base64Data = null;

    /**
     * Constructor.
     *
     * This constructor does not access the filesystem. To load a local file, use {@see File::fromLocalPath()}.
     *
     * @since 0.1.0
     * @since n.e.x.t Local file paths are no longer detected. Empty strings and non-http(s) URL schemes are rejected.
     *
     * @param string $file The file string (http(s) URL, data URI, or plain base64 data).
     * @param string|null $mimeType The MIME type of the file (optional, required for plain base64 data).
     * @throws InvalidArgumentException If the file format is invalid or MIME type cannot be determined.
     */
    public function __construct(string $file, ?string $mimeType = null)
    {
        $this->detectAndProcessFile($file, $mimeType);
    }

    /**
     * Creates a remote file from an http(s) URL.
     *
     * The URL is not fetched by the client; it is passed on to the provider.
     *
     * @since n.e.x.t
     *
     * @param string $url The http(s) URL.
     * @param string|null $mimeType The MIME type (optional, otherwise inferred from the URL path extension).
     * @return self The remote file.
     * @throws InvalidArgumentException If the URL is not an http(s) URL or the MIME type cannot be determined.
     */
    public static function fromUrl(string $url, ?string $mimeType = null): self
    {
        if (!self::isHttpUrl($url)) {
            throw new InvalidArgumentException(
                'Invalid URL. Only http:// and https:// URLs are supported.'
            );
        }

        return new self($url, $mimeType);
    }

    /**
     * Creates an inline file from a data URI (data:[mime/type];base64,[data]).
     *
     * @since n.e.x.t
     *
     * @param string $dataUri The data URI.
     * @param string|null $mimeType The MIME type (optional, overrides the MIME type embedded in the data URI).
     * @return self The inline file.
     * @throws InvalidArgumentException If the data URI is invalid or the MIME type cannot be determined.
     */
    public static function fromDataUri(string $dataUri, ?string $mimeType = null): self
    {
        if (!preg_match(self::DATA_URI_PATTERN, $dataUri)) {
            throw new InvalidArgumentException(
                'Invalid data URI. Expected the format data:[mime/type];base64,[data].'
            );
        }

        return new self($dataUri, $mimeType);
    }

    /**
     * Creates an inline file from plain base64-encoded data.
     *
     * @since n.e.x.t
     *
     * @param string $base64 The base64-encoded data (without data URI prefix).
     * @param string $mimeType The MIME type of the data.
     * @return self The inline file.
     * @throws InvalidArgumentException If the data is not valid base64 or the MIME type is invalid.
     */
    public static function fromBase64(string $base64, string $mimeType): self
    {
        if (str_starts_with($base64, 'data:')) {
            throw new InvalidArgumentException(
                'Expected raw base64 data; use File::fromDataUri() for data URIs.'
            );
        }

        if (!self::isBase64($base64)) {
            throw new InvalidArgumentException(
                'Invalid base64 data provided.'
            );
        }

        if (!MimeType::isValid($mimeType)) {
            throw new InvalidArgumentException(
                sprintf('Invalid MIME type: %s', $mimeType)
            );
        }

        // Routing through the data URI form makes the constructor take a deterministic, filesystem-free path.
        return new self(sprintf('data:%s;base64,%s', $mimeType, $base64));
    }

    /**
     * Creates an inline file by reading a file from the local filesystem.
     *
     * This is the only method of this class that accesses the filesystem. Never pass untrusted input
     * (such as user input or API response data) to this method.
     *
     * @since n.e.x.t
     *
     * @param string $path The path to the local file. Stream wrappers (e.g. ftp://, phar://) are not supported.
     * @param string|null $mimeType The MIME type (optional, otherwise inferred from the file extension).
     * @return self The inline file.
     * @throws InvalidArgumentException If the path is empty, uses a stream wrapper, is not a regular file,
     *                                  or the MIME type cannot be determined.
     * @throws RuntimeException If the file cannot be read.
     */
    public static function fromLocalPath(string $path, ?string $mimeType = null): self
    {
        if ($path === '') {
            throw new InvalidArgumentException('Local file path must not be empty.');
        }

        // Reject stream wrappers before touching the filesystem, so no wrapper can open a connection.
        if (str_contains($path, '://')) {
            throw new InvalidArgumentException(
                'Stream wrappers are not supported in local file paths.'
            );
        }

        if (!is_file($path)) {
            throw new InvalidArgumentException(
                sprintf('Local file not found or not a regular file: %s', $path)
            );
        }

        $resolvedMimeType = $mimeType !== null
            ? new MimeType($mimeType)
            : self::determineMimeType(null, null, $path);

        $fileContent = @file_get_contents($path);
        if ($fileContent === false) {
            throw new RuntimeException(
                sprintf('Unable to read file: %s', $path)
            );
        }

        return self::fromBase64(base64_encode($fileContent), (string) $resolvedMimeType);
    }

    /**
     * Detects the file type and processes it accordingly.
     *
     * @since 0.1.0
     * @since n.e.x.t No longer detects local file paths.
     *
     * @param string $file The file string to process.
     * @param string|null $providedMimeType The explicitly provided MIME type.
     * @throws InvalidArgumentException If the file format is invalid or MIME type cannot be determined.
     */
    private function detectAndProcessFile(string $file, ?string $providedMimeType): void
    {
        if ($file === '') {
            throw new InvalidArgumentException('File string must not be empty.');
        }

        // Check if it's an http(s) URL.
        if (self::isHttpUrl($file)) {
            $this->fileType = FileTypeEnum::remote();
            $this->url = $file;
            $this->mimeType = self::determineMimeType($providedMimeType, null, $file);
            return;
        }

        // Any other URL scheme (file://, ftp://, phar://, ...) is rejected before anything else runs.
        if (str_contains($file, '://')) {
            throw new InvalidArgumentException(
                'Unsupported URL scheme. Only http:// and https:// URLs are supported.'
            );
        }

        // Check if it's a data URI.
        if (preg_match(self::DATA_URI_PATTERN, $file, $matches)) {
            $this->fileType = FileTypeEnum::inline();
            $this->base64Data = $matches[2]; // Extract just the base64 data
            $extractedMimeType = empty($matches[1]) ? null : $matches[1];
            $this->mimeType = self::determineMimeType($providedMimeType, $extractedMimeType, null);
            return;
        }

        // Check if it's plain base64.
        if (self::isBase64($file)) {
            if ($providedMimeType === null) {
                throw new InvalidArgumentException(
                    'MIME type is required when providing plain base64 data without data URI format. '
                    . 'If you intended to pass a local file path, use File::fromLocalPath() instead.'
                );
            }
            $this->fileType = FileTypeEnum::inline();
            $this->base64Data = $file;
            $this->mimeType = new MimeType($providedMimeType);
            return;
        }

        throw new InvalidArgumentException(
            'Invalid file provided. Expected an http(s) URL, a data URI, or base64 data with a MIME type. '
            . 'To load a local file, use File::fromLocalPath().'
        );
    }

    /**
     * Checks if a string is an http(s) URL.
     *
     * @since 0.1.0
     * @since n.e.x.t Made static.
     *
     * @param string $string The string to check.
     * @return bool True if the string is an http(s) URL.
     */
    private static function isHttpUrl(string $string): bool
    {
        return filter_var($string, FILTER_VALIDATE_URL) !== false
            && preg_match('/^https?:\/\//i', $string) === 1;
    }

    /**
     * Checks if a string is non-empty plain base64 data.
     *
     * @since n.e.x.t
     *
     * @param string $string The string to check.
     * @return bool True if the string is plain base64 data.
     */
    private static function isBase64(string $string): bool
    {
        return preg_match(self::BASE64_PATTERN, $string) === 1;
    }

    /**
     * Gets the file type.
     *
     * @since 0.1.0
     *
     * @return FileTypeEnum The file type.
     */
    public function getFileType(): FileTypeEnum
    {
        return $this->fileType;
    }

    /**
     * Checks if the file is an inline file.
     *
     * @since 0.1.0
     *
     * @return bool True if the file is inline (base64/data URI).
     */
    public function isInline(): bool
    {
        return $this->fileType->isInline();
    }

    /**
     * Checks if the file is a remote file.
     *
     * @since 0.1.0
     *
     * @return bool True if the file is remote (URL).
     */
    public function isRemote(): bool
    {
        return $this->fileType->isRemote();
    }

    /**
     * Gets the URL for remote files.
     *
     * @since 0.1.0
     *
     * @return string|null The URL, or null if not a remote file.
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Gets the base64-encoded data for inline files.
     *
     * @since 0.1.0
     *
     * @return string|null The plain base64-encoded data (without data URI prefix), or null if not an inline file.
     */
    public function getBase64Data(): ?string
    {
        return $this->base64Data;
    }

    /**
     * Gets the data as a data URI for inline files.
     *
     * @since 0.1.0
     *
     * @return string|null The data URI in format: data:[mimeType];base64,[data], or null if not an inline file.
     */
    public function getDataUri(): ?string
    {
        if ($this->base64Data === null) {
            return null;
        }

        return sprintf('data:%s;base64,%s', $this->getMimeType(), $this->base64Data);
    }

    /**
     * Gets the MIME type of the file as a string.
     *
     * @since 0.1.0
     *
     * @return string The MIME type string value.
     */
    public function getMimeType(): string
    {
        return (string) $this->mimeType;
    }

    /**
     * Gets the MIME type object.
     *
     * @since 0.1.0
     *
     * @return MimeType The MIME type object.
     */
    public function getMimeTypeObject(): MimeType
    {
        return $this->mimeType;
    }

    /**
     * Checks if the file is a video.
     *
     * @since 0.1.0
     *
     * @return bool True if the file is a video.
     */
    public function isVideo(): bool
    {
        return $this->mimeType->isVideo();
    }

    /**
     * Checks if the file is an image.
     *
     * @since 0.1.0
     *
     * @return bool True if the file is an image.
     */
    public function isImage(): bool
    {
        return $this->mimeType->isImage();
    }

    /**
     * Checks if the file is audio.
     *
     * @since 0.1.0
     *
     * @return bool True if the file is audio.
     */
    public function isAudio(): bool
    {
        return $this->mimeType->isAudio();
    }

    /**
     * Checks if the file is text.
     *
     * @since 0.1.0
     *
     * @return bool True if the file is text.
     */
    public function isText(): bool
    {
        return $this->mimeType->isText();
    }

    /**
     * Checks if the file is a document.
     *
     * @since 0.1.0
     *
     * @return bool True if the file is a document.
     */
    public function isDocument(): bool
    {
        return $this->mimeType->isDocument();
    }

    /**
     * Checks if the file is a specific MIME type.
     *
     * @since 0.1.0
     *
     * @param string $type The mime type to check (e.g. 'image', 'text', 'video', 'audio').
     *
     * @return bool True if the file is of the specified type.
     */
    public function isMimeType(string $type): bool
    {
        return $this->mimeType->isType($type);
    }

    /**
     * Determines the MIME type from various sources.
     *
     * @since 0.1.0
     * @since n.e.x.t Made static.
     *
     * @param string|null $providedMimeType The explicitly provided MIME type.
     * @param string|null $extractedMimeType The MIME type extracted from data URI.
     * @param string|null $pathOrUrl The file path or URL to extract extension from.
     * @return MimeType The determined MIME type.
     * @throws InvalidArgumentException If MIME type cannot be determined.
     */
    private static function determineMimeType(
        ?string $providedMimeType,
        ?string $extractedMimeType,
        ?string $pathOrUrl
    ): MimeType {
        // Prefer explicitly provided MIME type
        if ($providedMimeType !== null) {
            return new MimeType($providedMimeType);
        }

        // Use extracted MIME type from data URI
        if ($extractedMimeType !== null) {
            return new MimeType($extractedMimeType);
        }

        // Try to determine from file extension
        if ($pathOrUrl !== null) {
            $parsedUrl = parse_url($pathOrUrl);
            $path = $parsedUrl['path'] ?? $pathOrUrl;

            // Remove query string and fragment if present
            $cleanPath = strtok($path, '?#');
            if ($cleanPath === false) {
                $cleanPath = $path;
            }

            $extension = pathinfo($cleanPath, PATHINFO_EXTENSION);
            if (!empty($extension)) {
                try {
                    return MimeType::fromExtension($extension);
                } catch (InvalidArgumentException $e) {
                    // Extension not recognized, continue to error
                    unset($e);
                }
            }
        }

        throw new InvalidArgumentException(
            'Unable to determine MIME type. Please provide it explicitly.'
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'oneOf' => [
                [
                    'properties' => [
                        self::KEY_FILE_TYPE => [
                            'type' => 'string',
                            'const' => FileTypeEnum::REMOTE,
                            'description' => 'The file type.',
                        ],
                        self::KEY_MIME_TYPE => [
                            'type' => 'string',
                            'description' => 'The MIME type of the file.',
                            'pattern' => '^[a-zA-Z0-9][a-zA-Z0-9!#$&\\-\\^_+.]*\\/[a-zA-Z0-9]'
                                . '[a-zA-Z0-9!#$&\\-\\^_+.]*$',
                        ],
                        self::KEY_URL => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'The URL to the remote file.',
                        ],
                    ],
                    'required' => [self::KEY_FILE_TYPE, self::KEY_MIME_TYPE, self::KEY_URL],
                ],
                [
                    'properties' => [
                        self::KEY_FILE_TYPE => [
                            'type' => 'string',
                            'const' => FileTypeEnum::INLINE,
                            'description' => 'The file type.',
                        ],
                        self::KEY_MIME_TYPE => [
                            'type' => 'string',
                            'description' => 'The MIME type of the file.',
                            'pattern' => '^[a-zA-Z0-9][a-zA-Z0-9!#$&\\-\\^_+.]*\\/[a-zA-Z0-9]'
                                . '[a-zA-Z0-9!#$&\\-\\^_+.]*$',
                        ],
                        self::KEY_BASE64_DATA => [
                            'type' => 'string',
                            'description' => 'The base64-encoded file data.',
                        ],
                    ],
                    'required' => [self::KEY_FILE_TYPE, self::KEY_MIME_TYPE, self::KEY_BASE64_DATA],
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     *
     * @return FileArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_FILE_TYPE => $this->fileType->value,
            self::KEY_MIME_TYPE => $this->getMimeType(),
        ];

        if ($this->url !== null) {
            $data[self::KEY_URL] = $this->url;
        } elseif (!$this->fileType->isRemote() && $this->base64Data !== null) {
            $data[self::KEY_BASE64_DATA] = $this->base64Data;
        } else {
            throw new RuntimeException(
                'File requires either url or base64Data. This should not be a possible condition.'
            );
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * This check is purely structural and never accesses the filesystem.
     *
     * @since n.e.x.t
     */
    public static function isArrayShape(array $array): bool
    {
        try {
            self::assertArrayShape($array);
            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     * @since n.e.x.t The fileType key is authoritative: remote files require an http(s) url, inline files
     *                require base64Data and mimeType. Local file paths are rejected.
     */
    public static function fromArray(array $array): self
    {
        self::assertArrayShape($array);

        $mimeType = $array[self::KEY_MIME_TYPE] ?? null;

        if ($array[self::KEY_FILE_TYPE] === FileTypeEnum::REMOTE) {
            return self::fromUrl($array[self::KEY_URL] ?? '', $mimeType);
        }

        return self::fromBase64($array[self::KEY_BASE64_DATA] ?? '', $mimeType ?? '');
    }

    /**
     * Asserts that an array has a valid file array shape, without constructing the file.
     *
     * @since n.e.x.t
     *
     * @param array<mixed> $array The array to check.
     * @throws InvalidArgumentException If the array is not a valid file array shape.
     */
    private static function assertArrayShape(array $array): void
    {
        static::validateFromArrayData($array, [self::KEY_FILE_TYPE]);

        $fileType = $array[self::KEY_FILE_TYPE];
        if ($fileType !== FileTypeEnum::REMOTE && $fileType !== FileTypeEnum::INLINE) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid file type. Expected "%s" or "%s".',
                    FileTypeEnum::REMOTE,
                    FileTypeEnum::INLINE
                )
            );
        }

        if (array_key_exists(self::KEY_MIME_TYPE, $array)) {
            $mimeType = $array[self::KEY_MIME_TYPE];
            if (!is_string($mimeType) || !MimeType::isValid($mimeType)) {
                throw new InvalidArgumentException('Invalid MIME type provided in file array.');
            }
        }

        if ($fileType === FileTypeEnum::REMOTE) {
            if (array_key_exists(self::KEY_BASE64_DATA, $array)) {
                throw new InvalidArgumentException('Remote files must not contain base64Data.');
            }
            $url = $array[self::KEY_URL] ?? null;
            if (!is_string($url) || !self::isHttpUrl($url)) {
                throw new InvalidArgumentException(
                    'Remote files require a url with an http:// or https:// scheme.'
                );
            }
            return;
        }

        if (array_key_exists(self::KEY_URL, $array)) {
            throw new InvalidArgumentException('Inline files must not contain url.');
        }
        $base64Data = $array[self::KEY_BASE64_DATA] ?? null;
        if (!is_string($base64Data)) {
            throw new InvalidArgumentException('Inline files require base64Data.');
        }
        if (str_starts_with($base64Data, 'data:')) {
            throw new InvalidArgumentException(
                'Inline files require raw base64 data in base64Data, not a data URI.'
            );
        }
        if (!self::isBase64($base64Data)) {
            throw new InvalidArgumentException(
                'Inline files require valid base64 data in base64Data. Local file paths are not supported.'
            );
        }
        if (!array_key_exists(self::KEY_MIME_TYPE, $array)) {
            throw new InvalidArgumentException('Inline files require a mimeType.');
        }
    }

    /**
     * Performs a deep clone of the file.
     *
     * This method ensures that the MimeType value object is cloned to prevent
     * any shared references between the original and cloned file.
     *
     * @since 0.4.2
     */
    public function __clone()
    {
        $this->mimeType = clone $this->mimeType;
    }
}

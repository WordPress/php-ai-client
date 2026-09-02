<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Files\DTO\File;

/**
 * Represents an image embedded in an extracted document page.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type FileArrayShape from File
 * @phpstan-import-type BoundingBoxArrayShape from BoundingBox
 *
 * @phpstan-type ExtractedImageArrayShape array{
 *     id: string,
 *     file?: FileArrayShape,
 *     boundingBox?: BoundingBoxArrayShape
 * }
 *
 * @extends AbstractDataTransferObject<ExtractedImageArrayShape>
 */
class ExtractedImage extends AbstractDataTransferObject
{
    public const KEY_ID = 'id';
    public const KEY_FILE = 'file';
    public const KEY_BOUNDING_BOX = 'boundingBox';

    /**
     * @var string Identifier of the image within the document (e.g. a filename referenced by the page markdown).
     */
    private string $id;

    /**
     * @var File|null The image data, when the provider returned it.
     */
    private ?File $file;

    /**
     * @var BoundingBox|null The image location on the page, when the provider reported it.
     */
    private ?BoundingBox $boundingBox;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $id Identifier of the image within the document.
     * @param File|null $file The image data, when returned by the provider.
     * @param BoundingBox|null $boundingBox The image location on the page, when reported.
     */
    public function __construct(string $id, ?File $file = null, ?BoundingBox $boundingBox = null)
    {
        $this->id = $id;
        $this->file = $file;
        $this->boundingBox = $boundingBox;
    }

    /**
     * Gets the image identifier.
     *
     * @since n.e.x.t
     *
     * @return string The identifier.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the image data, when the provider returned it.
     *
     * @since n.e.x.t
     *
     * @return File|null The image file, or null if image data was not requested/returned.
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * Gets the image location on the page, when the provider reported it.
     *
     * @since n.e.x.t
     *
     * @return BoundingBox|null The bounding box, or null if not reported.
     */
    public function getBoundingBox(): ?BoundingBox
    {
        return $this->boundingBox;
    }

    /**
     * Gets the JSON schema for an extracted image.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The JSON schema.
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                self::KEY_ID => [
                    'type' => 'string',
                    'description' => 'Identifier of the image within the document.',
                ],
                self::KEY_FILE => File::getJsonSchema(),
                self::KEY_BOUNDING_BOX => BoundingBox::getJsonSchema(),
            ],
            'required' => [
                self::KEY_ID,
            ],
        ];
    }

    /**
     * Converts the extracted image to an array.
     *
     * @since n.e.x.t
     *
     * @return ExtractedImageArrayShape The extracted image array.
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_ID => $this->id,
        ];

        if ($this->file !== null) {
            $data[self::KEY_FILE] = $this->file->toArray();
        }

        if ($this->boundingBox !== null) {
            $data[self::KEY_BOUNDING_BOX] = $this->boundingBox->toArray();
        }

        return $data;
    }

    /**
     * Creates an extracted image from an array.
     *
     * @since n.e.x.t
     *
     * @param ExtractedImageArrayShape $array The extracted image array.
     * @return self The extracted image instance.
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [
            self::KEY_ID,
        ]);

        return new self(
            $array[self::KEY_ID],
            isset($array[self::KEY_FILE]) ? File::fromArray($array[self::KEY_FILE]) : null,
            isset($array[self::KEY_BOUNDING_BOX]) ? BoundingBox::fromArray($array[self::KEY_BOUNDING_BOX]) : null
        );
    }

    /**
     * Creates a deep clone of this extracted image.
     *
     * @since n.e.x.t
     */
    public function __clone()
    {
        if ($this->file !== null) {
            $this->file = clone $this->file;
        }
        if ($this->boundingBox !== null) {
            $this->boundingBox = clone $this->boundingBox;
        }
    }
}

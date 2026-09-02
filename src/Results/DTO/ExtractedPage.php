<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Represents a single page of a text extraction result.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type ExtractedImageArrayShape from ExtractedImage
 * @phpstan-import-type PageDimensionsArrayShape from PageDimensions
 *
 * @phpstan-type ExtractedPageArrayShape array{
 *     pageNumber: int,
 *     markdown: string,
 *     images?: list<ExtractedImageArrayShape>,
 *     dimensions?: PageDimensionsArrayShape
 * }
 *
 * @extends AbstractDataTransferObject<ExtractedPageArrayShape>
 */
class ExtractedPage extends AbstractDataTransferObject
{
    public const KEY_PAGE_NUMBER = 'pageNumber';
    public const KEY_MARKDOWN = 'markdown';
    public const KEY_IMAGES = 'images';
    public const KEY_DIMENSIONS = 'dimensions';

    /**
     * @var int The 1-based page number.
     */
    private int $pageNumber;

    /**
     * @var string The extracted page content as markdown.
     */
    private string $markdown;

    /**
     * @var list<ExtractedImage> Images embedded in the page.
     */
    private array $images;

    /**
     * @var PageDimensions|null The page dimensions, when reported by the provider.
     */
    private ?PageDimensions $dimensions;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param int $pageNumber The 1-based page number.
     * @param string $markdown The extracted page content as markdown.
     * @param list<ExtractedImage> $images Images embedded in the page.
     * @param PageDimensions|null $dimensions The page dimensions, when reported.
     */
    public function __construct(
        int $pageNumber,
        string $markdown,
        array $images = [],
        ?PageDimensions $dimensions = null
    ) {
        if ($pageNumber < 1) {
            throw new InvalidArgumentException('Page number must be 1 or greater.');
        }

        foreach ($images as $image) {
            if (!$image instanceof ExtractedImage) {
                throw new InvalidArgumentException('All images must be ExtractedImage instances.');
            }
        }

        $this->pageNumber = $pageNumber;
        $this->markdown = $markdown;
        $this->images = $images;
        $this->dimensions = $dimensions;
    }

    /**
     * Gets the 1-based page number.
     *
     * @since n.e.x.t
     *
     * @return int The page number.
     */
    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    /**
     * Gets the extracted page content as markdown.
     *
     * Providers that only produce plain text return it unchanged (plain text is valid markdown).
     *
     * @since n.e.x.t
     *
     * @return string The page content.
     */
    public function getMarkdown(): string
    {
        return $this->markdown;
    }

    /**
     * Gets the images embedded in the page.
     *
     * @since n.e.x.t
     *
     * @return list<ExtractedImage> The images; empty when not requested or not supported.
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Gets the page dimensions, when reported by the provider.
     *
     * @since n.e.x.t
     *
     * @return PageDimensions|null The dimensions, or null if not reported.
     */
    public function getDimensions(): ?PageDimensions
    {
        return $this->dimensions;
    }

    /**
     * Gets the JSON schema for an extracted page.
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
                self::KEY_PAGE_NUMBER => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'The 1-based page number.',
                ],
                self::KEY_MARKDOWN => [
                    'type' => 'string',
                    'description' => 'The extracted page content as markdown.',
                ],
                self::KEY_IMAGES => [
                    'type' => 'array',
                    'items' => ExtractedImage::getJsonSchema(),
                    'description' => 'Images embedded in the page.',
                ],
                self::KEY_DIMENSIONS => PageDimensions::getJsonSchema(),
            ],
            'required' => [
                self::KEY_PAGE_NUMBER,
                self::KEY_MARKDOWN,
            ],
        ];
    }

    /**
     * Converts the extracted page to an array.
     *
     * @since n.e.x.t
     *
     * @return ExtractedPageArrayShape The extracted page array.
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_PAGE_NUMBER => $this->pageNumber,
            self::KEY_MARKDOWN => $this->markdown,
        ];

        if (!empty($this->images)) {
            $data[self::KEY_IMAGES] = array_map(
                static fn (ExtractedImage $image): array => $image->toArray(),
                $this->images
            );
        }

        if ($this->dimensions !== null) {
            $data[self::KEY_DIMENSIONS] = $this->dimensions->toArray();
        }

        return $data;
    }

    /**
     * Creates an extracted page from an array.
     *
     * @since n.e.x.t
     *
     * @param ExtractedPageArrayShape $array The extracted page array.
     * @return self The extracted page instance.
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [
            self::KEY_PAGE_NUMBER,
            self::KEY_MARKDOWN,
        ]);

        $images = [];
        if (isset($array[self::KEY_IMAGES])) {
            foreach ($array[self::KEY_IMAGES] as $imageData) {
                $images[] = ExtractedImage::fromArray($imageData);
            }
        }

        return new self(
            (int) $array[self::KEY_PAGE_NUMBER],
            $array[self::KEY_MARKDOWN],
            $images,
            isset($array[self::KEY_DIMENSIONS]) ? PageDimensions::fromArray($array[self::KEY_DIMENSIONS]) : null
        );
    }

    /**
     * Creates a deep clone of this extracted page.
     *
     * @since n.e.x.t
     */
    public function __clone()
    {
        $clonedImages = [];
        foreach ($this->images as $image) {
            $clonedImages[] = clone $image;
        }
        $this->images = $clonedImages;

        if ($this->dimensions !== null) {
            $this->dimensions = clone $this->dimensions;
        }
    }
}

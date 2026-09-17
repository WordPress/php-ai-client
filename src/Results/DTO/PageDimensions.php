<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Represents the pixel dimensions of an extracted document page.
 *
 * @since n.e.x.t
 *
 * @phpstan-type PageDimensionsArrayShape array{
 *     width: int,
 *     height: int,
 *     dpi?: int
 * }
 *
 * @extends AbstractDataTransferObject<PageDimensionsArrayShape>
 */
class PageDimensions extends AbstractDataTransferObject
{
    public const KEY_WIDTH = 'width';
    public const KEY_HEIGHT = 'height';
    public const KEY_DPI = 'dpi';

    /**
     * @var int The page width in pixels.
     */
    private int $width;

    /**
     * @var int The page height in pixels.
     */
    private int $height;

    /**
     * @var int|null The resolution in dots per inch, if reported.
     */
    private ?int $dpi;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param int $width The page width in pixels.
     * @param int $height The page height in pixels.
     * @param int|null $dpi The resolution in dots per inch, if reported.
     */
    public function __construct(int $width, int $height, ?int $dpi = null)
    {
        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Page dimensions must be positive integers.');
        }

        $this->width = $width;
        $this->height = $height;
        $this->dpi = $dpi;
    }

    /**
     * Gets the page width in pixels.
     *
     * @since n.e.x.t
     *
     * @return int The width.
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Gets the page height in pixels.
     *
     * @since n.e.x.t
     *
     * @return int The height.
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Gets the resolution in dots per inch, if reported by the provider.
     *
     * @since n.e.x.t
     *
     * @return int|null The DPI, or null if not reported.
     */
    public function getDpi(): ?int
    {
        return $this->dpi;
    }

    /**
     * Gets the JSON schema for page dimensions.
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
                self::KEY_WIDTH => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'Page width in pixels.',
                ],
                self::KEY_HEIGHT => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'Page height in pixels.',
                ],
                self::KEY_DPI => [
                    'type' => 'integer',
                    'description' => 'Resolution in dots per inch.',
                ],
            ],
            'required' => [
                self::KEY_WIDTH,
                self::KEY_HEIGHT,
            ],
        ];
    }

    /**
     * Converts the page dimensions to an array.
     *
     * @since n.e.x.t
     *
     * @return PageDimensionsArrayShape The page dimensions array.
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_WIDTH => $this->width,
            self::KEY_HEIGHT => $this->height,
        ];

        if ($this->dpi !== null) {
            $data[self::KEY_DPI] = $this->dpi;
        }

        return $data;
    }

    /**
     * Creates page dimensions from an array.
     *
     * @since n.e.x.t
     *
     * @param PageDimensionsArrayShape $array The page dimensions array.
     * @return self The page dimensions instance.
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [
            self::KEY_WIDTH,
            self::KEY_HEIGHT,
        ]);

        return new self(
            (int) $array[self::KEY_WIDTH],
            (int) $array[self::KEY_HEIGHT],
            isset($array[self::KEY_DPI]) ? (int) $array[self::KEY_DPI] : null
        );
    }
}

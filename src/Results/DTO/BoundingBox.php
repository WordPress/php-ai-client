<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Represents a bounding box within a page, using normalized coordinates.
 *
 * Coordinates are expressed in the 0–1 range relative to the page size, with the
 * origin at the top-left corner. Pixel values can be recovered by multiplying with
 * the page dimensions ({@see PageDimensions}) when the provider reports them.
 *
 * @since n.e.x.t
 *
 * @phpstan-type BoundingBoxArrayShape array{
 *     left: float,
 *     top: float,
 *     width: float,
 *     height: float
 * }
 *
 * @extends AbstractDataTransferObject<BoundingBoxArrayShape>
 */
class BoundingBox extends AbstractDataTransferObject
{
    public const KEY_LEFT = 'left';
    public const KEY_TOP = 'top';
    public const KEY_WIDTH = 'width';
    public const KEY_HEIGHT = 'height';

    /**
     * @var float Normalized left offset (0–1).
     */
    private float $left;

    /**
     * @var float Normalized top offset (0–1).
     */
    private float $top;

    /**
     * @var float Normalized width (0–1).
     */
    private float $width;

    /**
     * @var float Normalized height (0–1).
     */
    private float $height;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param float $left Normalized left offset (0–1).
     * @param float $top Normalized top offset (0–1).
     * @param float $width Normalized width (0–1).
     * @param float $height Normalized height (0–1).
     */
    public function __construct(float $left, float $top, float $width, float $height)
    {
        foreach (['left' => $left, 'top' => $top, 'width' => $width, 'height' => $height] as $name => $value) {
            if ($value < 0.0 || $value > 1.0) {
                throw new InvalidArgumentException(
                    sprintf('Bounding box %s must be a normalized value between 0 and 1.', $name)
                );
            }
        }

        $this->left = $left;
        $this->top = $top;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Gets the normalized left offset.
     *
     * @since n.e.x.t
     *
     * @return float The left offset (0–1).
     */
    public function getLeft(): float
    {
        return $this->left;
    }

    /**
     * Gets the normalized top offset.
     *
     * @since n.e.x.t
     *
     * @return float The top offset (0–1).
     */
    public function getTop(): float
    {
        return $this->top;
    }

    /**
     * Gets the normalized width.
     *
     * @since n.e.x.t
     *
     * @return float The width (0–1).
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * Gets the normalized height.
     *
     * @since n.e.x.t
     *
     * @return float The height (0–1).
     */
    public function getHeight(): float
    {
        return $this->height;
    }

    /**
     * Gets the JSON schema for a bounding box.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The JSON schema.
     */
    public static function getJsonSchema(): array
    {
        $coordinate = [
            'type' => 'number',
            'minimum' => 0,
            'maximum' => 1,
        ];

        return [
            'type' => 'object',
            'properties' => [
                self::KEY_LEFT => array_merge($coordinate, [
                    'description' => 'Normalized left offset relative to the page width.',
                ]),
                self::KEY_TOP => array_merge($coordinate, [
                    'description' => 'Normalized top offset relative to the page height.',
                ]),
                self::KEY_WIDTH => array_merge($coordinate, [
                    'description' => 'Normalized width relative to the page width.',
                ]),
                self::KEY_HEIGHT => array_merge($coordinate, [
                    'description' => 'Normalized height relative to the page height.',
                ]),
            ],
            'required' => [
                self::KEY_LEFT,
                self::KEY_TOP,
                self::KEY_WIDTH,
                self::KEY_HEIGHT,
            ],
        ];
    }

    /**
     * Converts the bounding box to an array.
     *
     * @since n.e.x.t
     *
     * @return BoundingBoxArrayShape The bounding box array.
     */
    public function toArray(): array
    {
        return [
            self::KEY_LEFT => $this->left,
            self::KEY_TOP => $this->top,
            self::KEY_WIDTH => $this->width,
            self::KEY_HEIGHT => $this->height,
        ];
    }

    /**
     * Creates a bounding box from an array.
     *
     * @since n.e.x.t
     *
     * @param BoundingBoxArrayShape $array The bounding box array.
     * @return self The bounding box instance.
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [
            self::KEY_LEFT,
            self::KEY_TOP,
            self::KEY_WIDTH,
            self::KEY_HEIGHT,
        ]);

        return new self(
            (float) $array[self::KEY_LEFT],
            (float) $array[self::KEY_TOP],
            (float) $array[self::KEY_WIDTH],
            (float) $array[self::KEY_HEIGHT]
        );
    }
}

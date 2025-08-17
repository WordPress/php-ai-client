<?php

declare(strict_types=1);

namespace WordPress\AiClient\Embeddings\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;

/**
 * Represents a single embedding vector with its metadata.
 *
 * An embedding is a high-dimensional vector representation of input data,
 * typically used for semantic similarity comparisons, search, and classification.
 *
 * @since n.e.x.t
 *
 * @phpstan-type EmbeddingArrayShape array{
 *     vector: float[],
 *     dimension: int
 * }
 *
 * @extends AbstractDataTransferObject<EmbeddingArrayShape>
 */
class Embedding extends AbstractDataTransferObject
{
    public const KEY_VECTOR = 'vector';
    public const KEY_DIMENSION = 'dimension';

    /**
     * @var float[] The embedding vector values.
     */
    private array $vector;

    /**
     * @var int The dimension (length) of the embedding vector.
     */
    private int $dimension;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param float[] $vector The embedding vector values.
     */
    public function __construct(array $vector)
    {
        $this->vector = $vector;
        $this->dimension = count($vector);
    }

    /**
     * Gets the embedding vector values.
     *
     * @since n.e.x.t
     *
     * @return float[] The vector values.
     */
    public function getVector(): array
    {
        return $this->vector;
    }

    /**
     * Gets the dimension (length) of the embedding vector.
     *
     * @since n.e.x.t
     *
     * @return int The vector dimension.
     */
    public function getDimension(): int
    {
        return $this->dimension;
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
                self::KEY_VECTOR => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'number',
                    ],
                    'description' => 'The embedding vector values.',
                ],
                self::KEY_DIMENSION => [
                    'type' => 'integer',
                    'description' => 'The dimension (length) of the embedding vector.',
                ],
            ],
            'required' => [self::KEY_VECTOR, self::KEY_DIMENSION],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return EmbeddingArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_VECTOR => $this->vector,
            self::KEY_DIMENSION => $this->dimension,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [
            self::KEY_VECTOR,
        ]);

        return new self($array[self::KEY_VECTOR]);
    }
}

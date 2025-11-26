<?php

declare(strict_types=1);

namespace WordPress\AiClient\Embeddings\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Represents a single embedding vector returned by a provider.
 *
 * @since 0.2.0
 *
 * @phpstan-type EmbeddingArrayShape array{
 *     vector: list<float>,
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
     * @var list<float> The embedding vector values.
     */
    private array $vector;

    /**
     * @var int The dimensionality of the embedding vector.
     */
    private int $dimension;

    /**
     * Constructor.
     *
     * @since 0.2.0
     *
     * @param list<float|int> $vector The embedding vector values.
     * @param int $dimension The dimensionality of the vector.
     *
     * @throws InvalidArgumentException If vector validation fails.
     */
    public function __construct(array $vector, int $dimension)
    {
        if (!array_is_list($vector)) {
            throw new InvalidArgumentException('Embedding vector must be a list array.');
        }

        $normalizedVector = [];
        foreach ($vector as $value) {
            if (!is_float($value) && !is_int($value)) {
                throw new InvalidArgumentException('Embedding vector values must be numeric.');
            }
            $normalizedVector[] = (float) $value;
        }

        if ($dimension <= 0) {
            throw new InvalidArgumentException('Embedding dimension must be greater than zero.');
        }

        if (count($normalizedVector) !== $dimension) {
            throw new InvalidArgumentException(
                sprintf(
                    'Embedding dimension mismatch: expected %d values, got %d.',
                    $dimension,
                    count($normalizedVector)
                )
            );
        }

        $this->vector = $normalizedVector;
        $this->dimension = $dimension;
    }

    /**
     * Gets the embedding vector values.
     *
     * @since 0.2.0
     *
     * @return list<float> The embedding vector.
     */
    public function getVector(): array
    {
        return $this->vector;
    }

    /**
     * Gets the dimensionality of the embedding vector.
     *
     * @since 0.2.0
     *
     * @return int The embedding dimension.
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
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
                    'minimum' => 1,
                    'description' => 'The dimensionality of the embedding vector.',
                ],
            ],
            'required' => [self::KEY_VECTOR, self::KEY_DIMENSION],
            'additionalProperties' => false,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
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
     * @since 0.2.0
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_VECTOR, self::KEY_DIMENSION]);

        return new self(
            $array[self::KEY_VECTOR],
            $array[self::KEY_DIMENSION]
        );
    }
}

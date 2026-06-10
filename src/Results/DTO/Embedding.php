<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Represents a single generated embedding vector.
 *
 * @since n.e.x.t
 *
 * @implements IteratorAggregate<int, float|int>
 */
final class Embedding implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var list<float|int>
     */
    private array $values;

    private int $dimensions;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param list<mixed> $values The embedding vector values.
     * @param int $dimensions The vector dimension count.
     */
    public function __construct(array $values, int $dimensions)
    {
        if ($dimensions < 1) {
            throw new InvalidArgumentException('Embedding dimensions must be greater than zero');
        }

        if (!array_is_list($values)) {
            throw new InvalidArgumentException('Embedding values must be a list array.');
        }

        if (count($values) !== $dimensions) {
            throw new InvalidArgumentException('Embedding vector length must match dimensions.');
        }

        $normalizedValues = [];
        foreach ($values as $value) {
            if (!is_int($value) && !is_float($value)) {
                throw new InvalidArgumentException('Embedding values must be integers or floats.');
            }

            $normalizedValues[] = $value;
        }

        $this->values = $normalizedValues;
        $this->dimensions = $dimensions;
    }

    /**
     * Gets the vector values.
     *
     * @since n.e.x.t
     *
     * @return list<float|int> The embedding vector values.
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Gets the vector dimension count.
     *
     * @since n.e.x.t
     *
     * @return int The vector dimension count.
     */
    public function getDimensions(): int
    {
        return $this->dimensions;
    }

    /**
     * Gets the number of vector values.
     *
     * @since n.e.x.t
     *
     * @return int The number of vector values.
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * Gets an iterator for the vector values.
     *
     * @since n.e.x.t
     *
     * @return Traversable<int, float|int> The vector value iterator.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }

    /**
     * Gets the JSON schema for embedding vectors.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The JSON schema.
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => 'number',
            ],
            'description' => 'Generated embedding vector values.',
        ];
    }

    /**
     * Converts the embedding to an array.
     *
     * @since n.e.x.t
     *
     * @return list<float|int> The embedding vector values.
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * Creates an embedding from an array.
     *
     * @since n.e.x.t
     *
     * @param list<mixed> $array The embedding vector values.
     * @param int $dimensions The vector dimension count.
     * @return self The embedding instance.
     */
    public static function fromArray(array $array, int $dimensions): self
    {
        return new self($array, $dimensions);
    }

    /**
     * Converts the embedding to a JSON-serializable value.
     *
     * @since n.e.x.t
     *
     * @return list<float|int> The embedding vector values.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

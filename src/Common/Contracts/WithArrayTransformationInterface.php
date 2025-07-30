<?php

declare(strict_types=1);

namespace WordPress\AiClient\Common\Contracts;

/**
 * Interface for objects that support array transformation.
 *
 * @since n.e.x.t
 *
 * @template TArrayShape of array<string, mixed>
 */
interface WithArrayTransformationInterface
{
    /**
     * Converts the object to an array representation.
     *
     * @since n.e.x.t
     *
     * @return TArrayShape The array representation.
     */
    public function toArray(): array;

    /**
     * Creates an instance from array data.
     *
     * @since n.e.x.t
     *
     * @param TArrayShape $array The array data.
     * @return self<TArrayShape> The created instance.
     */
    public static function fromArray(array $array): self;
}

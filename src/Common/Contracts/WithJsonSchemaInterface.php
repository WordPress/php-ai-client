<?php

declare(strict_types=1);

namespace WordPress\AiClient\Common\Contracts;

/**
 * Interface for objects that can provide their JSON schema representation.
 *
 * This interface is implemented by DTOs to provide a consistent way to retrieve
 * their JSON schema for validation and serialization purposes.
 *
 * @since n.e.x.t
 */
interface WithJsonSchemaInterface
{
    /**
     * Gets the JSON schema representation of the object.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The JSON schema as an associative array.
     */
    public static function getJsonSchema(): array;
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Common\Contracts;

/**
 * Interface for objects that cache data.
 *
 * @since n.e.x.t
 */
interface CachesDataInterface
{
    /**
     * Invalidates all caches managed by this object.
     *
     * @since n.e.x.t
     *
     * @return void
     */
    public function invalidateCaches(): void;
}

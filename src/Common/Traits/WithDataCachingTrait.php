<?php

declare(strict_types=1);

namespace WordPress\AiClient\Common\Traits;

use WordPress\AiClient\AiClient;

/**
 * Trait for objects that cache data using PSR-16 cache.
 *
 * @since n.e.x.t
 */
trait WithDataCachingTrait
{
    /**
     * Gets the base cache key for this object.
     *
     * The base cache key is used as a prefix for all cache keys managed by this object.
     * It should be unique to the implementing class to avoid cache key collisions.
     *
     * @since n.e.x.t
     *
     * @return string The base cache key.
     */
    abstract protected function getBaseCacheKey(): string;

    /**
     * Gets a value from the cache.
     *
     * @since n.e.x.t
     *
     * @param string $key     The cache key suffix (will be appended to the base key).
     * @param mixed  $default The default value to return if the key does not exist.
     * @return mixed The cached value or the default value if not found.
     */
    protected function getCache(string $key, $default = null)
    {
        $cache = AiClient::getCache();
        if ($cache === null) {
            return $default;
        }

        return $cache->get($this->buildCacheKey($key), $default);
    }

    /**
     * Sets a value in the cache.
     *
     * @since n.e.x.t
     *
     * @param string                $key   The cache key suffix (will be appended to the base key).
     * @param mixed                 $value The value to cache.
     * @param int|\DateInterval|null $ttl   The TTL for the cache entry, or null for default.
     * @return bool True on success, false on failure or if no cache is configured.
     */
    protected function setCache(string $key, $value, $ttl = null): bool
    {
        $cache = AiClient::getCache();
        if ($cache === null) {
            return false;
        }

        return $cache->set($this->buildCacheKey($key), $value, $ttl);
    }

    /**
     * Clears a value from the cache.
     *
     * @since n.e.x.t
     *
     * @param string $key The cache key suffix (will be appended to the base key).
     * @return bool True on success, false on failure or if no cache is configured.
     */
    protected function clearCache(string $key): bool
    {
        $cache = AiClient::getCache();
        if ($cache === null) {
            return false;
        }

        return $cache->delete($this->buildCacheKey($key));
    }

    /**
     * Builds the full cache key by combining the base key with the suffix.
     *
     * @since n.e.x.t
     *
     * @param string $key The cache key suffix.
     * @return string The full cache key.
     */
    private function buildCacheKey(string $key): string
    {
        return $this->getBaseCacheKey() . '_' . $key;
    }
}

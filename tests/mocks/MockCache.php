<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

/**
 * Mock cache for testing.
 *
 * This simple implementation stores values in memory and tracks
 * cache operations for testing purposes.
 */
class MockCache implements CacheInterface
{
    /**
     * @var array<string, mixed> The cached values.
     */
    private array $cache = [];

    /**
     * @var list<array{operation: string, key: string, value?: mixed}> The recorded operations.
     */
    private array $operations = [];

    /**
     * {@inheritDoc}
     *
     * @param string $key The cache key.
     * @param mixed $default Default value to return if key doesn't exist.
     * @return mixed The cached value or default.
     */
    public function get($key, $default = null)
    {
        $this->operations[] = ['operation' => 'get', 'key' => $key];
        return $this->cache[$key] ?? $default;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $key The cache key.
     * @param mixed $value The value to cache.
     * @param int|DateInterval|null $ttl The TTL (ignored in mock).
     * @return bool True on success.
     */
    public function set($key, $value, $ttl = null): bool
    {
        $this->operations[] = ['operation' => 'set', 'key' => $key, 'value' => $value];
        $this->cache[$key] = $value;
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $key The cache key.
     * @return bool True on success.
     */
    public function delete($key): bool
    {
        $this->operations[] = ['operation' => 'delete', 'key' => $key];
        unset($this->cache[$key]);
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool True on success.
     */
    public function clear(): bool
    {
        $this->operations[] = ['operation' => 'clear', 'key' => ''];
        $this->cache = [];
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @param iterable<string> $keys The cache keys.
     * @param mixed $default Default value for missing keys.
     * @return iterable<string, mixed> The cached values.
     */
    public function getMultiple($keys, $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     *
     * @param iterable<string, mixed> $values The values to cache.
     * @param int|DateInterval|null $ttl The TTL (ignored in mock).
     * @return bool True on success.
     */
    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @param iterable<string> $keys The cache keys.
     * @return bool True on success.
     */
    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $key The cache key.
     * @return bool True if key exists.
     */
    public function has($key): bool
    {
        $this->operations[] = ['operation' => 'has', 'key' => $key];
        return array_key_exists($key, $this->cache);
    }

    /**
     * Gets all recorded operations.
     *
     * @return list<array{operation: string, key: string, value?: mixed}> The recorded operations.
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * Gets operations filtered by type.
     *
     * @param string $operation The operation type (get, set, delete, clear, has).
     * @return list<array{operation: string, key: string, value?: mixed}> The filtered operations.
     */
    public function getOperationsOfType(string $operation): array
    {
        return array_values(array_filter(
            $this->operations,
            static function (array $op) use ($operation): bool {
                return $op['operation'] === $operation;
            }
        ));
    }

    /**
     * Clears all recorded operations.
     *
     * @return void
     */
    public function clearOperations(): void
    {
        $this->operations = [];
    }

    /**
     * Gets a value directly from the cache without recording an operation.
     *
     * @param string $key The cache key.
     * @return mixed The cached value or null.
     */
    public function peek(string $key)
    {
        return $this->cache[$key] ?? null;
    }

    /**
     * Sets a value directly in the cache without recording an operation.
     *
     * @param string $key The cache key.
     * @param mixed $value The value to cache.
     * @return void
     */
    public function seed(string $key, $value): void
    {
        $this->cache[$key] = $value;
    }
}

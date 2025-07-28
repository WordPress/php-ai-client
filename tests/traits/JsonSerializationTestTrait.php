<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\traits;

/**
 * Trait for testing JSON serialization functionality.
 *
 * Provides common assertions for DTOs that implement WithJsonSerialization.
 *
 * @since 1.0.0
 */
trait JsonSerializationTestTrait
{
    /**
     * Asserts that an object implements WithJsonSerialization interface.
     *
     * @param object $object The object to test.
     * @return void
     */
    protected function assertImplementsJsonSerialization($object): void
    {
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithJsonSerialization::class,
            $object,
            'Object should implement WithJsonSerialization interface'
        );
        $this->assertInstanceOf(
            \JsonSerializable::class,
            $object,
            'Object should implement JsonSerializable interface'
        );
    }

    /**
     * Asserts that jsonSerialize returns a valid array.
     *
     * @param object $object The object to test.
     * @return array The serialized data.
     */
    protected function assertJsonSerializeReturnsArray($object): array
    {
        $json = $object->jsonSerialize();
        $this->assertIsArray($json, 'jsonSerialize() should return an array');
        return $json;
    }

    /**
     * Asserts round-trip JSON serialization works correctly.
     *
     * @param object $original The original object.
     * @param callable $assertCallback Callback to assert equality between original and restored.
     * @return void
     */
    protected function assertJsonRoundTrip($original, callable $assertCallback): void
    {
        $json = $original->jsonSerialize();
        $className = get_class($original);
        $restored = $className::fromJson($json);
        
        $this->assertInstanceOf($className, $restored, 'fromJson() should return instance of ' . $className);
        $assertCallback($original, $restored);
    }

    /**
     * Asserts that specific keys exist in serialized JSON.
     *
     * @param array $json The serialized JSON array.
     * @param array $expectedKeys The keys that should exist.
     * @return void
     */
    protected function assertJsonHasKeys(array $json, array $expectedKeys): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $json, "JSON should contain key: {$key}");
        }
    }

    /**
     * Asserts that specific keys do not exist in serialized JSON.
     *
     * @param array $json The serialized JSON array.
     * @param array $unexpectedKeys The keys that should not exist.
     * @return void
     */
    protected function assertJsonNotHasKeys(array $json, array $unexpectedKeys): void
    {
        foreach ($unexpectedKeys as $key) {
            $this->assertArrayNotHasKey($key, $json, "JSON should not contain key: {$key}");
        }
    }
}
<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\traits;

/**
 * Trait for testing array transformation functionality.
 *
 * Provides common assertions for DTOs that implement WithArrayTransformationInterface.
 */
trait ArrayTransformationTestTrait
{
    /**
     * Asserts that an object implements WithArrayTransformationInterface interface.
     *
     * @param object $object The object to test.
     * @return void
     */
    protected function assertImplementsArrayTransformation($object): void
    {
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface::class,
            $object,
            'Object should implement WithArrayTransformationInterface interface'
        );
    }

    /**
     * Asserts that toArray returns a valid array.
     *
     * @param object $object The object to test.
     * @return array The serialized data.
     */
    protected function assertToArrayReturnsArray($object): array
    {
        $array = $object->toArray();
        $this->assertIsArray($array, 'toArray() should return an array');
        return $array;
    }

    /**
     * Asserts round-trip array transformation works correctly.
     *
     * @param object $original The original object.
     * @param callable $assertCallback Callback to assert equality between original and restored.
     * @return void
     */
    protected function assertArrayRoundTrip($original, callable $assertCallback): void
    {
        $array = $original->toArray();
        $className = get_class($original);
        $restored = $className::fromArray($array);

        $this->assertInstanceOf($className, $restored, 'fromArray() should return instance of ' . $className);
        $assertCallback($original, $restored);
    }

    /**
     * Asserts that specific keys exist in transformed array.
     *
     * @param array $array The transformed array.
     * @param array $expectedKeys The keys that should exist.
     * @return void
     */
    protected function assertArrayHasKeys(array $array, array $expectedKeys): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, "Array should contain key: {$key}");
        }
    }

    /**
     * Asserts that specific keys do not exist in transformed array.
     *
     * @param array $array The transformed array.
     * @param array $unexpectedKeys The keys that should not exist.
     * @return void
     */
    protected function assertArrayNotHasKeys(array $array, array $unexpectedKeys): void
    {
        foreach ($unexpectedKeys as $key) {
            $this->assertArrayNotHasKey($key, $array, "Array should not contain key: {$key}");
        }
    }
}

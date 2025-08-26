<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\traits;

use WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface;

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
            WithArrayTransformationInterface::class,
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

    /**
     * Tests isArrayShape with valid and invalid arrays.
     *
     * @param string $className The class name to test.
     * @param array $validArray A valid array that should pass isArrayShape.
     * @param array[] $invalidArrays Arrays that should fail isArrayShape.
     * @return void
     */
    protected function assertIsArrayShapeValidation(string $className, array $validArray, array $invalidArrays): void
    {
        // Test valid array
        $this->assertTrue(
            $className::isArrayShape($validArray),
            'isArrayShape() should return true for valid array structure'
        );

        // Test that fromArray works with the valid array (ensures consistency)
        $instance = $className::fromArray($validArray);
        $this->assertInstanceOf($className, $instance);

        // Test invalid arrays
        foreach ($invalidArrays as $description => $invalidArray) {
            $this->assertFalse(
                $className::isArrayShape($invalidArray),
                "isArrayShape() should return false for: {$description}"
            );
        }
    }
}

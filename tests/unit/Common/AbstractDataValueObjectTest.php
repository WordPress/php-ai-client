<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Common;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use stdClass;
use WordPress\AiClient\Common\AbstractDataValueObject;

/**
 * Tests for the AbstractDataValueObject class.
 *
 * This test class verifies that the AbstractDataValueObject correctly
 * implements JSON serialization with proper empty array to object conversion
 * based on JSON schema definitions. It combines the functionality previously
 * tested in HasJsonSerializationTest.
 *
 * @covers \WordPress\AiClient\Common\AbstractDataValueObject
 */
class AbstractDataValueObjectTest extends TestCase
{
    /**
     * Tests that empty arrays are converted to objects when schema expects object type.
     *
     * @return void
     */
    public function testEmptyArraysConvertedToObjects(): void
    {
        $testObject = new class extends AbstractDataValueObject {
            public function toArray(): array
            {
                return [
                    'emptyObject' => [],
                    'nonEmptyObject' => ['key' => 'value'],
                    'emptyArray' => [],
                    'nonEmptyArray' => [1, 2, 3],
                ];
            }

            public static function fromArray(array $array)
            {
                return new static();
            }

            public static function getJsonSchema(): array
            {
                return [
                    'type' => 'object',
                    'properties' => [
                        'emptyObject' => [
                            'type' => 'object',
                            'properties' => []
                        ],
                        'nonEmptyObject' => [
                            'type' => 'object',
                            'properties' => [
                                'key' => ['type' => 'string']
                            ]
                        ],
                        'emptyArray' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer']
                        ],
                        'nonEmptyArray' => [
                            'type' => 'array',
                            'items' => ['type' => 'integer']
                        ],
                    ],
                ];
            }
        };

        $result = $testObject->jsonSerialize();

        // Verify result is an array
        $this->assertIsArray($result);

        // Empty array marked as object in schema should be stdClass
        $this->assertInstanceOf(stdClass::class, $result['emptyObject']);

        // Non-empty object should remain array
        $this->assertIsArray($result['nonEmptyObject']);
        $this->assertEquals(['key' => 'value'], $result['nonEmptyObject']);

        // Empty array marked as array in schema should remain array
        $this->assertIsArray($result['emptyArray']);
        $this->assertEmpty($result['emptyArray']);

        // Non-empty array should remain array
        $this->assertIsArray($result['nonEmptyArray']);
        $this->assertEquals([1, 2, 3], $result['nonEmptyArray']);

        // Verify JSON encoding produces correct output
        $json = json_encode($result);
        $this->assertIsString($json);
        $decoded = json_decode($json, true);

        // In JSON, empty object should be {} not []
        $this->assertStringContainsString('"emptyObject":{}', $json);
        $this->assertStringContainsString('"emptyArray":[]', $json);
    }

    /**
     * Tests nested object conversion.
     *
     * @return void
     */
    public function testNestedObjectConversion(): void
    {
        $testObject = new class extends AbstractDataValueObject {
            public function toArray(): array
            {
                return [
                    'nested' => [
                        'emptyChild' => [],
                        'nonEmptyChild' => ['value' => 'test'],
                    ],
                ];
            }

            public static function fromArray(array $array)
            {
                return new static();
            }

            public static function getJsonSchema(): array
            {
                return [
                    'type' => 'object',
                    'properties' => [
                        'nested' => [
                            'type' => 'object',
                            'properties' => [
                                'emptyChild' => [
                                    'type' => 'object',
                                    'properties' => []
                                ],
                                'nonEmptyChild' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'value' => ['type' => 'string']
                                    ]
                                ],
                            ],
                        ],
                    ],
                ];
            }
        };

        $result = $testObject->jsonSerialize();

        // Verify result is an array
        $this->assertIsArray($result);
        $this->assertIsArray($result['nested']);
        $this->assertInstanceOf(stdClass::class, $result['nested']['emptyChild']);
        $this->assertIsArray($result['nested']['nonEmptyChild']);

        $json = json_encode($result);
        $this->assertIsString($json);
        $this->assertStringContainsString('"emptyChild":{}', $json);
    }

    /**
     * Tests handling of oneOf schemas uses first schema without validation.
     *
     * @return void
     */
    public function testOneOfSchemaHandling(): void
    {
        $testObject = new class extends AbstractDataValueObject {
            public function toArray(): array
            {
                return [
                    'dynamicField' => [
                        'type' => 'objectType',
                        'data' => [],
                    ],
                ];
            }

            public static function fromArray(array $array)
            {
                return new static();
            }

            public static function getJsonSchema(): array
            {
                return [
                    'type' => 'object',
                    'properties' => [
                        'dynamicField' => [
                            'oneOf' => [
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => [
                                            'type' => 'string',
                                            'const' => 'objectType'
                                        ],
                                        'data' => [
                                            'type' => 'object',
                                            'properties' => []
                                        ],
                                    ],
                                    'required' => ['type', 'data'],
                                ],
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'type' => [
                                            'type' => 'string',
                                            'const' => 'arrayType'
                                        ],
                                        'data' => [
                                            'type' => 'array',
                                            'items' => ['type' => 'string']
                                        ],
                                    ],
                                    'required' => ['type', 'data'],
                                ],
                            ],
                        ],
                    ],
                ];
            }
        };

        $result = $testObject->jsonSerialize();

        // Verify result is an array
        $this->assertIsArray($result);

        // The implementation uses the first oneOf schema without validation
        // Since the first schema has 'data' as type 'object', empty array is converted
        $this->assertIsArray($result['dynamicField']);
        $this->assertInstanceOf(stdClass::class, $result['dynamicField']['data']);

        $json = json_encode($result);
        $this->assertIsString($json);
        $this->assertStringContainsString('"data":{}', $json);
    }

    /**
     * Tests that arrays of objects are processed recursively.
     *
     * @return void
     */
    public function testArrayOfObjectsProcessing(): void
    {
        $testObject = new class extends AbstractDataValueObject {
            public function toArray(): array
            {
                return [
                    'items' => [
                        ['name' => 'Item 1', 'metadata' => []],
                        ['name' => 'Item 2', 'metadata' => ['key' => 'value']],
                        ['name' => 'Item 3', 'metadata' => []],
                    ],
                ];
            }

            public static function fromArray(array $array)
            {
                return new static();
            }

            public static function getJsonSchema(): array
            {
                return [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'metadata' => [
                                        'type' => 'object',
                                        'properties' => []
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            }
        };

        $result = $testObject->jsonSerialize();

        // Verify result is an array
        $this->assertIsArray($result);

        // Verify array structure is preserved
        $this->assertIsArray($result['items']);
        $this->assertCount(3, $result['items']);

        // Each item should have empty metadata converted to stdClass
        $items = $result['items'];
        $this->assertIsArray($items[0]);
        $this->assertInstanceOf(stdClass::class, $items[0]['metadata']);
        $this->assertIsArray($items[1]);
        $this->assertIsArray($items[1]['metadata']); // Non-empty remains array
        $this->assertIsArray($items[2]);
        $this->assertInstanceOf(stdClass::class, $items[2]['metadata']);

        $json = json_encode($result);
        $this->assertIsString($json);
        $this->assertStringContainsString('"metadata":{}', $json);
        $this->assertStringContainsString('"metadata":{"key":"value"}', $json);
    }

    /**
     * Tests deeply nested structure conversion.
     *
     * @return void
     */
    public function testDeeplyNestedStructures(): void
    {
        $testObject = new class extends AbstractDataValueObject {
            public function toArray(): array
            {
                return [
                    'level1' => [
                        'level2' => [
                            'level3' => [
                                'emptyObject' => [],
                                'emptyArray' => [],
                            ],
                        ],
                    ],
                ];
            }

            public static function fromArray(array $array)
            {
                return new static();
            }

            public static function getJsonSchema(): array
            {
                return [
                    'type' => 'object',
                    'properties' => [
                        'level1' => [
                            'type' => 'object',
                            'properties' => [
                                'level2' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'level3' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'emptyObject' => [
                                                    'type' => 'object',
                                                    'properties' => []
                                                ],
                                                'emptyArray' => [
                                                    'type' => 'array',
                                                    'items' => ['type' => 'string']
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            }
        };

        $result = $testObject->jsonSerialize();

        // Verify result is an array
        $this->assertIsArray($result);

        // Verify deep nesting is preserved
        $this->assertIsArray($result['level1']);
        $this->assertIsArray($result['level1']['level2']);
        $this->assertIsArray($result['level1']['level2']['level3']);

        // Verify conversions at deepest level
        $this->assertInstanceOf(stdClass::class, $result['level1']['level2']['level3']['emptyObject']);
        $this->assertIsArray($result['level1']['level2']['level3']['emptyArray']);
        $this->assertEmpty($result['level1']['level2']['level3']['emptyArray']);
    }

    /**
     * Tests that non-array data types pass through unchanged.
     *
     * @return void
     */
    public function testNonArrayDataPassesThrough(): void
    {
        $testObject = new class extends AbstractDataValueObject {
            public function toArray(): array
            {
                return [
                    'string' => 'test',
                    'number' => 42,
                    'float' => 3.14,
                    'boolean' => true,
                    'null' => null,
                    'mixedObject' => [
                        'value' => 'test',
                        'emptyData' => [],
                    ],
                ];
            }

            public static function fromArray(array $array)
            {
                return new static();
            }

            public static function getJsonSchema(): array
            {
                return [
                    'type' => 'object',
                    'properties' => [
                        'string' => ['type' => 'string'],
                        'number' => ['type' => 'integer'],
                        'float' => ['type' => 'number'],
                        'boolean' => ['type' => 'boolean'],
                        'null' => ['type' => 'null'],
                        'mixedObject' => [
                            'type' => 'object',
                            'properties' => [
                                'value' => ['type' => 'string'],
                                'emptyData' => [
                                    'type' => 'object',
                                    'properties' => []
                                ],
                            ],
                        ],
                    ],
                ];
            }
        };

        $result = $testObject->jsonSerialize();

        // Verify result is an array
        $this->assertIsArray($result);

        // Non-array values should pass through unchanged
        $this->assertSame('test', $result['string']);
        $this->assertSame(42, $result['number']);
        $this->assertSame(3.14, $result['float']);
        $this->assertSame(true, $result['boolean']);
        $this->assertNull($result['null']);

        // Mixed object should have empty array converted
        $this->assertIsArray($result['mixedObject']);
        $this->assertSame('test', $result['mixedObject']['value']);
        $this->assertInstanceOf(stdClass::class, $result['mixedObject']['emptyData']);
    }

    /**
     * Tests behavior when schema is missing or incomplete.
     *
     * @return void
     */
    public function testMissingSchemaProperties(): void
    {
        $testObject = new class extends AbstractDataValueObject {
            public function toArray(): array
            {
                return [
                    'hasSchema' => [],
                    'noSchema' => [],
                ];
            }

            public static function fromArray(array $array)
            {
                return new static();
            }

            public static function getJsonSchema(): array
            {
                return [
                    'type' => 'object',
                    'properties' => [
                        'hasSchema' => [
                            'type' => 'object',
                            'properties' => []
                        ],
                        // 'noSchema' is intentionally missing from properties
                    ],
                ];
            }
        };

        $result = $testObject->jsonSerialize();

        // Verify result is an array
        $this->assertIsArray($result);

        // Property with schema should be converted
        $this->assertInstanceOf(stdClass::class, $result['hasSchema']);

        // Property without schema should remain as-is
        $this->assertIsArray($result['noSchema']);
        $this->assertEmpty($result['noSchema']);

        $json = json_encode($result);
        $this->assertIsString($json);
        $this->assertStringContainsString('"hasSchema":{}', $json);
        $this->assertStringContainsString('"noSchema":[]', $json);
    }

    /**
     * Tests that AbstractDataValueObject implements all required interfaces.
     *
     * @return void
     */
    public function testImplementsRequiredInterfaces(): void
    {
        $testObject = new class extends AbstractDataValueObject {
            public function toArray(): array
            {
                return ['test' => 'value'];
            }

            public static function fromArray(array $array)
            {
                return new static();
            }

            public static function getJsonSchema(): array
            {
                return [
                    'type' => 'object',
                    'properties' => [
                        'test' => ['type' => 'string']
                    ],
                ];
            }
        };

        // Verify interface implementations
        $this->assertInstanceOf(\WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface::class, $testObject);
        $this->assertInstanceOf(\WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface::class, $testObject);
        $this->assertInstanceOf(JsonSerializable::class, $testObject);

        // Verify methods exist and work
        $this->assertIsArray($testObject->toArray());
        $this->assertIsArray($testObject::getJsonSchema());
        $this->assertNotNull($testObject->jsonSerialize());
    }
}
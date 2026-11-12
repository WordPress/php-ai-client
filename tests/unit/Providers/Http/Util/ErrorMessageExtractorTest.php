<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\Util;

use PHPUnit\Framework\TestCase;
use stdClass;
use WordPress\AiClient\Providers\Http\Util\ErrorMessageExtractor;

/**
 * @covers \WordPress\AiClient\Providers\Http\Util\ErrorMessageExtractor
 */
class ErrorMessageExtractorTest extends TestCase
{
    /**
     * Tests that extractFromResponseData returns null when the input is not an array.
     *
     * @dataProvider nonArrayDataProvider
     *
     * @param mixed $data Non-array input data.
     * @return void
     */
    public function testExtractFromResponseDataReturnsNullForNonArray($data): void
    {
        $this->assertNull(ErrorMessageExtractor::extractFromResponseData($data));
    }

    /**
     * Provides non-array data types.
     *
     * @return array
     */
    public function nonArrayDataProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'string' => ['error occurred'],
            'integer' => [500],
            'float' => [404.5],
            'boolean true' => [true],
            'boolean false' => [false],
            'object' => [new stdClass()],
        ];
    }

    /**
     * Tests extraction from an array of error objects (e.g. Google Gemini API error payload).
     *
     * @dataProvider arrayOfErrorObjectsDataProvider
     *
     * @param array $data The response data array.
     * @param string $expectedMessage The expected extracted error message.
     * @return void
     */
    public function testExtractFromResponseDataWithArrayOfErrorObjects(array $data, string $expectedMessage): void
    {
        $this->assertSame($expectedMessage, ErrorMessageExtractor::extractFromResponseData($data));
    }

    /**
     * Provides array-wrapped error object data.
     *
     * @return array
     */
    public function arrayOfErrorObjectsDataProvider(): array
    {
        return [
            'standard list with error message' => [
                [
                    [
                        'error' => [
                            'message' => 'API rate limit exceeded.',
                        ],
                    ],
                ],
                'API rate limit exceeded.',
            ],
            'list with multiple elements extracts from first' => [
                [
                    [
                        'error' => [
                            'message' => 'Primary error message.',
                        ],
                    ],
                    [
                        'error' => [
                            'message' => 'Secondary error message.',
                        ],
                    ],
                ],
                'Primary error message.',
            ],
            'list with extra metadata in error object' => [
                [
                    [
                        'error' => [
                            'message' => 'Quota reached.',
                            'code' => 429,
                            'status' => 'RESOURCE_EXHAUSTED',
                        ],
                    ],
                ],
                'Quota reached.',
            ],
        ];
    }

    /**
     * Tests extraction from a nested associative error object (e.g. OpenAI API error payload).
     *
     * @dataProvider nestedErrorObjectDataProvider
     *
     * @param array $data The response data array.
     * @param string $expectedMessage The expected extracted error message.
     * @return void
     */
    public function testExtractFromResponseDataWithNestedErrorObject(array $data, string $expectedMessage): void
    {
        $this->assertSame($expectedMessage, ErrorMessageExtractor::extractFromResponseData($data));
    }

    /**
     * Provides nested associative error object data.
     *
     * @return array
     */
    public function nestedErrorObjectDataProvider(): array
    {
        return [
            'standard nested error message' => [
                [
                    'error' => [
                        'message' => 'The model `gpt-4` does not exist or you do not have access to it.',
                    ],
                ],
                'The model `gpt-4` does not exist or you do not have access to it.',
            ],
            'nested error with additional properties' => [
                [
                    'error' => [
                        'message' => 'Invalid parameter provided.',
                        'type' => 'invalid_request_error',
                        'param' => 'prompt',
                        'code' => 'parameter_invalid',
                    ],
                ],
                'Invalid parameter provided.',
            ],
        ];
    }

    /**
     * Tests extraction when the error property is a simple string.
     *
     * @dataProvider simpleErrorStringDataProvider
     *
     * @param array $data The response data array.
     * @param string $expectedMessage The expected extracted error message.
     * @return void
     */
    public function testExtractFromResponseDataWithSimpleErrorString(array $data, string $expectedMessage): void
    {
        $this->assertSame($expectedMessage, ErrorMessageExtractor::extractFromResponseData($data));
    }

    /**
     * Provides simple error string data.
     *
     * @return array
     */
    public function simpleErrorStringDataProvider(): array
    {
        return [
            'simple error string' => [
                [
                    'error' => 'Unauthorized access: Invalid API key.',
                ],
                'Unauthorized access: Invalid API key.',
            ],
            'simple error string with other metadata' => [
                [
                    'error' => 'Forbidden access.',
                    'status' => 403,
                ],
                'Forbidden access.',
            ],
        ];
    }

    /**
     * Tests extraction when the message property is a simple string.
     *
     * @dataProvider messageStringDataProvider
     *
     * @param array $data The response data array.
     * @param string $expectedMessage The expected extracted error message.
     * @return void
     */
    public function testExtractFromResponseDataWithMessageString(array $data, string $expectedMessage): void
    {
        $this->assertSame($expectedMessage, ErrorMessageExtractor::extractFromResponseData($data));
    }

    /**
     * Provides message string data.
     *
     * @return array
     */
    public function messageStringDataProvider(): array
    {
        return [
            'simple message string' => [
                [
                    'message' => 'Endpoint deprecated or not found.',
                ],
                'Endpoint deprecated or not found.',
            ],
            'simple message string with code' => [
                [
                    'message' => 'Service Unavailable.',
                    'code' => 503,
                ],
                'Service Unavailable.',
            ],
        ];
    }

    /**
     * Tests extraction format precedence when multiple error keys are present.
     *
     * @return void
     */
    public function testExtractFromResponseDataFormatPrecedence(): void
    {
        // First element in array takes precedence over root error property.
        $dataWithArrayAndRoot = [
            [
                'error' => [
                    'message' => 'List element error message.',
                ],
            ],
            'error' => [
                'message' => 'Root error message.',
            ],
        ];
        $this->assertSame(
            'List element error message.',
            ErrorMessageExtractor::extractFromResponseData($dataWithArrayAndRoot)
        );

        // Nested error['message'] takes precedence over root message property.
        $dataWithNestedAndMessage = [
            'error' => [
                'message' => 'Nested error message.',
            ],
            'message' => 'Root message string.',
        ];
        $this->assertSame(
            'Nested error message.',
            ErrorMessageExtractor::extractFromResponseData($dataWithNestedAndMessage)
        );

        // Root error string takes precedence over root message property.
        $dataWithErrorAndMessageString = [
            'error' => 'Error string message.',
            'message' => 'Root message string.',
        ];
        $this->assertSame(
            'Error string message.',
            ErrorMessageExtractor::extractFromResponseData($dataWithErrorAndMessageString)
        );
    }

    /**
     * Tests that extractFromResponseData returns null for invalid, empty, or unrecognized shapes.
     *
     * @dataProvider invalidOrUnrecognizedDataProvider
     *
     * @param array $data The invalid response data array.
     * @return void
     */
    public function testExtractFromResponseDataWithInvalidOrUnrecognizedDataReturnsNull(array $data): void
    {
        $this->assertNull(ErrorMessageExtractor::extractFromResponseData($data));
    }

    /**
     * Provides invalid, empty, or unrecognized data structures.
     *
     * @return array
     */
    public function invalidOrUnrecognizedDataProvider(): array
    {
        return [
            'empty array' => [[]],
            'unrelated associative keys' => [
                [
                    'status' => 'failed',
                    'code' => 500,
                ],
            ],
            'error object without message key' => [
                [
                    'error' => [
                        'code' => 400,
                        'type' => 'bad_request',
                    ],
                ],
            ],
            'error object with non-string message (int)' => [
                [
                    'error' => [
                        'message' => 404,
                    ],
                ],
            ],
            'error object with non-string message (null)' => [
                [
                    'error' => [
                        'message' => null,
                    ],
                ],
            ],
            'error object with non-string message (array)' => [
                [
                    'error' => [
                        'message' => [
                            'detail' => 'nested array',
                        ],
                    ],
                ],
            ],
            'error object with non-string message (bool)' => [
                [
                    'error' => [
                        'message' => false,
                    ],
                ],
            ],
            'error value is integer' => [
                [
                    'error' => 500,
                ],
            ],
            'error value is null' => [
                [
                    'error' => null,
                ],
            ],
            'error value is boolean' => [
                [
                    'error' => true,
                ],
            ],
            'error value is empty array' => [
                [
                    'error' => [],
                ],
            ],
            'message value is integer' => [
                [
                    'message' => 500,
                ],
            ],
            'message value is null' => [
                [
                    'message' => null,
                ],
            ],
            'message value is boolean' => [
                [
                    'message' => false,
                ],
            ],
            'message value is array' => [
                [
                    'message' => [
                        'detail' => 'nested array',
                    ],
                ],
            ],
            'index 0 is integer' => [
                [123],
            ],
            'index 0 is string' => [
                ['simple string'],
            ],
            'index 0 is null' => [
                [null],
            ],
            'index 0 error is string instead of array' => [
                [
                    [
                        'error' => 'not an array',
                    ],
                ],
            ],
            'index 0 error is empty array' => [
                [
                    [
                        'error' => [],
                    ],
                ],
            ],
            'index 0 error message is integer' => [
                [
                    [
                        'error' => [
                            'message' => 123,
                        ],
                    ],
                ],
            ],
            'index 0 error message is array' => [
                [
                    [
                        'error' => [
                            'message' => [
                                'nested' => 'array',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

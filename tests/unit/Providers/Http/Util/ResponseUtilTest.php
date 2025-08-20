<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\Util;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;

/**
 * @covers \WordPress\AiClient\Providers\Http\Util\ResponseUtil
 */
class ResponseUtilTest extends TestCase
{
    /**
     * Tests that throwIfNotSuccessful does not throw an exception for successful responses.
     *
     * @dataProvider successfulResponseStatusCodeProvider
     * @param int $statusCode The successful HTTP status code.
     * @return void
     */
    public function testThrowIfNotSuccessfulDoesNotThrowForSuccessfulResponses(int $statusCode): void
    {
        $response = $this->createMock(Response::class);
        $response->method('isSuccessful')->willReturn(true);
        $response->method('getStatusCode')->willReturn($statusCode);

        // Expect no exception to be thrown
        $this->expectNotToPerformAssertions();
        ResponseUtil::throwIfNotSuccessful($response);
    }

    /**
     * Provides successful HTTP status codes.
     *
     * @return array
     */
    public function successfulResponseStatusCodeProvider(): array
    {
        return [
            '200 OK' => [200],
            '201 Created' => [201],
            '204 No Content' => [204],
        ];
    }

    /**
     * Tests that throwIfNotSuccessful throws an exception for unsuccessful responses.
     *
     * @dataProvider unsuccessfulResponseStatusCodeProvider
     * @param int $statusCode The unsuccessful HTTP status code.
     * @param array $data The response data.
     * @param string $expectedMessagePart The expected part of the exception message.
     * @return void
     */
    public function testThrowIfNotSuccessfulThrowsForUnsuccessfulResponses(
        int $statusCode,
        array $data,
        string $expectedMessagePart
    ): void {
        $response = $this->createMock(Response::class);
        $response->method('isSuccessful')->willReturn(false);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getData')->willReturn($data);

        $this->expectException(ResponseException::class);
        $this->expectExceptionCode($statusCode);
        $this->expectExceptionMessageMatches("/^Bad status code: {$statusCode}\.($| {$expectedMessagePart})$/");

        ResponseUtil::throwIfNotSuccessful($response);
    }

    /**
     * Provides unsuccessful HTTP status codes and corresponding data for testing.
     *
     * @return array
     */
    public function unsuccessfulResponseStatusCodeProvider(): array
    {
        return [
            '400 Bad Request (no extra message)' => [
                400,
                [],
                '',
            ],
            '401 Unauthorized (error.message)' => [
                401,
                ['error' => ['message' => 'Invalid API key.']],
                'Invalid API key\.',
            ],
            '403 Forbidden (error string)' => [
                403,
                ['error' => 'Access denied.'],
                'Access denied\.',
            ],
            '404 Not Found (message string)' => [
                404,
                ['message' => 'Resource not found.'],
                'Resource not found\.',
            ],
            '500 Internal Server Error (no extra message)' => [
                500,
                [],
                '',
            ],
            '503 Service Unavailable (error.message with special chars)' => [
                503,
                ['error' => ['message' => 'Service is temporarily unavailable. Please try again later.']],
                'Service is temporarily unavailable\. Please try again later\.',
            ],
        ];
    }
}

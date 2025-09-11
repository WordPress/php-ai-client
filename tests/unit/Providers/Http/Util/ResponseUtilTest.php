<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\Util;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WordPress\AiClient\Providers\Http\Exception\ServerException;
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
     * Tests that throwIfNotSuccessful throws ClientException for 400 Bad Request.
     *
     * @return void
     */
    public function testThrowIfNotSuccessfulThrowsClientExceptionFor400BadRequest(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('isSuccessful')->willReturn(false);
        $response->method('getStatusCode')->willReturn(400);
        $response->method('getBody')->willReturn('');

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Bad request (400): Invalid request parameters');

        ResponseUtil::throwIfNotSuccessful($response);
    }

    /**
     * Tests that throwIfNotSuccessful throws ClientException for 4xx client errors.
     *
     * @dataProvider clientErrorStatusCodeProvider
     * @param int $statusCode The 4xx HTTP status code.
     * @param array $data The response data.
     * @param string $expectedMessagePart The expected part of the exception message.
     * @return void
     */
    public function testThrowIfNotSuccessfulThrowsClientExceptionFor4xxErrors(
        int $statusCode,
        array $data,
        string $expectedMessagePart
    ): void {
        $response = $this->createMock(Response::class);
        $response->method('isSuccessful')->willReturn(false);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getData')->willReturn($data);

        $this->expectException(ClientException::class);
        $this->expectExceptionCode($statusCode);
        $this->expectExceptionMessageMatches(
            "/^Client error \\({$statusCode}\\): Request was rejected due to " .
            "client-side issue( - {$expectedMessagePart})?$/"
        );

        ResponseUtil::throwIfNotSuccessful($response);
    }

    /**
     * Tests that throwIfNotSuccessful throws ServerException for 5xx server errors.
     *
     * @dataProvider serverErrorStatusCodeProvider
     * @param int $statusCode The 5xx HTTP status code.
     * @param array $data The response data.
     * @param string $expectedMessagePart The expected part of the exception message.
     * @return void
     */
    public function testThrowIfNotSuccessfulThrowsServerExceptionFor5xxErrors(
        int $statusCode,
        array $data,
        string $expectedMessagePart
    ): void {
        $response = $this->createMock(Response::class);
        $response->method('isSuccessful')->willReturn(false);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getData')->willReturn($data);

        $this->expectException(ServerException::class);
        $this->expectExceptionCode($statusCode);
        $this->expectExceptionMessageMatches(
            "/^Server error \\({$statusCode}\\): Request failed due to server-side issue( - {$expectedMessagePart})?$/"
        );

        ResponseUtil::throwIfNotSuccessful($response);
    }

    /**
     * Provides 4xx client error HTTP status codes and corresponding data for testing.
     *
     * @return array
     */
    public function clientErrorStatusCodeProvider(): array
    {
        return [
            '401 Unauthorized (error.message)' => [
                401,
                ['error' => ['message' => 'Invalid API key.']],
                'Invalid API key\\.',
            ],
            '403 Forbidden (error string)' => [
                403,
                ['error' => 'Access denied.'],
                'Access denied\\.',
            ],
            '404 Not Found (message string)' => [
                404,
                ['message' => 'Resource not found.'],
                'Resource not found\\.',
            ],
        ];
    }

    /**
     * Provides 5xx server error HTTP status codes and corresponding data for testing.
     *
     * @return array
     */
    public function serverErrorStatusCodeProvider(): array
    {
        return [
            '500 Internal Server Error (no extra message)' => [
                500,
                [],
                '',
            ],
            '503 Service Unavailable (error.message with special chars)' => [
                503,
                ['error' => ['message' => 'Service is temporarily unavailable. Please try again later.']],
                'Service is temporarily unavailable\\. Please try again later\\.',
            ],
        ];
    }
}

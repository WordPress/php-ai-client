<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Exceptions;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Contracts\AiClientExceptionInterface;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Common\Exception\TokenLimitReachedException;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WordPress\AiClient\Providers\Http\Exception\NetworkException;
use WordPress\AiClient\Providers\Http\Exception\ServerException;

/**
 * Tests for AI Client exceptions.
 *
 * @since 0.2.0
 * @covers \WordPress\AiClient\Common\Exception\InvalidArgumentException
 * @covers \WordPress\AiClient\Common\Exception\RuntimeException
 * @covers \WordPress\AiClient\Common\Exception\TokenLimitReachedException
 * @covers \WordPress\AiClient\Providers\Http\Exception\NetworkException
 * @covers \WordPress\AiClient\Providers\Http\Exception\ClientException
 * @covers \WordPress\AiClient\Providers\Http\Exception\ServerException
 */
class ExceptionsTest extends TestCase
{
    public function testAllExceptionsImplementAiClientExceptionInterface(): void
    {
        $exceptions = [
            new InvalidArgumentException('test'),
            new RuntimeException('test'),
            new TokenLimitReachedException('test'),
            new NetworkException('test'),
            new ClientException('test'),
            new ServerException('test'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(AiClientExceptionInterface::class, $exception);
        }
    }

    public function testTokenLimitReachedExceptionExtendsRuntimeException(): void
    {
        $exception = new TokenLimitReachedException('token limit reached');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testTokenLimitReachedExceptionMaxTokensDefaultsToNull(): void
    {
        $exception = new TokenLimitReachedException('token limit reached');

        $this->assertNull($exception->getMaxTokens());
    }

    public function testTokenLimitReachedExceptionStoresMaxTokens(): void
    {
        $exception = new TokenLimitReachedException('token limit reached', 4096);

        $this->assertSame(4096, $exception->getMaxTokens());
    }

    public function testCatchAllFunctionality(): void
    {
        $exceptions = [
            new InvalidArgumentException('invalid error'),
            new RuntimeException('runtime error'),
            new TokenLimitReachedException('token limit error'),
            new NetworkException('network error'),
            new ClientException('client error'),
            new ServerException('server error'),
        ];

        foreach ($exceptions as $exception) {
            $caught = false;
            try {
                throw $exception;
            } catch (AiClientExceptionInterface $e) {
                $caught = true;
                $this->assertStringContainsString('error', $e->getMessage());
            }
            $this->assertTrue($caught, 'Exception should be catchable as AiClientExceptionInterface');
        }
    }

    public function testServerExceptionExtendsRuntimeException(): void
    {
        $exception = new ServerException('server error');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    /**
     * @dataProvider knownServerStatusCodeProvider
     */
    public function testServerExceptionFromKnownStatusCodes(int $statusCode, string $expectedText): void
    {
        $response = new Response($statusCode, []);

        $exception = ServerException::fromServerErrorResponse($response);

        $this->assertSame($statusCode, $exception->getCode());
        $this->assertStringContainsString($expectedText, $exception->getMessage());
        $this->assertStringContainsString((string) $statusCode, $exception->getMessage());
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function knownServerStatusCodeProvider(): array
    {
        return [
            '500 Internal Server Error' => [500, 'Internal Server Error'],
            '502 Bad Gateway'           => [502, 'Bad Gateway'],
            '503 Service Unavailable'   => [503, 'Service Unavailable'],
            '504 Gateway Timeout'       => [504, 'Gateway Timeout'],
            '507 Insufficient Storage'  => [507, 'Insufficient Storage'],
            '529 Overloaded'            => [529, 'Overloaded'],
        ];
    }

    public function testServerExceptionFromUnknownStatusCode(): void
    {
        $response = new Response(599, []);

        $exception = ServerException::fromServerErrorResponse($response);

        $this->assertSame(599, $exception->getCode());
        $this->assertStringContainsString('Server error (599)', $exception->getMessage());
        $this->assertStringContainsString('server-side issue', $exception->getMessage());
    }

    public function testServerExceptionExtractsErrorMessageFromResponseBody(): void
    {
        $body = json_encode(['error' => ['message' => 'The server is overloaded']]);
        $response = new Response(500, [], $body);

        $exception = ServerException::fromServerErrorResponse($response);

        $this->assertStringContainsString('Internal Server Error (500)', $exception->getMessage());
        $this->assertStringContainsString('The server is overloaded', $exception->getMessage());
    }

    public function testServerExceptionExtractsStringErrorFromResponseBody(): void
    {
        $body = json_encode(['error' => 'Something went wrong']);
        $response = new Response(502, [], $body);

        $exception = ServerException::fromServerErrorResponse($response);

        $this->assertStringContainsString('Bad Gateway (502)', $exception->getMessage());
        $this->assertStringContainsString('Something went wrong', $exception->getMessage());
    }

    public function testServerExceptionExtractsMessageKeyFromResponseBody(): void
    {
        $body = json_encode(['message' => 'Service temporarily unavailable']);
        $response = new Response(503, [], $body);

        $exception = ServerException::fromServerErrorResponse($response);

        $this->assertStringContainsString('Service Unavailable (503)', $exception->getMessage());
        $this->assertStringContainsString('Service temporarily unavailable', $exception->getMessage());
    }

    public function testServerExceptionWithNoBody(): void
    {
        $response = new Response(500, []);

        $exception = ServerException::fromServerErrorResponse($response);

        $this->assertSame('Internal Server Error (500)', $exception->getMessage());
    }

    public function testServerExceptionWithNonJsonBody(): void
    {
        $response = new Response(500, [], '<html>Server Error</html>');

        $exception = ServerException::fromServerErrorResponse($response);

        $this->assertSame('Internal Server Error (500)', $exception->getMessage());
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Exceptions;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Contracts\AiClientExceptionInterface;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Common\Exception\ProviderUnavailableException;
use WordPress\AiClient\Common\Exception\TokenLimitReachedException;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WordPress\AiClient\Providers\Http\Exception\NetworkException;

/**
 * Tests for AI Client exceptions.
 *
 * @since 0.2.0
 * @covers \WordPress\AiClient\Common\Exception\InvalidArgumentException
 * @covers \WordPress\AiClient\Common\Exception\RuntimeException
 * @covers \WordPress\AiClient\Common\Exception\ProviderUnavailableException
 * @covers \WordPress\AiClient\Common\Exception\TokenLimitReachedException
 * @covers \WordPress\AiClient\Providers\Http\Exception\NetworkException
 * @covers \WordPress\AiClient\Providers\Http\Exception\ClientException
 */
class ExceptionsTest extends TestCase
{
    public function testAllExceptionsImplementAiClientExceptionInterface(): void
    {
        $exceptions = [
            new InvalidArgumentException('test'),
            new RuntimeException('test'),
            new TokenLimitReachedException('test'),
            new ProviderUnavailableException('test'),
            new NetworkException('test'),
            new ClientException('test'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(AiClientExceptionInterface::class , $exception);
        }
    }

    public function testTokenLimitReachedExceptionExtendsRuntimeException(): void
    {
        $exception = new TokenLimitReachedException('token limit reached');

        $this->assertInstanceOf(RuntimeException::class , $exception);
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

    public function testProviderUnavailableExceptionExtendsRuntimeException(): void
    {
        $exception = new ProviderUnavailableException('provider unavailable');

        $this->assertInstanceOf(RuntimeException::class , $exception);
    }

    public function testProviderUnavailableExceptionDefaultsToNull(): void
    {
        $exception = new ProviderUnavailableException('provider unavailable');

        $this->assertNull($exception->getHttpStatusCode());
        $this->assertNull($exception->getErrorType());
    }

    public function testProviderUnavailableExceptionStoresHttpStatusCode(): void
    {
        $exception = new ProviderUnavailableException('provider unavailable', 529);

        $this->assertSame(529, $exception->getHttpStatusCode());
    }

    public function testProviderUnavailableExceptionStoresErrorType(): void
    {
        $exception = new ProviderUnavailableException('provider unavailable', 529, 'overloaded_error');

        $this->assertSame('overloaded_error', $exception->getErrorType());
    }

    public function testCatchAllFunctionality(): void
    {
        $exceptions = [
            new InvalidArgumentException('invalid error'),
            new RuntimeException('runtime error'),
            new TokenLimitReachedException('token limit error'),
            new ProviderUnavailableException('provider unavailable error'),
            new NetworkException('network error'),
            new ClientException('client error'),
        ];

        foreach ($exceptions as $exception) {
            $caught = false;
            try {
                throw $exception;
            }
            catch (AiClientExceptionInterface $e) {
                $caught = true;
                $this->assertStringContainsString('error', $e->getMessage());
            }
            $this->assertTrue($caught, 'Exception should be catchable as AiClientExceptionInterface');
        }
    }
}
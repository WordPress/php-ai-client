<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Exceptions;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Exceptions\AiClientExceptionInterface;
use WordPress\AiClient\Exceptions\InvalidArgumentException;
use WordPress\AiClient\Exceptions\NetworkException;
use WordPress\AiClient\Exceptions\RequestException;
use WordPress\AiClient\Exceptions\RuntimeException;

/**
 * Tests for AI Client exceptions.
 *
 * @since 0.2.0
 * @covers \WordPress\AiClient\Exceptions\AiClientExceptionInterface
 * @covers \WordPress\AiClient\Exceptions\InvalidArgumentException
 * @covers \WordPress\AiClient\Exceptions\RuntimeException
 * @covers \WordPress\AiClient\Exceptions\NetworkException
 * @covers \WordPress\AiClient\Exceptions\RequestException
 */
class ExceptionsTest extends TestCase
{
    public function testAllExceptionsImplementAiClientExceptionInterface(): void
    {
        $exceptions = [
            new InvalidArgumentException('test'),
            new RuntimeException('test'),
            new NetworkException('test'),
            new RequestException('test'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(AiClientExceptionInterface::class, $exception);
        }
    }

    public function testCatchAllFunctionality(): void
    {
        $exceptions = [
            new InvalidArgumentException('invalid error'),
            new RuntimeException('runtime error'),
            new NetworkException('network error'),
            new RequestException('request error'),
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
}
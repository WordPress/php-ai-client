<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\Unit\Providers\Http\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Tests\traits\EnumTestTrait;

/**
 * Tests for the HttpMethodEnum class.
 *
 * @covers \WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum
 */
class HttpMethodEnumTest extends TestCase
{
    use EnumTestTrait;

    /**
     * {@inheritDoc}
     */
    protected function getEnumClass(): string
    {
        return HttpMethodEnum::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedValues(): array
    {
        return [
            'GET' => 'GET',
            'POST' => 'POST',
            'PUT' => 'PUT',
            'PATCH' => 'PATCH',
            'DELETE' => 'DELETE',
            'HEAD' => 'HEAD',
            'OPTIONS' => 'OPTIONS',
            'CONNECT' => 'CONNECT',
            'TRACE' => 'TRACE',
        ];
    }

    /**
     * Tests that idempotent methods are correctly identified.
     *
     * @return void
     */
    public function testIsIdempotent(): void
    {
        $this->assertTrue(HttpMethodEnum::GET()->isIdempotent());
        $this->assertTrue(HttpMethodEnum::HEAD()->isIdempotent());
        $this->assertTrue(HttpMethodEnum::OPTIONS()->isIdempotent());
        $this->assertTrue(HttpMethodEnum::TRACE()->isIdempotent());
        $this->assertTrue(HttpMethodEnum::PUT()->isIdempotent());
        $this->assertTrue(HttpMethodEnum::DELETE()->isIdempotent());

        $this->assertFalse(HttpMethodEnum::POST()->isIdempotent());
        $this->assertFalse(HttpMethodEnum::PATCH()->isIdempotent());
        $this->assertFalse(HttpMethodEnum::CONNECT()->isIdempotent());
    }

    /**
     * Tests that methods with bodies are correctly identified.
     *
     * @return void
     */
    public function testHasBody(): void
    {
        $this->assertTrue(HttpMethodEnum::POST()->hasBody());
        $this->assertTrue(HttpMethodEnum::PUT()->hasBody());
        $this->assertTrue(HttpMethodEnum::PATCH()->hasBody());

        $this->assertFalse(HttpMethodEnum::GET()->hasBody());
        $this->assertFalse(HttpMethodEnum::HEAD()->hasBody());
        $this->assertFalse(HttpMethodEnum::OPTIONS()->hasBody());
        $this->assertFalse(HttpMethodEnum::TRACE()->hasBody());
        $this->assertFalse(HttpMethodEnum::DELETE()->hasBody());
        $this->assertFalse(HttpMethodEnum::CONNECT()->hasBody());
    }
}

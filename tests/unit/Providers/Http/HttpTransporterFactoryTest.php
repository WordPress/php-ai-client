<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\HttpTransporterFactory;

/**
 * Tests for HttpTransporterFactory class.
 *
 * @covers \WordPress\AiClient\Providers\Http\HttpTransporterFactory
 */
class HttpTransporterFactoryTest extends TestCase
{
    /**
     * Tests creating an HTTP transporter.
     *
     * @return void
     */
    public function testCreateTransporter(): void
    {
        $transporter = HttpTransporterFactory::createTransporter();

        $this->assertInstanceOf(HttpTransporterInterface::class, $transporter);
    }
}

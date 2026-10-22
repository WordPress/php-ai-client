<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\Streaming\ValueObjects;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\Streaming\ValueObjects\ServerSentEvent;

/**
 * @covers \WordPress\AiClient\Providers\Http\Streaming\ValueObjects\ServerSentEvent
 */
class ServerSentEventTest extends TestCase
{
    /**
     * Tests that the getters expose the constructor values.
     */
    public function testExposesAllConstructorValues(): void
    {
        $event = new ServerSentEvent('completion', '{"x":1}', 'evt-1', 2000);

        $this->assertSame('completion', $event->getEvent());
        $this->assertSame('{"x":1}', $event->getData());
        $this->assertSame('evt-1', $event->getId());
        $this->assertSame(2000, $event->getRetry());
    }

    /**
     * Tests that the id defaults to an empty string and retry to null.
     */
    public function testDefaults(): void
    {
        $event = new ServerSentEvent('message', 'payload');

        $this->assertSame('message', $event->getEvent());
        $this->assertSame('payload', $event->getData());
        $this->assertSame('', $event->getId());
        $this->assertNull($event->getRetry());
    }
}

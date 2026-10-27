<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\ValueObjects;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Results\ValueObjects\ToolCallDelta;

/**
 * @covers \WordPress\AiClient\Results\ValueObjects\ToolCallDelta
 */
class ToolCallDeltaTest extends TestCase
{
    /**
     * Tests that the getters expose the constructor values.
     */
    public function testExposesAllConstructorValues(): void
    {
        $delta = new ToolCallDelta(2, 'call_1', 'get_weather', '{"city":"SF"}');

        $this->assertSame(2, $delta->getIndex());
        $this->assertSame('call_1', $delta->getId());
        $this->assertSame('get_weather', $delta->getFunctionName());
        $this->assertSame('{"city":"SF"}', $delta->getArgumentsFragment());
    }

    /**
     * Tests that the optional fields default to null and an empty fragment.
     */
    public function testDefaultsWhenOnlyIndexProvided(): void
    {
        $delta = new ToolCallDelta(0);

        $this->assertSame(0, $delta->getIndex());
        $this->assertNull($delta->getId());
        $this->assertNull($delta->getFunctionName());
        $this->assertSame('', $delta->getArgumentsFragment());
    }

    /**
     * Tests that a null index is preserved.
     */
    public function testNullIndexIsPreserved(): void
    {
        $delta = new ToolCallDelta(null, 'call_1', 'fn', '{}');

        $this->assertNull($delta->getIndex());
    }
}

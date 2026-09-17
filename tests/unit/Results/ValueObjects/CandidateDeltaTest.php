<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\ValueObjects;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Results\ValueObjects\CandidateDelta;
use WordPress\AiClient\Results\ValueObjects\ToolCallDelta;
use WordPress\AiClient\Tools\DTO\FunctionCall;

/**
 * @covers \WordPress\AiClient\Results\ValueObjects\CandidateDelta
 */
class CandidateDeltaTest extends TestCase
{
    /**
     * Creates a content-channel text part.
     *
     * @param string $text The text.
     * @return MessagePart
     */
    private function createContentPart(string $text): MessagePart
    {
        return new MessagePart($text, MessagePartChannelEnum::content());
    }

    /**
     * Creates a thought-channel text part.
     *
     * @param string $text The text.
     * @return MessagePart
     */
    private function createReasoningPart(string $text): MessagePart
    {
        return new MessagePart($text, MessagePartChannelEnum::thought());
    }

    /**
     * Tests that the getters expose the constructor values.
     */
    public function testExposesConstructorValues(): void
    {
        $parts = [$this->createContentPart('Hi')];
        $toolCallDeltas = [new ToolCallDelta(0, 'call_1', 'fn', '{}')];
        $delta = new CandidateDelta(1, $parts, FinishReasonEnum::stop(), $toolCallDeltas);

        $this->assertSame(1, $delta->getIndex());
        $this->assertSame($parts, $delta->getParts());
        $this->assertTrue($delta->getFinishReason()->is(FinishReasonEnum::stop()));
        $this->assertSame($toolCallDeltas, $delta->getToolCallDeltas());
    }

    /**
     * Tests that the optional fields default to empty values and a null finish reason.
     */
    public function testDefaults(): void
    {
        $delta = new CandidateDelta(0);

        $this->assertSame([], $delta->getParts());
        $this->assertNull($delta->getFinishReason());
        $this->assertSame([], $delta->getToolCallDeltas());
    }

    /**
     * Tests that getDeltaText concatenates only the content-channel parts.
     */
    public function testGetDeltaTextConcatenatesContentChannelOnly(): void
    {
        $delta = new CandidateDelta(0, [$this->createContentPart('Hel'), $this->createContentPart('lo')]);

        $this->assertSame('Hello', $delta->getDeltaText());
    }

    /**
     * Tests that getReasoningDeltaText concatenates only the thought-channel parts.
     */
    public function testGetReasoningDeltaTextConcatenatesThoughtChannelOnly(): void
    {
        $delta = new CandidateDelta(0, [$this->createReasoningPart('Think'), $this->createReasoningPart('ing')]);

        $this->assertSame('Thinking', $delta->getReasoningDeltaText());
    }

    /**
     * Tests that the content and reasoning channels do not bleed into each other.
     */
    public function testContentAndReasoningChannelsDoNotBleed(): void
    {
        $delta = new CandidateDelta(0, [$this->createReasoningPart('reason'), $this->createContentPart('answer')]);

        $this->assertSame('answer', $delta->getDeltaText());
        $this->assertSame('reason', $delta->getReasoningDeltaText());
    }

    /**
     * Tests that a non-text content part is ignored when reading the delta text.
     */
    public function testNonTextPartIsIgnoredInDeltaText(): void
    {
        $delta = new CandidateDelta(0, [
            $this->createContentPart('a'),
            new MessagePart(new FunctionCall('id', 'fn', [])),
            $this->createContentPart('b'),
        ]);

        $this->assertSame('ab', $delta->getDeltaText());
    }

    /**
     * Tests that a candidate with no parts yields empty delta text.
     */
    public function testEmptyDeltaTextWhenNoParts(): void
    {
        $delta = new CandidateDelta(0);

        $this->assertSame('', $delta->getDeltaText());
        $this->assertSame('', $delta->getReasoningDeltaText());
    }
}

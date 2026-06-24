<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\ValueObjects;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\ValueObjects\CandidateDelta;
use WordPress\AiClient\Results\ValueObjects\GenerativeAiResultChunk;
use WordPress\AiClient\Results\ValueObjects\ToolCallDelta;

/**
 * @covers \WordPress\AiClient\Results\ValueObjects\GenerativeAiResultChunk
 */
class GenerativeAiResultChunkTest extends TestCase
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
        $usage = new TokenUsage(1, 2, 3);
        $candidateDeltas = [new CandidateDelta(0, [$this->createContentPart('Hi')])];
        $chunk = new GenerativeAiResultChunk('chatcmpl-1', $usage, ['model' => 'x'], $candidateDeltas);

        $this->assertSame('chatcmpl-1', $chunk->getId());
        $this->assertSame($usage, $chunk->getTokenUsage());
        $this->assertSame(['model' => 'x'], $chunk->getAdditionalData());
        $this->assertSame($candidateDeltas, $chunk->getCandidateDeltas());
    }

    /**
     * Tests that the optional fields default to null and empty values.
     */
    public function testDefaults(): void
    {
        $chunk = new GenerativeAiResultChunk();

        $this->assertNull($chunk->getId());
        $this->assertNull($chunk->getTokenUsage());
        $this->assertSame([], $chunk->getAdditionalData());
        $this->assertSame([], $chunk->getCandidateDeltas());
    }

    /**
     * Tests that getDeltaText concatenates the content text across candidate deltas.
     */
    public function testGetDeltaTextFlattensAcrossCandidateDeltas(): void
    {
        $chunk = new GenerativeAiResultChunk(null, null, [], [
            new CandidateDelta(0, [$this->createContentPart('A')]),
            new CandidateDelta(1, [$this->createContentPart('B')]),
        ]);

        $this->assertSame('AB', $chunk->getDeltaText());
    }

    /**
     * Tests that getReasoningDeltaText concatenates the thought text across candidate deltas.
     */
    public function testGetReasoningDeltaTextFlattensAcrossCandidateDeltas(): void
    {
        $chunk = new GenerativeAiResultChunk(null, null, [], [
            new CandidateDelta(0, [$this->createReasoningPart('think ')]),
            new CandidateDelta(1, [$this->createReasoningPart('more')]),
        ]);

        $this->assertSame('think more', $chunk->getReasoningDeltaText());
    }

    /**
     * Tests that getToolCallDeltas flattens the tool calls across candidate deltas.
     */
    public function testGetToolCallDeltasFlattensAcrossCandidateDeltas(): void
    {
        $chunk = new GenerativeAiResultChunk(null, null, [], [
            new CandidateDelta(0, [], null, [new ToolCallDelta(0, 'a', 'fn_a', '')]),
            new CandidateDelta(1, [], null, [new ToolCallDelta(0, 'b', 'fn_b', '')]),
        ]);

        $deltas = $chunk->getToolCallDeltas();
        $this->assertCount(2, $deltas);
        $this->assertSame('a', $deltas[0]->getId());
        $this->assertSame('b', $deltas[1]->getId());
    }

    /**
     * Tests that a metadata-only chunk has empty convenience accessors.
     */
    public function testConveniencesEmptyWhenMetadataOnly(): void
    {
        $chunk = new GenerativeAiResultChunk('id', new TokenUsage(1, 1, 2), ['model' => 'x'], []);

        $this->assertSame('', $chunk->getDeltaText());
        $this->assertSame('', $chunk->getReasoningDeltaText());
        $this->assertSame([], $chunk->getToolCallDeltas());
    }

    /**
     * Tests that the content and reasoning channels do not bleed at the chunk level.
     */
    public function testContentAndReasoningDoNotBleed(): void
    {
        $chunk = new GenerativeAiResultChunk(null, null, [], [
            new CandidateDelta(0, [$this->createReasoningPart('reason'), $this->createContentPart('answer')]),
        ]);

        $this->assertSame('answer', $chunk->getDeltaText());
        $this->assertSame('reason', $chunk->getReasoningDeltaText());
    }
}

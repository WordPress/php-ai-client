<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\ChunkAccumulator;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Results\ValueObjects\CandidateDelta;
use WordPress\AiClient\Results\ValueObjects\GenerativeAiResultChunk;
use WordPress\AiClient\Results\ValueObjects\ToolCallDelta;
use WordPress\AiClient\Tools\DTO\FunctionCall;

/**
 * @covers \WordPress\AiClient\Results\ChunkAccumulator
 */
class ChunkAccumulatorTest extends TestCase
{
    /**
     * Creates test provider metadata.
     *
     * @return ProviderMetadata
     */
    private function createTestProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata('test-provider', 'Test Provider', ProviderTypeEnum::cloud());
    }

    /**
     * Creates test model metadata.
     *
     * @return ModelMetadata
     */
    private function createTestModelMetadata(): ModelMetadata
    {
        return new ModelMetadata('test-model', 'Test Model', [], []);
    }

    /**
     * Creates an accumulator with the test provider/model metadata.
     *
     * @return ChunkAccumulator
     */
    private function createAccumulator(): ChunkAccumulator
    {
        return new ChunkAccumulator($this->createTestProviderMetadata(), $this->createTestModelMetadata());
    }

    /**
     * Creates a content-channel message part.
     *
     * @param string $text The text.
     * @param string|null $signature The thought signature.
     * @return MessagePart
     */
    private function createContentPart(string $text, ?string $signature = null): MessagePart
    {
        return new MessagePart($text, MessagePartChannelEnum::content(), $signature);
    }

    /**
     * Creates a thought-channel message part.
     *
     * @param string $text The text.
     * @param string|null $signature The thought signature.
     * @return MessagePart
     */
    private function createReasoningPart(string $text, ?string $signature = null): MessagePart
    {
        return new MessagePart($text, MessagePartChannelEnum::thought(), $signature);
    }

    /**
     * Creates a chunk.
     *
     * @param string|null $id The result id.
     * @param TokenUsage|null $usage The token usage.
     * @param array<string, mixed> $additionalData The provider metadata.
     * @param list<CandidateDelta> $candidateDeltas The candidate deltas.
     * @return GenerativeAiResultChunk
     */
    private function createChunk(
        ?string $id = null,
        ?TokenUsage $usage = null,
        array $additionalData = [],
        array $candidateDeltas = []
    ): GenerativeAiResultChunk {
        return new GenerativeAiResultChunk($id, $usage, $additionalData, $candidateDeltas);
    }

    /**
     * Tests that the id is captured from the first chunk that reports one.
     */
    public function testCapturesIdFromFirstChunkThatReportsOne(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createContentPart('hi')])]));
        $acc->add($this->createChunk('first', null, [], []));
        $acc->add($this->createChunk('second', null, [], []));

        $this->assertSame('first', $acc->build()->getId());
    }

    /**
     * Tests that token usage is last-wins.
     */
    public function testTokenUsageIsLastWins(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createContentPart('hi')])]));
        $acc->add($this->createChunk(null, new TokenUsage(1, 1, 2), [], []));
        $acc->add($this->createChunk(null, new TokenUsage(10, 20, 30), [], []));

        $usage = $acc->build()->getTokenUsage();
        $this->assertSame(10, $usage->getPromptTokens());
        $this->assertSame(30, $usage->getTotalTokens());
    }

    /**
     * Tests that additional data is merged with later chunks winning.
     */
    public function testAdditionalDataMergedWithLaterChunksWinning(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, ['model' => 'x', 'a' => 1], [
            new CandidateDelta(0, [$this->createContentPart('hi')]),
        ]));
        $acc->add($this->createChunk(null, null, [], []));
        $acc->add($this->createChunk(null, null, ['model' => 'y', 'b' => 2], []));

        $this->assertSame(['model' => 'y', 'a' => 1, 'b' => 2], $acc->build()->getAdditionalData());
    }

    /**
     * Tests that text on the same channel is concatenated into one part.
     */
    public function testTextConcatenatedPerChannel(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createContentPart('Hel')])]));
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createContentPart('lo')])]));

        $parts = $acc->build()->getCandidates()[0]->getMessage()->getParts();
        $this->assertCount(1, $parts);
        $this->assertSame('Hello', $parts[0]->getText());
        $this->assertTrue($parts[0]->getChannel()->is(MessagePartChannelEnum::content()));
    }

    /**
     * Tests that reasoning and content become separate parts in arrival order.
     */
    public function testReasoningAndContentAreSeparatePartsInArrivalOrder(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [$this->createReasoningPart('because'), $this->createContentPart('Hi')]),
        ]));

        $parts = $acc->build()->getCandidates()[0]->getMessage()->getParts();
        $this->assertCount(2, $parts);
        $this->assertTrue($parts[0]->getChannel()->is(MessagePartChannelEnum::thought()));
        $this->assertSame('because', $parts[0]->getText());
        $this->assertTrue($parts[1]->getChannel()->is(MessagePartChannelEnum::content()));
        $this->assertSame('Hi', $parts[1]->getText());
    }

    /**
     * Tests that the thought signature is captured last-wins and a null signature does not clear it.
     */
    public function testThoughtSignatureCapturedLastWinsPerChannel(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [
                $this->createReasoningPart('a', 's1'),
                $this->createReasoningPart('b', 's2'),
                $this->createReasoningPart('c', null),
            ]),
        ]));
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createContentPart('hi')])]));

        $parts = $acc->build()->getCandidates()[0]->getMessage()->getParts();
        $this->assertSame('abc', $parts[0]->getText());
        $this->assertSame('s2', $parts[0]->getThoughtSignature());
        $this->assertNull($parts[1]->getThoughtSignature());
    }

    /**
     * Tests that the finish reason defaults to stop when not reported.
     */
    public function testFinishReasonDefaultsToStopWhenNotReported(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createContentPart('hi')])]));

        $this->assertTrue($acc->build()->getCandidates()[0]->getFinishReason()->is(FinishReasonEnum::stop()));
    }

    /**
     * Tests that the reported finish reason is used when present.
     */
    public function testFinishReasonUsedWhenReported(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [$this->createContentPart('hi')], FinishReasonEnum::length()),
        ]));

        $this->assertTrue($acc->build()->getCandidates()[0]->getFinishReason()->is(FinishReasonEnum::length()));
    }

    /**
     * Tests that a non-text part is kept and placed after the text parts.
     */
    public function testNonTextPartIsKeptAndPlacedAfterText(): void
    {
        $acc = $this->createAccumulator();
        $functionCallPart = new MessagePart(new FunctionCall('id', 'fn', ['k' => 'v']));
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [$this->createContentPart('Hi'), $functionCallPart]),
        ]));

        $parts = $acc->build()->getCandidates()[0]->getMessage()->getParts();
        $this->assertCount(2, $parts);
        $this->assertSame('Hi', $parts[0]->getText());
        $this->assertNotNull($parts[1]->getFunctionCall());
        $this->assertSame('fn', $parts[1]->getFunctionCall()->getName());
    }

    /**
     * Tests that tool-call fragments are stitched by slot into a function call.
     */
    public function testToolCallReassembledFromFragments(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [], null, [new ToolCallDelta(0, 'call_1', 'get_weather', '{"loc')]),
        ]));
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [], null, [new ToolCallDelta(0, null, null, 'ation":"SF"}')]),
        ]));
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [], FinishReasonEnum::toolCalls())]));

        $parts = $acc->build()->getCandidates()[0]->getMessage()->getParts();
        $this->assertCount(1, $parts);
        $fc = $parts[0]->getFunctionCall();
        $this->assertNotNull($fc);
        $this->assertSame('call_1', $fc->getId());
        $this->assertSame('get_weather', $fc->getName());
        $this->assertSame(['location' => 'SF'], $fc->getArgs());
    }

    /**
     * Tests that tool-call id and name are first-wins across fragments.
     */
    public function testToolCallIdAndNameAreFirstWins(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [], null, [new ToolCallDelta(0, 'first-id', null, '')]),
        ]));
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [], null, [new ToolCallDelta(0, 'ignored-id', 'the_fn', '{}')]),
        ]));

        $fc = $acc->build()->getCandidates()[0]->getMessage()->getParts()[0]->getFunctionCall();
        $this->assertSame('first-id', $fc->getId());
        $this->assertSame('the_fn', $fc->getName());
        $this->assertSame([], $fc->getArgs());
    }

    /**
     * Tests decoding of tool-call arguments.
     *
     * @dataProvider toolCallArgumentsProvider
     *
     * @param string $argumentsFragment The accumulated arguments string.
     * @param mixed $expected The expected decoded arguments.
     */
    public function testDecodesToolCallArguments(string $argumentsFragment, $expected): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [], null, [new ToolCallDelta(0, 'id', 'fn', $argumentsFragment)]),
        ]));

        $fc = $acc->build()->getCandidates()[0]->getMessage()->getParts()[0]->getFunctionCall();
        $this->assertSame($expected, $fc->getArgs());
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public function toolCallArgumentsProvider(): array
    {
        return [
            'valid JSON decodes to an array' => ['{"city":"SF"}', ['city' => 'SF']],
            'nested JSON decodes recursively' => ['{"a":{"b":1}}', ['a' => ['b' => 1]]],
            'broken JSON is kept as the raw string' => ['{"city":"SF', '{"city":"SF'],
            'empty arguments become null' => ['', null],
        ];
    }

    /**
     * Tests that parallel tool calls are emitted in slot-index order.
     */
    public function testParallelToolCallsAreOrderedBySlot(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [], null, [
                new ToolCallDelta(1, 'call_b', 'fn_b', '{"y":2}'),
                new ToolCallDelta(0, 'call_a', 'fn_a', '{"x":1}'),
            ]),
        ]));

        $parts = $acc->build()->getCandidates()[0]->getMessage()->getParts();
        $this->assertCount(2, $parts);
        $this->assertSame('fn_a', $parts[0]->getFunctionCall()->getName());
        $this->assertSame('fn_b', $parts[1]->getFunctionCall()->getName());
    }

    /**
     * Tests that a tool-call slot without an id or name is skipped.
     */
    public function testToolCallSlotWithoutIdOrNameIsSkipped(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [$this->createContentPart('hi')], null, [
                new ToolCallDelta(0, null, null, '{"x":1}'),
            ]),
        ]));

        $parts = $acc->build()->getCandidates()[0]->getMessage()->getParts();
        $this->assertCount(1, $parts);
        $this->assertSame('hi', $parts[0]->getText());
    }

    /**
     * Tests that tool-call fragments without an index stitch into slot 0.
     */
    public function testToolCallDeltaWithoutIndexUsesSlotZero(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [], null, [new ToolCallDelta(null, 'id', 'fn', '{"a":')]),
        ]));
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [], null, [new ToolCallDelta(null, null, null, '1}')]),
        ]));

        $parts = $acc->build()->getCandidates()[0]->getMessage()->getParts();
        $this->assertCount(1, $parts);
        $this->assertSame(['a' => 1], $parts[0]->getFunctionCall()->getArgs());
    }

    /**
     * Tests that candidates are separated and sorted by index.
     */
    public function testCandidatesAreSeparatedAndSortedByIndex(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(1, [$this->createContentPart('B')]),
            new CandidateDelta(0, [$this->createContentPart('A')]),
        ]));

        $candidates = $acc->build()->getCandidates();
        $this->assertCount(2, $candidates);
        $this->assertSame('A', $candidates[0]->getMessage()->getParts()[0]->getText());
        $this->assertSame('B', $candidates[1]->getMessage()->getParts()[0]->getText());
    }

    /**
     * Tests that hasCandidates() reflects the accumulated state.
     */
    public function testHasCandidatesReflectsAccumulatedState(): void
    {
        $acc = $this->createAccumulator();
        $this->assertFalse($acc->hasCandidates());
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createContentPart('hi')])]));
        $this->assertTrue($acc->hasCandidates());
    }

    /**
     * Tests that a metadata-only chunk registers no candidate.
     */
    public function testMetadataOnlyChunkRegistersNoCandidate(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk('id', new TokenUsage(1, 1, 2), ['model' => 'x'], []));

        $this->assertFalse($acc->hasCandidates());
    }

    /**
     * Tests that build() throws when no candidate was accumulated.
     */
    public function testBuildThrowsWhenNoCandidates(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk('id', new TokenUsage(1, 1, 2), ['model' => 'x'], []));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The stream produced no candidates.');
        $acc->build();
    }

    /**
     * Tests that build() applies defaults when metadata is absent.
     */
    public function testBuildAppliesDefaultsWhenMetadataAbsent(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createContentPart('hi')])]));

        $result = $acc->build();
        $this->assertSame('', $result->getId());
        $this->assertSame(0, $result->getTokenUsage()->getTotalTokens());
        $this->assertSame([], $result->getAdditionalData());
    }

    /**
     * Tests that build() uses the provider and model metadata.
     */
    public function testBuildUsesProviderAndModelMetadata(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createContentPart('hi')])]));

        $result = $acc->build();
        $this->assertSame('test-provider', $result->getProviderMetadata()->getId());
        $this->assertSame('test-model', $result->getModelMetadata()->getId());
    }

    /**
     * Tests that a candidate with no parts builds an empty message.
     */
    public function testCandidateWithNoPartsBuildsEmptyMessage(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [], FinishReasonEnum::contentFilter())]));

        $candidate = $acc->build()->getCandidates()[0];
        $this->assertCount(0, $candidate->getMessage()->getParts());
        $this->assertTrue($candidate->getFinishReason()->is(FinishReasonEnum::contentFilter()));
    }

    /**
     * Tests that tool-call arguments reassemble from many small fragments.
     */
    public function testToolCallArgumentsReassembleFromManySmallFragments(): void
    {
        $acc = $this->createAccumulator();
        $pieces = ['{', '"ci', 'ty":', ' "San ', 'Francisco"', '}'];
        foreach ($pieces as $i => $piece) {
            $acc->add($this->createChunk(null, null, [], [
                new CandidateDelta(0, [], null, [
                    new ToolCallDelta(0, $i === 0 ? 'call_1' : null, $i === 0 ? 'get_weather' : null, $piece),
                ]),
            ]));
        }

        $fc = $acc->build()->getCandidates()[0]->getMessage()->getParts()[0]->getFunctionCall();
        $this->assertSame('get_weather', $fc->getName());
        $this->assertSame(['city' => 'San Francisco'], $fc->getArgs());
    }

    /**
     * Tests that parallel tool-call fragments interleaved across deltas stitch per slot.
     */
    public function testParallelToolCallFragmentsInterleaveAcrossDeltas(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [], null, [
                new ToolCallDelta(0, 'a', 'fn_a', '{"x":'),
                new ToolCallDelta(1, 'b', 'fn_b', '{"y":'),
            ]),
        ]));
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [], null, [new ToolCallDelta(0, null, null, '1}')]),
        ]));
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [], null, [new ToolCallDelta(1, null, null, '2}')]),
        ]));

        $parts = $acc->build()->getCandidates()[0]->getMessage()->getParts();
        $this->assertCount(2, $parts);
        $this->assertSame('fn_a', $parts[0]->getFunctionCall()->getName());
        $this->assertSame(['x' => 1], $parts[0]->getFunctionCall()->getArgs());
        $this->assertSame('fn_b', $parts[1]->getFunctionCall()->getName());
        $this->assertSame(['y' => 2], $parts[1]->getFunctionCall()->getArgs());
    }

    /**
     * Tests that interleaved reasoning and content concatenate per channel.
     */
    public function testReasoningAndContentInterleaveAcrossDeltas(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createReasoningPart('think ')])]));
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createContentPart('Hi ')])]));
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createReasoningPart('more')])]));
        $acc->add($this->createChunk(null, null, [], [new CandidateDelta(0, [$this->createContentPart('there')])]));

        $parts = $acc->build()->getCandidates()[0]->getMessage()->getParts();
        $this->assertCount(2, $parts);
        $this->assertTrue($parts[0]->getChannel()->is(MessagePartChannelEnum::thought()));
        $this->assertSame('think more', $parts[0]->getText());
        $this->assertTrue($parts[1]->getChannel()->is(MessagePartChannelEnum::content()));
        $this->assertSame('Hi there', $parts[1]->getText());
    }

    /**
     * Tests that parts are ordered thought-then-content even when content arrives first.
     */
    public function testContentArrivingBeforeReasoningStillOrdersThoughtFirst(): void
    {
        $acc = $this->createAccumulator();
        $acc->add($this->createChunk(null, null, [], [
            new CandidateDelta(0, [$this->createContentPart('answer'), $this->createReasoningPart('reason')]),
        ]));

        $parts = $acc->build()->getCandidates()[0]->getMessage()->getParts();
        $this->assertCount(2, $parts);
        $this->assertTrue($parts[0]->getChannel()->is(MessagePartChannelEnum::thought()));
        $this->assertSame('reason', $parts[0]->getText());
        $this->assertTrue($parts[1]->getChannel()->is(MessagePartChannelEnum::content()));
        $this->assertSame('answer', $parts[1]->getText());
    }
}

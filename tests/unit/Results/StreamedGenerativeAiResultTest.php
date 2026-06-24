<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results;

use ArrayIterator;
use Generator;
use Iterator;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Results\StreamedGenerativeAiResult;
use WordPress\AiClient\Results\ValueObjects\CandidateDelta;
use WordPress\AiClient\Results\ValueObjects\GenerativeAiResultChunk;

/**
 * @covers \WordPress\AiClient\Results\StreamedGenerativeAiResult
 */
class StreamedGenerativeAiResultTest extends TestCase
{
    /**
     * @return ProviderMetadata
     */
    private function createTestProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata('test-provider', 'Test Provider', ProviderTypeEnum::cloud());
    }

    /**
     * @return ModelMetadata
     */
    private function createTestModelMetadata(): ModelMetadata
    {
        return new ModelMetadata('test-model', 'Test Model', [], []);
    }

    /**
     * Creates a streamed result handle over the given chunk iterator.
     *
     * @param Iterator<int, GenerativeAiResultChunk> $chunks The chunk stream.
     * @return StreamedGenerativeAiResult
     */
    private function createHandle(Iterator $chunks): StreamedGenerativeAiResult
    {
        return new StreamedGenerativeAiResult(
            $chunks,
            $this->createTestProviderMetadata(),
            $this->createTestModelMetadata()
        );
    }

    /**
     * Creates a handle over a fixed list of chunks.
     *
     * @param list<GenerativeAiResultChunk> $chunks The chunks.
     * @return StreamedGenerativeAiResult
     */
    private function createHandleFromChunks(array $chunks): StreamedGenerativeAiResult
    {
        return $this->createHandle(new ArrayIterator($chunks));
    }

    /**
     * Creates a content chunk for candidate 0.
     *
     * @param string $text The content text delta.
     * @param FinishReasonEnum|null $finishReason The finish reason, if any.
     * @return GenerativeAiResultChunk
     */
    private function createContentChunk(string $text, ?FinishReasonEnum $finishReason = null): GenerativeAiResultChunk
    {
        return new GenerativeAiResultChunk(null, null, [], [
            new CandidateDelta(0, [new MessagePart($text)], $finishReason),
        ]);
    }

    /**
     * Creates a metadata-only chunk carrying token usage.
     *
     * @param TokenUsage $usage The usage.
     * @return GenerativeAiResultChunk
     */
    private function createUsageChunk(TokenUsage $usage): GenerativeAiResultChunk
    {
        return new GenerativeAiResultChunk(null, $usage, [], []);
    }

    /**
     * Yields the given chunks, then throws.
     *
     * @param list<GenerativeAiResultChunk> $chunks The chunks to yield before failing.
     * @param \Throwable $error The error to throw after the chunks.
     * @return Generator<int, GenerativeAiResultChunk>
     */
    private function createFailingIterator(array $chunks, \Throwable $error): Generator
    {
        foreach ($chunks as $chunk) {
            yield $chunk;
        }
        throw $error;
    }

    /**
     * Creates a single-use source that throws if it is read after exhaustion.
     *
     * @param list<GenerativeAiResultChunk> $chunks The chunks to yield once.
     * @return Iterator<int, GenerativeAiResultChunk>
     */
    private function createSingleUseSource(array $chunks): Iterator
    {
        return new class ($chunks) implements Iterator {
            /** @var list<GenerativeAiResultChunk> */
            private array $chunks;
            private int $pos = 0;
            private bool $exhausted = false;

            /** @param list<GenerativeAiResultChunk> $chunks */
            public function __construct(array $chunks)
            {
                $this->chunks = array_values($chunks);
            }

            public function current(): GenerativeAiResultChunk
            {
                $this->guardNotExhausted();
                return $this->chunks[$this->pos];
            }

            public function next(): void
            {
                $this->guardNotExhausted();
                $this->pos++;
                if ($this->pos >= count($this->chunks)) {
                    $this->exhausted = true;
                }
            }

            public function key(): int
            {
                return $this->pos;
            }

            public function valid(): bool
            {
                return $this->pos < count($this->chunks);
            }

            public function rewind(): void
            {
                if ($this->pos !== 0 || $this->exhausted) {
                    throw new \LogicException('A single-use source cannot be rewound.');
                }
            }

            private function guardNotExhausted(): void
            {
                if ($this->exhausted) {
                    throw new \LogicException('A single-use source cannot be read after exhaustion.');
                }
            }
        };
    }

    /**
     * Tests that iterating yields all chunks in order.
     */
    public function testIteratingYieldsAllChunksInOrder(): void
    {
        $a = $this->createContentChunk('a');
        $b = $this->createContentChunk('b');
        $c = $this->createContentChunk('c');

        $collected = [];
        foreach ($this->createHandleFromChunks([$a, $b, $c]) as $chunk) {
            $collected[] = $chunk;
        }

        $this->assertSame([$a, $b, $c], $collected);
    }

    /**
     * Tests that getFinalResult() assembles the result without iterating.
     */
    public function testGetFinalResultAssemblesWithoutIterating(): void
    {
        $result = $this->createHandleFromChunks([
            $this->createContentChunk('Hel'),
            $this->createContentChunk('lo', FinishReasonEnum::stop()),
            $this->createUsageChunk(new TokenUsage(3, 5, 8)),
        ])->getFinalResult();

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertSame('Hello', $result->toText());
        $this->assertSame(8, $result->getTokenUsage()->getTotalTokens());
        $this->assertTrue($result->getCandidates()[0]->getFinishReason()->is(FinishReasonEnum::stop()));
    }

    /**
     * Tests that iterating then getFinalResult() returns the same assembled result.
     */
    public function testIterateThenGetFinalResultIsConsistent(): void
    {
        $handle = $this->createHandleFromChunks([
            $this->createContentChunk('Hel'),
            $this->createContentChunk('lo', FinishReasonEnum::stop()),
        ]);

        foreach ($handle as $chunk) {
            // Consume the stream but do nothing with the chunks.
        }

        $this->assertSame('Hello', $handle->getFinalResult()->toText());
    }

    /**
     * Tests that getFinalResult() returns the same instance on repeated calls.
     */
    public function testGetFinalResultIsIdempotent(): void
    {
        $handle = $this->createHandleFromChunks([$this->createContentChunk('hi', FinishReasonEnum::stop())]);

        $first = $handle->getFinalResult();
        $second = $handle->getFinalResult();

        $this->assertSame($first, $second);
    }

    /**
     * Tests that getFinalResult() drains the remainder after an early break.
     */
    public function testGetFinalResultAfterEarlyBreakDrainsRemainder(): void
    {
        $handle = $this->createHandleFromChunks([
            $this->createContentChunk('a'),
            $this->createContentChunk('b'),
            $this->createContentChunk('c', FinishReasonEnum::stop()),
        ]);

        foreach ($handle as $chunk) {
            break;
        }

        $this->assertSame('abc', $handle->getFinalResult()->toText());
    }

    /**
     * Tests that the completion callback fires once with the result after a full iteration.
     */
    public function testOnCompleteFiresOnceWithResultOnFullIteration(): void
    {
        $received = [];
        $handle = $this->createHandleFromChunks([$this->createContentChunk('hi', FinishReasonEnum::stop())]);
        $handle->onComplete(function (GenerativeAiResult $result) use (&$received) {
            $received[] = $result;
        });

        foreach ($handle as $chunk) {
            // Consume the stream but do nothing with the chunks.
        }

        $this->assertCount(1, $received);
        $this->assertSame($handle->getFinalResult(), $received[0]);
    }

    /**
     * Tests that the completion callback fires on getFinalResult() without iterating.
     */
    public function testOnCompleteFiresOnGetFinalResult(): void
    {
        $count = 0;
        $handle = $this->createHandleFromChunks([$this->createContentChunk('hi', FinishReasonEnum::stop())]);
        $handle->onComplete(function () use (&$count) {
            $count++;
        });

        $handle->getFinalResult();

        $this->assertSame(1, $count);
    }

    /**
     * Tests that the completion callback fires only once across iteration and getFinalResult().
     */
    public function testOnCompleteFiresOnlyOnceAcrossIterateAndGetFinalResult(): void
    {
        $count = 0;
        $handle = $this->createHandleFromChunks([$this->createContentChunk('hi', FinishReasonEnum::stop())]);
        $handle->onComplete(function () use (&$count) {
            $count++;
        });

        foreach ($handle as $chunk) {
            // Consume the stream but do nothing with the chunks.
        }
        $handle->getFinalResult();

        $this->assertSame(1, $count);
    }

    /**
     * Tests that multiple completion callbacks fire in registration order.
     */
    public function testMultipleOnCompleteCallbacksFireInRegistrationOrder(): void
    {
        $order = [];
        $handle = $this->createHandleFromChunks([$this->createContentChunk('hi', FinishReasonEnum::stop())]);
        $handle->onComplete(function () use (&$order) {
            $order[] = 'first';
        });
        $handle->onComplete(function () use (&$order) {
            $order[] = 'second';
        });

        $handle->getFinalResult();

        $this->assertSame(['first', 'second'], $order);
    }

    /**
     * Tests that the completion callback does not fire on an early break.
     */
    public function testOnCompleteNotFiredOnEarlyBreak(): void
    {
        $count = 0;
        $handle = $this->createHandleFromChunks([
            $this->createContentChunk('a'),
            $this->createContentChunk('b', FinishReasonEnum::stop()),
        ]);
        $handle->onComplete(function () use (&$count) {
            $count++;
        });

        foreach ($handle as $chunk) {
            break;
        }

        $this->assertSame(0, $count);
    }

    /**
     * Tests that an empty stream throws on getFinalResult() and fires no completion callback.
     */
    public function testEmptyStreamThrowsAndDoesNotFireOnComplete(): void
    {
        $count = 0;
        $handle = $this->createHandleFromChunks([$this->createUsageChunk(new TokenUsage(1, 1, 2))]);
        $handle->onComplete(function () use (&$count) {
            $count++;
        });

        try {
            $handle->getFinalResult();
            $this->fail('Expected RuntimeException for a stream with no candidates.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('no candidates', $e->getMessage());
        }

        $this->assertSame(0, $count);
    }

    /**
     * Tests that getFinalResult() after iterating an empty stream throws without re-reading the source.
     */
    public function testGetFinalResultAfterIteratingEmptyStreamThrowsWithoutReReadingSource(): void
    {
        $count = 0;
        $handle = $this->createHandle(
            $this->createSingleUseSource([$this->createUsageChunk(new TokenUsage(1, 1, 2))])
        );
        $handle->onComplete(function () use (&$count) {
            $count++;
        });

        foreach ($handle as $chunk) {
            // Consume the stream but do nothing with the chunks.
        }

        try {
            $handle->getFinalResult();
            $this->fail('Expected RuntimeException for a stream with no candidates.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('no candidates', $e->getMessage());
        }

        $this->assertSame(0, $count);
    }

    /**
     * Tests that a stream without a finish reason defaults to stop.
     */
    public function testPartialStreamWithoutFinishReasonDefaultsToStop(): void
    {
        $result = $this->createHandleFromChunks([$this->createContentChunk('partial')])->getFinalResult();

        $this->assertSame('partial', $result->toText());
        $this->assertTrue($result->getCandidates()[0]->getFinishReason()->is(FinishReasonEnum::stop()));
    }

    /**
     * Tests that iteration yields all chunks produced before an error, then propagates it.
     */
    public function testIterationYieldsChunksBeforeAnErrorThenPropagates(): void
    {
        $a = $this->createContentChunk('a');
        $b = $this->createContentChunk('b');
        $handle = $this->createHandle(
            $this->createFailingIterator([$a, $b], new RuntimeException('stream failed'))
        );

        $collected = [];
        $thrown = null;
        try {
            foreach ($handle as $chunk) {
                $collected[] = $chunk;
            }
        } catch (RuntimeException $e) {
            $thrown = $e;
        }

        $this->assertSame([$a, $b], $collected);
        $this->assertNotNull($thrown);
        $this->assertSame('stream failed', $thrown->getMessage());
    }

    /**
     * Tests that getFinalResult() propagates an error raised while draining.
     */
    public function testGetFinalResultPropagatesStreamError(): void
    {
        $handle = $this->createHandle(
            $this->createFailingIterator([$this->createContentChunk('a')], new RuntimeException('stream failed'))
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stream failed');
        $handle->getFinalResult();
    }

    /**
     * Tests that the completion callback does not fire when the stream errors.
     */
    public function testOnCompleteNotFiredWhenStreamErrors(): void
    {
        $count = 0;
        $handle = $this->createHandle(
            $this->createFailingIterator([$this->createContentChunk('a')], new RuntimeException('stream failed'))
        );
        $handle->onComplete(function () use (&$count) {
            $count++;
        });

        try {
            foreach ($handle as $chunk) {
                // Consume the stream but do nothing with the chunks.
            }
        } catch (RuntimeException $e) {
            // Expected error here.
        }

        $this->assertSame(0, $count);
    }

    /**
     * Tests that re-iterating a consumed stream throws.
     */
    public function testReiteratingAConsumedStreamThrows(): void
    {
        $handle = $this->createHandleFromChunks([$this->createContentChunk('hi', FinishReasonEnum::stop())]);

        foreach ($handle as $chunk) {
            // Consume the stream but do nothing with the chunks.
        }

        $this->expectException(RuntimeException::class);
        foreach ($handle as $chunk) {
        }
    }

    /**
     * Tests that iterating after getFinalResult() throws.
     */
    public function testIteratingAfterGetFinalResultThrows(): void
    {
        $handle = $this->createHandleFromChunks([$this->createContentChunk('hi', FinishReasonEnum::stop())]);
        $handle->getFinalResult();

        $this->expectException(RuntimeException::class);
        foreach ($handle as $chunk) {
        }
    }

    /**
     * Tests that the start callback fires exactly once when consumption begins.
     */
    public function testOnStartFiresOnceWhenConsumptionBegins(): void
    {
        $count = 0;
        $handle = $this->createHandleFromChunks([$this->createContentChunk('hi', FinishReasonEnum::stop())]);
        $handle->onStart(function () use (&$count) {
            $count++;
        });

        foreach ($handle as $chunk) {
        }

        $this->assertSame(1, $count);
    }

    /**
     * Tests that the start callback does not fire until the stream is consumed.
     */
    public function testOnStartNotFiredUntilConsumed(): void
    {
        $count = 0;
        $handle = $this->createHandleFromChunks([$this->createContentChunk('hi', FinishReasonEnum::stop())]);
        $handle->onStart(function () use (&$count) {
            $count++;
        });

        $this->assertSame(0, $count);

        $handle->getFinalResult();

        $this->assertSame(1, $count);
    }

    /**
     * Tests that the start callback fires even when the caller breaks early.
     */
    public function testOnStartFiresOnEarlyBreak(): void
    {
        $count = 0;
        $handle = $this->createHandleFromChunks([
            $this->createContentChunk('a'),
            $this->createContentChunk('b', FinishReasonEnum::stop()),
        ]);
        $handle->onStart(function () use (&$count) {
            $count++;
        });

        foreach ($handle as $chunk) {
            break;
        }

        $this->assertSame(1, $count);
    }

    /**
     * Tests that the error callback fires once with the error when iterating fails.
     */
    public function testOnErrorFiresOnceWhenIterationFails(): void
    {
        $received = [];
        $handle = $this->createHandle(
            $this->createFailingIterator([$this->createContentChunk('a')], new RuntimeException('stream failed'))
        );
        $handle->onError(function (\Throwable $e) use (&$received) {
            $received[] = $e;
        });

        try {
            foreach ($handle as $chunk) {
            }
        } catch (RuntimeException $e) {
        }

        $this->assertCount(1, $received);
        $this->assertSame('stream failed', $received[0]->getMessage());
    }

    /**
     * Tests that the error callback fires when getFinalResult() drains a failing stream.
     */
    public function testOnErrorFiresWhenGetFinalResultFails(): void
    {
        $count = 0;
        $handle = $this->createHandle(
            $this->createFailingIterator([$this->createContentChunk('a')], new RuntimeException('stream failed'))
        );
        $handle->onError(function () use (&$count) {
            $count++;
        });

        try {
            $handle->getFinalResult();
        } catch (RuntimeException $e) {
        }

        $this->assertSame(1, $count);
    }

    /**
     * Tests that the error callback does not fire on a successful stream.
     */
    public function testOnErrorNotFiredOnSuccess(): void
    {
        $count = 0;
        $handle = $this->createHandleFromChunks([$this->createContentChunk('hi', FinishReasonEnum::stop())]);
        $handle->onError(function () use (&$count) {
            $count++;
        });

        $handle->getFinalResult();

        $this->assertSame(0, $count);
    }

    /**
     * Tests that the error callback does not fire when the caller breaks early.
     */
    public function testOnErrorNotFiredOnEarlyBreak(): void
    {
        $count = 0;
        $handle = $this->createHandleFromChunks([
            $this->createContentChunk('a'),
            $this->createContentChunk('b', FinishReasonEnum::stop()),
        ]);
        $handle->onError(function () use (&$count) {
            $count++;
        });

        foreach ($handle as $chunk) {
            break;
        }

        $this->assertSame(0, $count);
    }

    /**
     * Tests that getFinalResult() after a failure re-throws the original stream error.
     */
    public function testGetFinalResultAfterFailureRethrowsOriginalError(): void
    {
        $handle = $this->createHandle(
            $this->createFailingIterator([$this->createContentChunk('a')], new RuntimeException('stream failed'))
        );

        try {
            foreach ($handle as $chunk) {
            }
        } catch (RuntimeException $e) {
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stream failed');
        $handle->getFinalResult();
    }

    /**
     * Tests that the error callback fires at most once across iteration and getFinalResult().
     */
    public function testOnErrorFiresAtMostOnceAcrossIterationAndGetFinalResult(): void
    {
        $count = 0;
        $handle = $this->createHandle(
            $this->createFailingIterator([$this->createContentChunk('a')], new RuntimeException('stream failed'))
        );
        $handle->onError(function () use (&$count) {
            $count++;
        });

        try {
            foreach ($handle as $chunk) {
            }
        } catch (RuntimeException $e) {
        }

        try {
            $handle->getFinalResult();
        } catch (RuntimeException $e) {
        }

        $this->assertSame(1, $count);
    }
}

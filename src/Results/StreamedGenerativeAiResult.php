<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results;

use Generator;
use Iterator;
use IteratorAggregate;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\ValueObjects\GenerativeAiResultChunk;

/**
 * Represents a streamed result from a generative AI operation.
 *
 * @since n.e.x.t
 *
 * @implements IteratorAggregate<int, GenerativeAiResultChunk>
 */
final class StreamedGenerativeAiResult implements IteratorAggregate
{
    /**
     * @var Iterator<int, GenerativeAiResultChunk> The source chunk stream.
     */
    private Iterator $chunks;

    private ChunkAccumulator $accumulator;

    /**
     * @var list<callable(GenerativeAiResult): void> Callbacks run once when the result is assembled.
     */
    private array $completionCallbacks = [];

    /**
     * @var bool Whether the source stream has been started.
     */
    private bool $started = false;

    /**
     * @var bool Whether the source stream has been fully read.
     */
    private bool $finished = false;

    /**
     * @var GenerativeAiResult|null The assembled result, once built.
     */
    private ?GenerativeAiResult $result = null;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param Iterator<int, GenerativeAiResultChunk> $chunks The source chunk stream.
     * @param ProviderMetadata $providerMetadata Provider metadata for the assembled result.
     * @param ModelMetadata $modelMetadata Model metadata for the assembled result.
     */
    public function __construct(Iterator $chunks, ProviderMetadata $providerMetadata, ModelMetadata $modelMetadata)
    {
        $this->chunks = $chunks;
        $this->accumulator = new ChunkAccumulator($providerMetadata, $modelMetadata);
    }

    /**
     * Registers a callback to run once, when the final result is first assembled.
     *
     * @since n.e.x.t
     *
     * @param callable(GenerativeAiResult): void $callback Receives the assembled result.
     * @return self
     */
    public function onComplete(callable $callback): self
    {
        $this->completionCallbacks[] = $callback;

        return $this;
    }

    /**
     * Yields each chunk as it is read, folding it into the accumulated state.
     *
     * @since n.e.x.t
     *
     * @return Generator<int, GenerativeAiResultChunk> The chunks, in order.
     *
     * @throws RuntimeException If the source stream has already been consumed.
     */
    public function getIterator(): Generator
    {
        if ($this->started) {
            throw new RuntimeException(
                'This streamed result has already been consumed; the stream can be read only once.'
            );
        }

        while (true) {
            $chunk = $this->pull();
            if ($chunk === null) {
                break;
            }
            yield $chunk;
        }

        $this->finalize();
    }

    /**
     * Returns the complete result, draining any unread chunks first.
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiResult The assembled result.
     * @throws RuntimeException If the stream produced no candidates.
     */
    public function getFinalResult(): GenerativeAiResult
    {
        if ($this->result === null) {
            while ($this->pull() !== null) {
                // Drain any remaining chunks so the result is complete.
            }
            $this->finalize();
        }

        if ($this->result === null) {
            throw new RuntimeException('The stream produced no candidates.');
        }

        return $this->result;
    }

    /**
     * Assembles the result and runs the completion callbacks.
     *
     * @since n.e.x.t
     *
     * @return void
     */
    private function finalize(): void
    {
        if (!$this->accumulator->hasCandidates()) {
            return;
        }

        $this->result = $this->accumulator->build();

        foreach ($this->completionCallbacks as $callback) {
            $callback($this->result);
        }
    }

    /**
     * Reads the next chunk from the source and folds it into the accumulated state.
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiResultChunk|null The next chunk, or null when the stream is exhausted.
     */
    private function pull(): ?GenerativeAiResultChunk
    {
        if ($this->finished) {
            return null;
        }

        if (!$this->started) {
            $this->chunks->rewind();
            $this->started = true;
        } else {
            $this->chunks->next();
        }

        if (!$this->chunks->valid()) {
            $this->finished = true;
            return null;
        }

        $chunk = $this->chunks->current();
        $this->accumulator->add($chunk);

        return $chunk;
    }
}

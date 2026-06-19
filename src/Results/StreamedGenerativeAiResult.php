<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results;

use ArrayIterator;
use Generator;
use Iterator;
use IteratorAggregate;
use IteratorIterator;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
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
    private ModelMetadata $modelMetadata;

    private ProviderMetadata $providerMetadata;

    /**
     * @var iterable<int, GenerativeAiResultChunk> The source chunk stream.
     */
    private iterable $chunks;

    /**
     * @var Iterator<int, GenerativeAiResultChunk>|null The lazily created source iterator.
     */
    private ?Iterator $iterator = null;

    /**
     * @var bool Whether the source stream has been fully read.
     */
    private bool $finished = false;

    /**
     * @var list<callable(GenerativeAiResult): void> Callbacks run once when the result is assembled.
     */
    private array $completionCallbacks = [];

    /**
     * @var GenerativeAiResult|null The assembled result, once built.
     */
    private ?GenerativeAiResult $result = null;

    /**
     * @var string|null The result id, captured from the first chunk that carries one.
     */
    private ?string $id = null;

    /**
     * @var TokenUsage|null The token usage, captured from the chunk that carries it.
     */
    private ?TokenUsage $tokenUsage = null;

    /**
     * @var array<int, bool> Candidate indices that carried content or a finish reason.
     */
    private array $candidates = [];

    /**
     * @var array<int, array<string, string>> Accumulated text, per candidate, per channel.
     */
    private array $text = [];

    /**
     * @var array<int, array<string, string>> Thought signature, per candidate, per channel.
     */
    private array $thoughtSignatures = [];

    /**
     * @var array<int, list<MessagePart>> Non-text parts, per candidate, in arrival order.
     */
    private array $otherParts = [];

    /**
     * @var array<int, FinishReasonEnum> Finish reason, per candidate.
     */
    private array $finishReasons = [];

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param iterable<int, GenerativeAiResultChunk> $chunks The source chunk stream.
     * @param ProviderMetadata $providerMetadata Provider metadata for the assembled result.
     * @param ModelMetadata $modelMetadata Model metadata for the assembled result.
     */
    public function __construct(iterable $chunks, ProviderMetadata $providerMetadata, ModelMetadata $modelMetadata)
    {
        $this->chunks = $chunks;
        $this->providerMetadata = $providerMetadata;
        $this->modelMetadata = $modelMetadata;
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
     */
    public function getIterator(): Generator
    {
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
     * Assembles the result once and runs the completion callbacks.
     *
     * A no-op if the result is already built or the stream produced no candidates,
     * so a fully iterated empty stream does not fire the completion callbacks.
     *
     * @since n.e.x.t
     *
     * @return void
     */
    private function finalize(): void
    {
        if ($this->result !== null || $this->candidates === []) {
            return;
        }

        $this->result = $this->buildResult();

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

        if ($this->iterator === null) {
            $this->iterator = $this->toIterator($this->chunks);
            $this->iterator->rewind();
        }

        $iterator = $this->iterator;

        if (!$iterator->valid()) {
            $this->finished = true;
            return null;
        }

        $chunk = $iterator->current();
        $this->accumulate($chunk);
        $iterator->next();

        return $chunk;
    }

    /**
     * Folds a single chunk into the accumulated state.
     *
     * @since n.e.x.t
     *
     * @param GenerativeAiResultChunk $chunk The chunk to fold in.
     * @return void
     */
    private function accumulate(GenerativeAiResultChunk $chunk): void
    {
        $id = $chunk->getId();
        if ($id !== null && $this->id === null) {
            $this->id = $id;
        }

        $tokenUsage = $chunk->getTokenUsage();
        if ($tokenUsage !== null) {
            $this->tokenUsage = $tokenUsage;
        }

        // A chunk with no candidate index carries only result-level metadata, such
        // as the final usage event, so it registers no candidate.
        $index = $chunk->getCandidateIndex();
        if ($index === null) {
            return;
        }

        $this->candidates[$index] = true;

        $finishReason = $chunk->getFinishReason();
        if ($finishReason !== null) {
            $this->finishReasons[$index] = $finishReason;
        }

        foreach ($chunk->getParts() as $part) {
            $this->addPart($index, $part);
        }
    }

    /**
     * Folds a content part into the candidate state.
     *
     * Text parts are concatenated per channel, so the final message has one part
     * per channel, matching a non-streamed response. Non-text parts are kept whole.
     *
     * @since n.e.x.t
     *
     * @param int $index The candidate index.
     * @param MessagePart $part The part to fold in.
     * @return void
     */
    private function addPart(int $index, MessagePart $part): void
    {
        $text = $part->getText();
        if ($text === null) {
            $this->otherParts[$index][] = $part;
            return;
        }

        $channel = $part->getChannel()->value;
        if (!isset($this->text[$index][$channel])) {
            $this->text[$index][$channel] = '';
        }
        $this->text[$index][$channel] .= $text;

        $signature = $part->getThoughtSignature();
        if ($signature !== null) {
            $this->thoughtSignatures[$index][$channel] = $signature;
        }
    }

    /**
     * Assembles the accumulated state into a result.
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiResult The assembled result.
     * @throws RuntimeException If no candidates were produced.
     */
    private function buildResult(): GenerativeAiResult
    {
        if ($this->candidates === []) {
            throw new RuntimeException('The stream produced no candidates.');
        }

        $indices = array_keys($this->candidates);
        sort($indices);

        $candidates = [];
        foreach ($indices as $index) {
            $candidates[] = $this->buildCandidate($index);
        }

        return new GenerativeAiResult(
            $this->id ?? '',
            $candidates,
            $this->tokenUsage ?? new TokenUsage(0, 0, 0),
            $this->providerMetadata,
            $this->modelMetadata
        );
    }

    /**
     * Builds a single candidate from its accumulated state.
     *
     * @since n.e.x.t
     *
     * @param int $index The candidate index.
     * @return Candidate The assembled candidate.
     */
    private function buildCandidate(int $index): Candidate
    {
        $parts = [];

        // Text parts, in the order their channels first appeared in the stream.
        foreach ($this->text[$index] ?? [] as $channel => $text) {
            $parts[] = new MessagePart(
                $text,
                MessagePartChannelEnum::from($channel),
                $this->thoughtSignatures[$index][$channel] ?? null
            );
        }

        foreach ($this->otherParts[$index] ?? [] as $part) {
            $parts[] = $part;
        }

        $message = new Message(MessageRoleEnum::model(), $parts);
        $finishReason = $this->finishReasons[$index] ?? FinishReasonEnum::stop();

        return new Candidate($message, $finishReason);
    }

    /**
     * Normalizes an iterable into an Iterator the result can pull from.
     *
     * @since n.e.x.t
     *
     * @param iterable<int, GenerativeAiResultChunk> $chunks The source chunk stream.
     * @return Iterator<int, GenerativeAiResultChunk> The normalized iterator.
     */
    private function toIterator(iterable $chunks): Iterator
    {
        if ($chunks instanceof Iterator) {
            return $chunks;
        }

        if ($chunks instanceof IteratorAggregate) {
            $inner = $chunks->getIterator();
            return $inner instanceof Iterator ? $inner : new IteratorIterator($inner);
        }

        /** @var array<int, GenerativeAiResultChunk> $chunks */
        return new ArrayIterator($chunks);
    }
}

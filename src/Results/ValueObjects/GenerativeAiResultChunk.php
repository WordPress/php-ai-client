<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\ValueObjects;

use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * Represents a single chunk of a streamed generative AI result.
 *
 * @todo Make this class readonly once php 8.2 is the minimum requirement.
 *
 * @since n.e.x.t
 */
final class GenerativeAiResultChunk
{
    /**
     * @var string|null The result id, when this chunk reports it.
     */
    private ?string $id;

    /**
     * @var TokenUsage|null The token usage, when this chunk reports it.
     */
    private ?TokenUsage $tokenUsage;

    /**
     * @var array<string, mixed> Result-level provider metadata carried by this chunk.
     */
    private array $additionalData;

    /**
     * @var list<CandidateDelta> The per-candidate deltas carried by this chunk.
     */
    private array $candidateDeltas;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string|null $id The result id, when reported.
     * @param TokenUsage|null $tokenUsage The token usage, when reported.
     * @param array<string, mixed> $additionalData Result-level provider metadata.
     * @param list<CandidateDelta> $candidateDeltas The per-candidate deltas.
     */
    public function __construct(
        ?string $id = null,
        ?TokenUsage $tokenUsage = null,
        array $additionalData = [],
        array $candidateDeltas = []
    ) {
        $this->id = $id;
        $this->tokenUsage = $tokenUsage;
        $this->additionalData = $additionalData;
        $this->candidateDeltas = $candidateDeltas;
    }

    /**
     * Gets the result id.
     *
     * @since n.e.x.t
     *
     * @return string|null The id, or null when not reported by this chunk.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Gets the token usage.
     *
     * @since n.e.x.t
     *
     * @return TokenUsage|null The token usage, or null when not reported by this chunk.
     */
    public function getTokenUsage(): ?TokenUsage
    {
        return $this->tokenUsage;
    }

    /**
     * Gets the result-level provider metadata carried by this chunk.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The provider metadata, possibly empty.
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    /**
     * Gets the per-candidate deltas carried by this chunk.
     *
     * @since n.e.x.t
     *
     * @return list<CandidateDelta> The candidate deltas, possibly empty (metadata-only event).
     */
    public function getCandidateDeltas(): array
    {
        return $this->candidateDeltas;
    }

    /**
     * Gets the content text delta carried by this chunk.
     *
     * @since n.e.x.t
     *
     * @return string The content text delta, or an empty string when this chunk carries none.
     */
    public function getDeltaText(): string
    {
        $text = '';
        foreach ($this->candidateDeltas as $delta) {
            $text .= $delta->getDeltaText();
        }

        return $text;
    }

    /**
     * Gets the reasoning (thought) text delta carried by this chunk.
     *
     * Convenience for single-candidate streaming; see getDeltaText().
     *
     * @since n.e.x.t
     *
     * @return string The reasoning text delta, or an empty string when this chunk carries none.
     */
    public function getReasoningDeltaText(): string
    {
        $text = '';
        foreach ($this->candidateDeltas as $delta) {
            $text .= $delta->getReasoningDeltaText();
        }

        return $text;
    }

    /**
     * Gets the tool call fragments carried by this chunk.
     *
     * @since n.e.x.t
     *
     * @return list<ToolCallDelta> The tool call fragments, possibly empty.
     */
    public function getToolCallDeltas(): array
    {
        $deltas = [];
        foreach ($this->candidateDeltas as $candidateDelta) {
            foreach ($candidateDelta->getToolCallDeltas() as $toolCallDelta) {
                $deltas[] = $toolCallDelta;
            }
        }

        return $deltas;
    }
}

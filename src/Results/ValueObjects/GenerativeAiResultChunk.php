<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\ValueObjects;

use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

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
     * @var int|null Index of the candidate this chunk contributes to, or null when the chunk
     *               carries only result-level metadata (e.g. a usage event).
     */
    private ?int $candidateIndex;

    /**
     * @var MessagePart[] The partial content parts carried by this chunk.
     */
    private array $parts;

    /**
     * @var FinishReasonEnum|null The finish reason, when this chunk reports it.
     */
    private ?FinishReasonEnum $finishReason;

    /**
     * @var TokenUsage|null The token usage, when this chunk reports it.
     */
    private ?TokenUsage $tokenUsage;

    /**
     * @var string|null The result id, when this chunk reports it.
     */
    private ?string $id;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param int|null $candidateIndex Index of the candidate this chunk contributes to.
     * @param MessagePart[] $parts The partial content parts carried by this chunk.
     * @param FinishReasonEnum|null $finishReason The finish reason, when reported.
     * @param TokenUsage|null $tokenUsage The token usage, when reported.
     * @param string|null $id The result id, when reported.
     */
    public function __construct(
        ?int $candidateIndex,
        array $parts = [],
        ?FinishReasonEnum $finishReason = null,
        ?TokenUsage $tokenUsage = null,
        ?string $id = null
    ) {
        $this->candidateIndex = $candidateIndex;
        $this->parts = $parts;
        $this->finishReason = $finishReason;
        $this->tokenUsage = $tokenUsage;
        $this->id = $id;
    }

    /**
     * Gets the index of the candidate this chunk contributes to.
     *
     * @since n.e.x.t
     *
     * @return int|null The candidate index, or null when the chunk carries only result-level metadata.
     */
    public function getCandidateIndex(): ?int
    {
        return $this->candidateIndex;
    }

    /**
     * Gets the partial content parts.
     *
     * @since n.e.x.t
     *
     * @return MessagePart[] The content parts.
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    /**
     * Gets delta text of this chunk's content channel.
     *
     * @since n.e.x.t
     *
     * @return string The content text delta, or an empty string when this chunk carries none.
     */
    public function getDeltaText(): string
    {
        return $this->deltaTextForChannel(MessagePartChannelEnum::content());
    }

    /**
     * Gets delta text of this chunk's reasoning (thought) channel.
     *
     * @since n.e.x.t
     *
     * @return string The reasoning text delta, or an empty string when this chunk carries none.
     */
    public function getReasoningDeltaText(): string
    {
        return $this->deltaTextForChannel(MessagePartChannelEnum::thought());
    }

    /**
     * Concatenates the delta text of this chunk's parts on the given channel.
     *
     * @since n.e.x.t
     *
     * @param MessagePartChannelEnum $channel The channel to read.
     * @return string The concatenated delta text, or an empty string when there is none.
     */
    private function deltaTextForChannel(MessagePartChannelEnum $channel): string
    {
        $text = '';
        foreach ($this->parts as $part) {
            if ($part->getChannel()->is($channel) && $part->getText() !== null) {
                $text .= $part->getText();
            }
        }

        return $text;
    }

    /**
     * Gets the finish reason.
     *
     * @since n.e.x.t
     *
     * @return FinishReasonEnum|null The finish reason, or null when not reported by this chunk.
     */
    public function getFinishReason(): ?FinishReasonEnum
    {
        return $this->finishReason;
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
}

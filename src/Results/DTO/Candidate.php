<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * Represents a candidate response from an AI model.
 *
 * When generating content, AI models can produce multiple candidates.
 * Each candidate contains a message and metadata about why generation stopped.
 *
 * @since n.e.x.t
 */
class Candidate implements WithJsonSchemaInterface
{
    /**
     * @var Message The generated message.
     */
    private Message $message;

    /**
     * @var FinishReasonEnum The reason generation stopped.
     */
    private FinishReasonEnum $finishReason;

    /**
     * @var int The number of tokens in this candidate.
     */
    private int $tokenCount;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param Message $message The generated message.
     * @param FinishReasonEnum $finishReason The reason generation stopped.
     * @param int $tokenCount The number of tokens in this candidate.
     */
    public function __construct(Message $message, FinishReasonEnum $finishReason, int $tokenCount)
    {
        $this->message = $message;
        $this->finishReason = $finishReason;
        $this->tokenCount = $tokenCount;
    }

    /**
     * Gets the generated message.
     *
     * @since n.e.x.t
     *
     * @return Message The message.
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * Gets the finish reason.
     *
     * @since n.e.x.t
     *
     * @return FinishReasonEnum The finish reason.
     */
    public function getFinishReason(): FinishReasonEnum
    {
        return $this->finishReason;
    }

    /**
     * Gets the token count.
     *
     * @since n.e.x.t
     *
     * @return int The token count.
     */
    public function getTokenCount(): int
    {
        return $this->tokenCount;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'message' => Message::getJsonSchema(),
                'finishReason' => [
                    'type' => 'string',
                    'enum' => ['stop', 'length', 'content_filter', 'tool_calls', 'error'],
                    'description' => 'The reason generation stopped.',
                ],
                'tokenCount' => [
                    'type' => 'integer',
                    'description' => 'The number of tokens in this candidate.',
                ],
            ],
            'required' => ['message', 'finishReason', 'tokenCount'],
        ];
    }
}

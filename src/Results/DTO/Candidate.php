<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Common\Contracts\WithJsonSerialization;
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
class Candidate implements WithJsonSchemaInterface, WithJsonSerialization
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
        if (!$message->getRole()->isModel()) {
            throw new \InvalidArgumentException(
                'Message must be a model message.'
            );
        }

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
                    'enum' => FinishReasonEnum::getValues(),
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

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'message' => $this->message->jsonSerialize(),
            'finishReason' => $this->finishReason->value,
            'tokenCount' => $this->tokenCount,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromJson(array $json): Candidate
    {
        /** @var array<string, mixed> $messageData */
        $messageData = $json['message'];

        return new self(
            Message::fromJson($messageData),
            FinishReasonEnum::from((string) $json['finishReason']),
            (int) $json['tokenCount']
        );
    }
}

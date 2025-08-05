<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataValueObject;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * Represents a candidate response from an AI model.
 *
 * When generating content, AI models can produce multiple candidates.
 * Each candidate contains a message and metadata about why generation stopped.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type MessageArrayShape from Message
 *
 * @phpstan-type CandidateArrayShape array{message: MessageArrayShape, finishReason: string}
 *
 * @extends AbstractDataValueObject<CandidateArrayShape>
 */
class Candidate extends AbstractDataValueObject
{
    public const KEY_MESSAGE = 'message';
    public const KEY_FINISH_REASON = 'finishReason';
    /**
     * @var Message The generated message.
     */
    private Message $message;

    /**
     * @var FinishReasonEnum The reason generation stopped.
     */
    private FinishReasonEnum $finishReason;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param Message $message The generated message.
     * @param FinishReasonEnum $finishReason The reason generation stopped.
     */
    public function __construct(Message $message, FinishReasonEnum $finishReason)
    {
        if (!$message->getRole()->isModel()) {
            throw new InvalidArgumentException(
                'Message must be a model message.'
            );
        }

        $this->message = $message;
        $this->finishReason = $finishReason;
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
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                self::KEY_MESSAGE => Message::getJsonSchema(),
                self::KEY_FINISH_REASON => [
                    'type' => 'string',
                    'enum' => FinishReasonEnum::getValues(),
                    'description' => 'The reason generation stopped.',
                ],
            ],
            'required' => [self::KEY_MESSAGE, self::KEY_FINISH_REASON],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return CandidateArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_MESSAGE => $this->message->toArray(),
            self::KEY_FINISH_REASON => $this->finishReason->value,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_MESSAGE, self::KEY_FINISH_REASON]);

        $messageData = $array[self::KEY_MESSAGE];

        return new self(
            Message::fromArray($messageData),
            FinishReasonEnum::from($array[self::KEY_FINISH_REASON]),
        );
    }
}

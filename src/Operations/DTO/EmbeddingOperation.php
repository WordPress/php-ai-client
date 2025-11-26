<?php

declare(strict_types=1);

namespace WordPress\AiClient\Operations\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Operations\Contracts\OperationInterface;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;
use WordPress\AiClient\Results\DTO\EmbeddingResult;

/**
 * Represents a long-running embedding generation operation.
 *
 * @since 0.2.0
 *
 * @phpstan-import-type EmbeddingResultArrayShape from EmbeddingResult
 *
 * @phpstan-type EmbeddingOperationArrayShape array{id: string, state: string, result?: EmbeddingResultArrayShape}
 *
 * @extends AbstractDataTransferObject<EmbeddingOperationArrayShape>
 */
class EmbeddingOperation extends AbstractDataTransferObject implements OperationInterface
{
    public const KEY_ID = 'id';
    public const KEY_STATE = 'state';
    public const KEY_RESULT = 'result';

    /**
     * @var string Unique identifier for this operation.
     */
    private string $id;

    /**
     * @var OperationStateEnum The current state of the operation.
     */
    private OperationStateEnum $state;

    /**
     * @var EmbeddingResult|null The result once the operation completes.
     */
    private ?EmbeddingResult $result;

    /**
     * Constructor.
     *
     * @since 0.2.0
     *
     * @param string $id Unique identifier for this operation.
     * @param OperationStateEnum $state The current state of the operation.
     * @param EmbeddingResult|null $result The result once the operation completes.
     */
    public function __construct(string $id, OperationStateEnum $state, ?EmbeddingResult $result = null)
    {
        $this->id = $id;
        $this->state = $state;
        $this->result = $result;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    public function getState(): OperationStateEnum
    {
        return $this->state;
    }

    /**
     * Gets the operation result.
     *
     * @since 0.2.0
     *
     * @return EmbeddingResult|null The embedding result or null if not yet complete.
     */
    public function getResult(): ?EmbeddingResult
    {
        return $this->result;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    public static function getJsonSchema(): array
    {
        return [
            'oneOf' => [
                [
                    'type' => 'object',
                    'properties' => [
                        self::KEY_ID => [
                            'type' => 'string',
                            'description' => 'Unique identifier for this operation.',
                        ],
                        self::KEY_STATE => [
                            'type' => 'string',
                            'const' => OperationStateEnum::succeeded()->value,
                        ],
                        self::KEY_RESULT => EmbeddingResult::getJsonSchema(),
                    ],
                    'required' => [self::KEY_ID, self::KEY_STATE, self::KEY_RESULT],
                    'additionalProperties' => false,
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        self::KEY_ID => [
                            'type' => 'string',
                            'description' => 'Unique identifier for this operation.',
                        ],
                        self::KEY_STATE => [
                            'type' => 'string',
                            'enum' => [
                                OperationStateEnum::starting()->value,
                                OperationStateEnum::processing()->value,
                                OperationStateEnum::failed()->value,
                                OperationStateEnum::canceled()->value,
                            ],
                            'description' => 'The current state of the operation.',
                        ],
                    ],
                    'required' => [self::KEY_ID, self::KEY_STATE],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     *
     * @return EmbeddingOperationArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_ID => $this->id,
            self::KEY_STATE => $this->state->value,
        ];

        if ($this->result !== null) {
            $data[self::KEY_RESULT] = $this->result->toArray();
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_ID, self::KEY_STATE]);

        $state = OperationStateEnum::from($array[self::KEY_STATE]);

        if ($state->isSucceeded()) {
            static::validateFromArrayData($array, [self::KEY_RESULT]);
        }

        $result = null;
        if (isset($array[self::KEY_RESULT])) {
            $result = EmbeddingResult::fromArray($array[self::KEY_RESULT]);
        }

        return new self($array[self::KEY_ID], $state, $result);
    }
}

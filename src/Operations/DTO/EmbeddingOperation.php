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
 * This DTO tracks the progress of embedding generation tasks that may not complete
 * immediately, providing access to the result once available.
 *
 * @since n.e.x.t
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
     * @since n.e.x.t
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
     * @since n.e.x.t
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getState(): OperationStateEnum
    {
        return $this->state;
    }

    /**
     * Gets the embedding operation result.
     *
     * @since n.e.x.t
     *
     * @return EmbeddingResult|null The result once the operation completes.
     */
    public function getResult(): ?EmbeddingResult
    {
        return $this->result;
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
                self::KEY_ID => [
                    'type' => 'string',
                    'description' => 'Unique identifier for this operation.',
                ],
                self::KEY_STATE => [
                    'type' => 'string',
                    'enum' => OperationStateEnum::getAllValues(),
                    'description' => 'The current state of the operation.',
                ],
                self::KEY_RESULT => [
                    '$ref' => '#/definitions/EmbeddingResult',
                    'description' => 'The result once the operation completes.',
                ],
            ],
            'required' => [self::KEY_ID, self::KEY_STATE],
            'definitions' => [
                'EmbeddingResult' => EmbeddingResult::getJsonSchema(),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return EmbeddingOperationArrayShape
     */
    public function toArray(): array
    {
        $array = [
            self::KEY_ID => $this->id,
            self::KEY_STATE => $this->state->getValue(),
        ];

        if ($this->result !== null) {
            $array[self::KEY_RESULT] = $this->result->toArray();
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [
            self::KEY_ID,
            self::KEY_STATE,
        ]);

        $result = null;
        if (isset($array[self::KEY_RESULT])) {
            $result = EmbeddingResult::fromArray($array[self::KEY_RESULT]);
        }

        return new self(
            $array[self::KEY_ID],
            OperationStateEnum::fromValue($array[self::KEY_STATE]),
            $result
        );
    }
}
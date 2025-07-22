<?php

declare(strict_types=1);

namespace WordPress\AiClient\Operations\DTO;

use WordPress\AiClient\Operations\Contracts\OperationInterface;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;
use WordPress\AiClient\Results\DTO\EmbeddingResult;

/**
 * Represents a long-running embedding generation operation
 *
 * This DTO tracks the progress of embedding generation tasks that may not complete
 * immediately, providing access to the result once available.
 *
 * @since n.e.x.t
 */
class EmbeddingOperation implements OperationInterface
{
    /**
     * @var string Unique identifier for this operation
     */
    private string $id;

    /**
     * @var OperationStateEnum The current state of the operation
     */
    private OperationStateEnum $state;

    /**
     * @var EmbeddingResult|null The result once the operation completes
     */
    private ?EmbeddingResult $result;

    /**
     * Constructor
     *
     * @since n.e.x.t
     * @param string $id Unique identifier for this operation
     * @param OperationStateEnum $state The current state of the operation
     * @param EmbeddingResult|null $result The result once the operation completes
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
     * Get the operation result
     *
     * @since n.e.x.t
     * @return EmbeddingResult|null The result or null if not yet complete
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
                'id' => [
                    'type' => 'string',
                    'description' => 'Unique identifier for this operation',
                ],
                'state' => [
                    'type' => 'string',
                    'enum' => ['starting', 'processing', 'succeeded', 'failed', 'canceled'],
                    'description' => 'The current state of the operation',
                ],
                'result' => [
                    'oneOf' => [
                        ['type' => 'null'],
                        EmbeddingResult::getJsonSchema(),
                    ],
                    'description' => 'The result once the operation completes',
                ],
            ],
            'required' => ['id', 'state'],
        ];
    }
}

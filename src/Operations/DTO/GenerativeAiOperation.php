<?php

declare(strict_types=1);

namespace WordPress\AiClient\Operations\DTO;

use WordPress\AiClient\Operations\Contracts\OperationInterface;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Represents a long-running generative AI operation
 *
 * This DTO tracks the progress of generative AI tasks that may not complete
 * immediately, providing access to the result once available.
 *
 * @since n.e.x.t
 */
class GenerativeAiOperation implements OperationInterface
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
     * @var GenerativeAiResult|null The result once the operation completes
     */
    private ?GenerativeAiResult $result;

    /**
     * Constructor
     *
     * @since n.e.x.t
     * @param string $id Unique identifier for this operation
     * @param OperationStateEnum $state The current state of the operation
     * @param GenerativeAiResult|null $result The result once the operation completes
     */
    public function __construct(string $id, OperationStateEnum $state, ?GenerativeAiResult $result = null)
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
     * @return GenerativeAiResult|null The result or null if not yet complete
     */
    public function getResult(): ?GenerativeAiResult
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
                        GenerativeAiResult::getJsonSchema(),
                    ],
                    'description' => 'The result once the operation completes',
                ],
            ],
            'required' => ['id', 'state'],
        ];
    }
}

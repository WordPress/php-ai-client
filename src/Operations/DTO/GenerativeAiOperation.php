<?php

declare(strict_types=1);

namespace WordPress\AiClient\Operations\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataValueObject;
use WordPress\AiClient\Operations\Contracts\OperationInterface;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Represents a long-running generative AI operation.
 *
 * This DTO tracks the progress of generative AI tasks that may not complete
 * immediately, providing access to the result once available.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type GenerativeAiResultArrayShape from GenerativeAiResult
 *
 * @phpstan-type GenerativeAiOperationArrayShape array{id: string, state: string, result?: GenerativeAiResultArrayShape}
 *
 * @extends AbstractDataValueObject<GenerativeAiOperationArrayShape>
 */
final class GenerativeAiOperation extends AbstractDataValueObject implements OperationInterface
{
    /**
     * @var string Unique identifier for this operation.
     */
    private string $id;

    /**
     * @var OperationStateEnum The current state of the operation.
     */
    private OperationStateEnum $state;

    /**
     * @var GenerativeAiResult|null The result once the operation completes.
     */
    private ?GenerativeAiResult $result;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $id Unique identifier for this operation.
     * @param OperationStateEnum $state The current state of the operation.
     * @param GenerativeAiResult|null $result The result once the operation completes.
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
     * Gets the operation result.
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiResult|null The result or null if not yet complete.
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
            'oneOf' => [
                // Succeeded state - has result
                [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'string',
                            'description' => 'Unique identifier for this operation.',
                        ],
                        'state' => [
                            'type' => 'string',
                            'const' => OperationStateEnum::succeeded()->value,
                        ],
                        'result' => GenerativeAiResult::getJsonSchema(),
                    ],
                    'required' => ['id', 'state', 'result'],
                    'additionalProperties' => false,
                ],
                // All other states - no result
                [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'string',
                            'description' => 'Unique identifier for this operation.',
                        ],
                        'state' => [
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
                    'required' => ['id', 'state'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiOperationArrayShape
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'state' => $this->state->value,
        ];

        if ($this->result !== null) {
            $data['result'] = $this->result->toArray();
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): GenerativeAiOperation
    {
        static::validateFromArrayData($array, ['id', 'state']);

        $state = OperationStateEnum::from($array['state']);
        $result = null;
        if (isset($array['result'])) {
            $result = GenerativeAiResult::fromArray($array['result']);
        }

        return new self($array['id'], $state, $result);
    }
}

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
class GenerativeAiOperation extends AbstractDataValueObject implements OperationInterface
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
                        self::KEY_ID => [
                            'type' => 'string',
                            'description' => 'Unique identifier for this operation.',
                        ],
                        self::KEY_STATE => [
                            'type' => 'string',
                            'const' => OperationStateEnum::succeeded()->value,
                        ],
                        self::KEY_RESULT => GenerativeAiResult::getJsonSchema(),
                    ],
                    'required' => [self::KEY_ID, self::KEY_STATE, self::KEY_RESULT],
                    'additionalProperties' => false,
                ],
                // All other states - no result
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
     * @since n.e.x.t
     *
     * @return GenerativeAiOperationArrayShape
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
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_ID, self::KEY_STATE]);

        $state = OperationStateEnum::from($array[self::KEY_STATE]);
        $result = null;
        if (isset($array[self::KEY_RESULT])) {
            $result = GenerativeAiResult::fromArray($array[self::KEY_RESULT]);
        }

        return new self($array[self::KEY_ID], $state, $result);
    }
}

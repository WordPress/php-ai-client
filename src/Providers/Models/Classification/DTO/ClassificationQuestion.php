<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Classification\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\Models\Classification\Enums\ClassificationQuestionTypeEnum;

/**
 * Represents a typed question for a classification model to answer about the given state.
 *
 * A binary question asks whether a statement is true, and takes no criteria. A choice question asks the
 * model to pick one of several named options, given as criteria that map each option key to a description
 * of when it applies. A score question asks the model to place the state on a scale, given as criteria that
 * list a description of each level from lowest to highest.
 *
 * @since n.e.x.t
 *
 * @phpstan-type ClassificationQuestionArrayShape array{
 *     type: string,
 *     instructions: string,
 *     criteria?: array<string, string>|list<string>
 * }
 *
 * @extends AbstractDataTransferObject<ClassificationQuestionArrayShape>
 */
class ClassificationQuestion extends AbstractDataTransferObject
{
    public const KEY_TYPE = 'type';
    public const KEY_INSTRUCTIONS = 'instructions';
    public const KEY_CRITERIA = 'criteria';

    /**
     * @var ClassificationQuestionTypeEnum The question type.
     */
    private ClassificationQuestionTypeEnum $type;

    /**
     * @var string The question to answer.
     */
    private string $instructions;

    /**
     * @var array<string, string>|list<string> The option descriptions keyed by option key, or the level
     *      descriptions from lowest to highest.
     */
    private array $criteria;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param ClassificationQuestionTypeEnum $type The question type.
     * @param string $instructions The question to answer.
     * @param array<string, string>|list<string> $criteria For a choice question, the option descriptions keyed
     *                                                     by option key. For a score question, the level
     *                                                     descriptions from lowest to highest. Empty for a
     *                                                     binary question.
     *
     * @throws InvalidArgumentException If the instructions are empty or the criteria do not suit the type.
     */
    public function __construct(ClassificationQuestionTypeEnum $type, string $instructions, array $criteria = [])
    {
        if (trim($instructions) === '') {
            throw new InvalidArgumentException('Classification question instructions cannot be empty.');
        }

        if ($type->isBinary()) {
            if ($criteria !== []) {
                throw new InvalidArgumentException('Binary classification questions do not accept criteria.');
            }
        } elseif (count($criteria) < 2) {
            throw new InvalidArgumentException(
                sprintf('Classification questions of type "%s" require at least two criteria.', $type->value)
            );
        }

        if ($type->isChoice()) {
            foreach (array_keys($criteria) as $optionKey) {
                // PHP converts integer-like string keys to integers, so those are rejected along with lists.
                if (!is_string($optionKey) || trim($optionKey) === '') {
                    throw new InvalidArgumentException(
                        'Choice classification question option keys must be non-empty, non-integer strings.'
                    );
                }
            }
        }

        if ($type->isScore() && !array_is_list($criteria)) {
            throw new InvalidArgumentException(
                'Score classification question criteria must be a list of level descriptions, '
                . 'from lowest to highest.'
            );
        }

        foreach ($criteria as $description) {
            if (!is_string($description) || trim($description) === '') {
                throw new InvalidArgumentException(
                    'Classification question criteria descriptions must be non-empty strings.'
                );
            }
        }

        $this->type = $type;
        $this->instructions = $instructions;
        $this->criteria = $criteria;
    }

    /**
     * Gets the question type.
     *
     * @since n.e.x.t
     *
     * @return ClassificationQuestionTypeEnum The question type.
     */
    public function getType(): ClassificationQuestionTypeEnum
    {
        return $this->type;
    }

    /**
     * Gets the question to answer.
     *
     * @since n.e.x.t
     *
     * @return string The question instructions.
     */
    public function getInstructions(): string
    {
        return $this->instructions;
    }

    /**
     * Gets the criteria to answer the question with.
     *
     * @since n.e.x.t
     *
     * @return array<string, string>|list<string> The option descriptions keyed by option key for a choice
     *                                            question, the level descriptions from lowest to highest for
     *                                            a score question, or an empty array for a binary question.
     */
    public function getCriteria(): array
    {
        return $this->criteria;
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
                self::KEY_TYPE => [
                    'type' => 'string',
                    'enum' => ClassificationQuestionTypeEnum::getValues(),
                    'description' => 'The question type.',
                ],
                self::KEY_INSTRUCTIONS => [
                    'type' => 'string',
                    'description' => 'The question to answer.',
                ],
                self::KEY_CRITERIA => [
                    'oneOf' => [
                        [
                            'type' => 'object',
                            'additionalProperties' => [
                                'type' => 'string',
                            ],
                            'minProperties' => 2,
                        ],
                        [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                            'minItems' => 2,
                        ],
                    ],
                    'description' => 'The option descriptions keyed by option key for a choice question, or the '
                        . 'level descriptions from lowest to highest for a score question.',
                ],
            ],
            'required' => [self::KEY_TYPE, self::KEY_INSTRUCTIONS],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return ClassificationQuestionArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_TYPE => $this->type->value,
            self::KEY_INSTRUCTIONS => $this->instructions,
        ];

        if ($this->criteria !== []) {
            $data[self::KEY_CRITERIA] = $this->criteria;
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
        static::validateFromArrayData($array, [self::KEY_TYPE, self::KEY_INSTRUCTIONS]);

        return new self(
            ClassificationQuestionTypeEnum::from($array[self::KEY_TYPE]),
            $array[self::KEY_INSTRUCTIONS],
            $array[self::KEY_CRITERIA] ?? []
        );
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Decision\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\Models\Decision\Enums\DecisionQuestionTypeEnum;

/**
 * Represents a typed question for a decision model to answer about the given state.
 *
 * A binary question asks whether a statement is true, and takes no criteria. A choice question asks the
 * model to pick one of several options, each identified by a value and optionally described. A score
 * question asks the model to place the state on a scale, given as a description of each level from lowest
 * to highest.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type DecisionOptionArrayShape from DecisionOption
 *
 * @phpstan-type DecisionQuestionArrayShape array{
 *     type: string,
 *     instructions: string,
 *     options?: list<DecisionOptionArrayShape>,
 *     levels?: list<string>
 * }
 *
 * @extends AbstractDataTransferObject<DecisionQuestionArrayShape>
 */
class DecisionQuestion extends AbstractDataTransferObject
{
    public const KEY_TYPE = 'type';
    public const KEY_INSTRUCTIONS = 'instructions';
    public const KEY_OPTIONS = 'options';
    public const KEY_LEVELS = 'levels';

    /**
     * @var DecisionQuestionTypeEnum The question type.
     */
    private DecisionQuestionTypeEnum $type;

    /**
     * @var string The question to answer.
     */
    private string $instructions;

    /**
     * @var list<DecisionOption> The options of a choice question.
     */
    private array $options = [];

    /**
     * @var list<string> The level descriptions of a score question, from lowest to highest.
     */
    private array $levels = [];

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param DecisionQuestionTypeEnum $type The question type.
     * @param string $instructions The question to answer.
     * @param array<string|DecisionOption> $criteria For a choice question, a list of the options, each given
     *                                               as its value or as a DecisionOption with a description.
     *                                               For a score question, a list of the level descriptions
     *                                               from lowest to highest. Empty for a binary question.
     *
     * @throws InvalidArgumentException If the instructions are empty or the criteria do not suit the type.
     */
    public function __construct(DecisionQuestionTypeEnum $type, string $instructions, array $criteria = [])
    {
        if (trim($instructions) === '') {
            throw new InvalidArgumentException('Decision question instructions cannot be empty.');
        }

        if ($type->isBinary()) {
            if ($criteria !== []) {
                throw new InvalidArgumentException('Binary decision questions do not accept criteria.');
            }
        } elseif (count($criteria) < 2 || !array_is_list($criteria)) {
            throw new InvalidArgumentException(
                sprintf('Decision questions of type "%s" require a list of at least two criteria.', $type->value)
            );
        }

        $this->type = $type;
        $this->instructions = $instructions;

        if ($type->isChoice()) {
            $this->options = self::normalizeOptions($criteria);
        } elseif ($type->isScore()) {
            $this->levels = self::validateLevels($criteria);
        }
    }

    /**
     * Gets the question type.
     *
     * @since n.e.x.t
     *
     * @return DecisionQuestionTypeEnum The question type.
     */
    public function getType(): DecisionQuestionTypeEnum
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
     * Gets the options of a choice question.
     *
     * @since n.e.x.t
     *
     * @return list<DecisionOption> The options, or an empty list if this is not a choice question.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Gets the levels of a score question.
     *
     * @since n.e.x.t
     *
     * @return list<string> The level descriptions from lowest to highest, or an empty list if this is not a
     *                      score question.
     */
    public function getLevels(): array
    {
        return $this->levels;
    }

    /**
     * Converts the criteria of a choice question to options.
     *
     * @since n.e.x.t
     *
     * @param list<mixed> $criteria The criteria, each an option value or a DecisionOption.
     * @return list<DecisionOption> The options.
     * @throws InvalidArgumentException If a criterion is not an option, or two options share a value.
     */
    private static function normalizeOptions(array $criteria): array
    {
        $options = [];
        $values = [];

        foreach ($criteria as $criterion) {
            if (is_string($criterion)) {
                $criterion = new DecisionOption($criterion);
            } elseif (!$criterion instanceof DecisionOption) {
                throw new InvalidArgumentException(
                    'Choice decision question options must be option values or DecisionOption instances.'
                );
            }

            if (isset($values[$criterion->getValue()])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Choice decision question options must be unique, but "%s" is given more than once.',
                        $criterion->getValue()
                    )
                );
            }

            $values[$criterion->getValue()] = true;
            $options[] = $criterion;
        }

        return $options;
    }

    /**
     * Validates the criteria of a score question.
     *
     * @since n.e.x.t
     *
     * @param list<mixed> $criteria The criteria, each a level description.
     * @return list<string> The level descriptions.
     * @throws InvalidArgumentException If a level description is not a non-empty string.
     */
    private static function validateLevels(array $criteria): array
    {
        $levels = [];

        foreach ($criteria as $criterion) {
            if (!is_string($criterion) || trim($criterion) === '') {
                throw new InvalidArgumentException(
                    'Score decision question levels must be non-empty descriptions.'
                );
            }

            $levels[] = $criterion;
        }

        return $levels;
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
                    'enum' => DecisionQuestionTypeEnum::getValues(),
                    'description' => 'The question type.',
                ],
                self::KEY_INSTRUCTIONS => [
                    'type' => 'string',
                    'description' => 'The question to answer.',
                ],
                self::KEY_OPTIONS => [
                    'type' => 'array',
                    'items' => DecisionOption::getJsonSchema(),
                    'minItems' => 2,
                    'description' => 'The options of a choice question.',
                ],
                self::KEY_LEVELS => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'minItems' => 2,
                    'description' => 'The level descriptions of a score question, from lowest to highest.',
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
     * @return DecisionQuestionArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_TYPE => $this->type->value,
            self::KEY_INSTRUCTIONS => $this->instructions,
        ];

        if ($this->options !== []) {
            $data[self::KEY_OPTIONS] = array_map(
                static function (DecisionOption $option): array {
                    return $option->toArray();
                },
                $this->options
            );
        }

        if ($this->levels !== []) {
            $data[self::KEY_LEVELS] = $this->levels;
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

        $criteria = $array[self::KEY_LEVELS] ?? [];
        if (isset($array[self::KEY_OPTIONS])) {
            $criteria = array_map([DecisionOption::class, 'fromArray'], $array[self::KEY_OPTIONS]);
        }

        return new self(
            DecisionQuestionTypeEnum::from($array[self::KEY_TYPE]),
            $array[self::KEY_INSTRUCTIONS],
            $criteria
        );
    }
}

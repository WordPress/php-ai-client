<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Decision\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\Models\Decision\Enums\DecisionQuestionTypeEnum;
use WordPress\AiClient\Providers\Models\Decision\Util\DescriptionUtil;

/**
 * Represents a typed question for a decision model to answer about the given state.
 *
 * A binary question asks whether a statement is true, and may describe what counts as true and what counts
 * as false. A choice question asks the model to pick one of several options, each identified by a value and
 * optionally described. A score question asks the model to place the state on a scale, given as a
 * description of each level from lowest to highest.
 *
 * Each description is a sentence, or an array for when structure makes it clearer, see
 * {@see DescriptionUtil}.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type DecisionOptionArrayShape from DecisionOption
 *
 * @phpstan-type DecisionQuestionArrayShape array{
 *     type: string,
 *     instructions: string,
 *     trueDescription?: string|array<mixed>,
 *     falseDescription?: string|array<mixed>,
 *     options?: list<DecisionOptionArrayShape>,
 *     levels?: list<string|array<mixed>>
 * }
 *
 * @extends AbstractDataTransferObject<DecisionQuestionArrayShape>
 */
class DecisionQuestion extends AbstractDataTransferObject
{
    public const KEY_TYPE = 'type';
    public const KEY_INSTRUCTIONS = 'instructions';
    public const KEY_TRUE_DESCRIPTION = 'trueDescription';
    public const KEY_FALSE_DESCRIPTION = 'falseDescription';
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
     * @var string|array<mixed>|null The description of what counts as true for a binary question, if any.
     */
    private $trueDescription;

    /**
     * @var string|array<mixed>|null The description of what counts as false for a binary question, if any.
     */
    private $falseDescription;

    /**
     * @var list<DecisionOption> The options of a choice question.
     */
    private array $options = [];

    /**
     * @var list<string|array<mixed>> The level descriptions of a score question, from lowest to highest.
     */
    private array $levels = [];

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param DecisionQuestionTypeEnum $type The question type.
     * @param string $instructions The question to answer.
     * @param array<mixed> $criteria For a binary question, optional descriptions of what counts
     *                                               as true and as false, keyed by "true" and "false". For a
     *                                               choice question, a list of the options, each given as its
     *                                               value or as a DecisionOption with a description. For a
     *                                               score question, a list of the level descriptions from
     *                                               lowest to highest.
     *
     * @throws InvalidArgumentException If the instructions are empty or the criteria do not suit the type.
     */
    public function __construct(DecisionQuestionTypeEnum $type, string $instructions, array $criteria = [])
    {
        if (trim($instructions) === '') {
            throw new InvalidArgumentException('Decision question instructions cannot be empty.');
        }

        if (!$type->isBinary() && (count($criteria) < 2 || !array_is_list($criteria))) {
            throw new InvalidArgumentException(
                sprintf('Decision questions of type "%s" require a list of at least two criteria.', $type->value)
            );
        }

        $this->type = $type;
        $this->instructions = $instructions;

        if ($type->isBinary()) {
            $this->validateBinaryCriteria($criteria);
        } elseif ($type->isChoice()) {
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
     * Gets the description of what counts as true for a binary question.
     *
     * @since n.e.x.t
     *
     * @return string|array<mixed>|null The description, or null if there is none or this is not a binary
     *                                  question.
     */
    public function getTrueDescription()
    {
        return $this->trueDescription;
    }

    /**
     * Gets the description of what counts as false for a binary question.
     *
     * @since n.e.x.t
     *
     * @return string|array<mixed>|null The description, or null if there is none or this is not a binary
     *                                  question.
     */
    public function getFalseDescription()
    {
        return $this->falseDescription;
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
     * @return list<string|array<mixed>> The level descriptions from lowest to highest, or an empty list if
     *                                    this is not a score question.
     */
    public function getLevels(): array
    {
        return $this->levels;
    }

    /**
     * Validates and stores the criteria of a binary question.
     *
     * @since n.e.x.t
     *
     * @param array<mixed> $criteria The criteria, with optional descriptions keyed by "true" and "false".
     * @return void
     * @throws InvalidArgumentException If a key is not "true" or "false", or a description is invalid.
     */
    private function validateBinaryCriteria(array $criteria): void
    {
        foreach ($criteria as $key => $description) {
            if ($key !== 'true' && $key !== 'false') {
                throw new InvalidArgumentException(
                    'Binary decision question criteria may only describe what counts as "true" and as "false".'
                );
            }

            $description = DescriptionUtil::validate(
                $description,
                sprintf('The description of what counts as %s', $key)
            );

            if ($key === 'true') {
                $this->trueDescription = $description;
            } else {
                $this->falseDescription = $description;
            }
        }
    }

    /**
     * Converts the criteria of a choice question to options.
     *
     * @since n.e.x.t
     *
     * @param array<mixed> $criteria The criteria, each an option value or a DecisionOption.
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
     * @param array<mixed> $criteria The criteria, each a level description.
     * @return list<string|array<mixed>> The level descriptions.
     * @throws InvalidArgumentException If a level description is invalid.
     */
    private static function validateLevels(array $criteria): array
    {
        $levels = [];

        foreach ($criteria as $index => $criterion) {
            $levels[] = DescriptionUtil::validate($criterion, sprintf('Score level %d', $index));
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
                self::KEY_TRUE_DESCRIPTION => [
                    'type' => ['string', 'object', 'array'],
                    'description' => 'The description of what counts as true for a binary question.',
                ],
                self::KEY_FALSE_DESCRIPTION => [
                    'type' => ['string', 'object', 'array'],
                    'description' => 'The description of what counts as false for a binary question.',
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
                        'type' => ['string', 'object', 'array'],
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

        if ($this->trueDescription !== null) {
            $data[self::KEY_TRUE_DESCRIPTION] = $this->trueDescription;
        }

        if ($this->falseDescription !== null) {
            $data[self::KEY_FALSE_DESCRIPTION] = $this->falseDescription;
        }

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
        if (isset($array[self::KEY_TRUE_DESCRIPTION])) {
            $criteria['true'] = $array[self::KEY_TRUE_DESCRIPTION];
        }
        if (isset($array[self::KEY_FALSE_DESCRIPTION])) {
            $criteria['false'] = $array[self::KEY_FALSE_DESCRIPTION];
        }

        return new self(
            DecisionQuestionTypeEnum::from($array[self::KEY_TYPE]),
            $array[self::KEY_INSTRUCTIONS],
            $criteria
        );
    }
}

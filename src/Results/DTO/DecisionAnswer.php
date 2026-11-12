<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Decisions\Enums\DecisionQuestionTypeEnum;

/**
 * Represents a decision model's answer to a single question.
 *
 * An answer has the type of the question it answers, which determines its value:
 *
 * - binary: the probability, from 0 to 1, that the statement is true. See {@see self::getProbability()}.
 * - choice: the value of the chosen option. See {@see self::getChoice()}.
 * - score: the position on the scale, from 0 for the lowest level to one less than the number of levels.
 *   It may fall between levels, for example when a model reports a probability-weighted position. See
 *   {@see self::getScore()}.
 *
 * Models may also report how confident they are in the answer and, for choice and score questions, the
 * probability of each option or level.
 *
 * A model may also refuse to answer a question, while still answering the others. A refusal has the type of
 * the question but no value, so {@see self::isRefusal()} must be checked before reading the value.
 *
 * @since n.e.x.t
 *
 * @phpstan-type DecisionAnswerArrayShape array{
 *     type: string,
 *     value: float|string|null,
 *     confidence?: float,
 *     probabilities?: list<array{value: string, probability: float}>|list<float>
 * }
 *
 * @extends AbstractDataTransferObject<DecisionAnswerArrayShape>
 */
class DecisionAnswer extends AbstractDataTransferObject
{
    public const KEY_TYPE = 'type';
    public const KEY_VALUE = 'value';
    public const KEY_CONFIDENCE = 'confidence';
    public const KEY_PROBABILITIES = 'probabilities';
    public const KEY_PROBABILITY = 'probability';

    /**
     * How far a number may fall outside its range before it is rejected.
     *
     * Scores and probabilities are computed with floating-point arithmetic, so a valid probability can come
     * out as 1.0000000000000002. Numbers within this tolerance of their range are accepted and clamped into
     * it. Scores below zero are clamped here, and scores above the top level by the decision builder, which
     * knows the scale of the question.
     *
     * @since n.e.x.t
     *
     * @var float
     */
    public const TOLERANCE = 1.0E-6;

    /**
     * @var DecisionQuestionTypeEnum The type of the question answered.
     */
    private DecisionQuestionTypeEnum $type;

    /**
     * @var float|string|null The probability, the chosen option value, the position on the scale, or null if
     *      the model refused to answer.
     */
    private $value;

    /**
     * @var float|null The model's confidence in the answer, if reported.
     */
    private ?float $confidence;

    /**
     * @var array<float> The probability of each option keyed by option value, or of each level from lowest to
     *      highest, if reported.
     */
    private array $probabilities;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param DecisionQuestionTypeEnum $type The type of the question answered.
     * @param float|string|null $value The probability for a binary question, the chosen option value for a
     *                                 choice question, the position on the scale for a score question, or
     *                                 null if the model refused to answer.
     * @param float|null $confidence The model's confidence in the answer, if reported.
     * @param array<float> $probabilities For a choice question, the probability of each option keyed by option
     *                                    value. For a score question, the probability of each level from lowest
     *                                    to highest. Empty if not reported, and always empty for a binary
     *                                    question or a refusal.
     *
     * @throws InvalidArgumentException If the value, confidence, or probabilities do not suit the type, or a
     *                                  confidence or probability is not between 0 and 1.
     */
    public function __construct(
        DecisionQuestionTypeEnum $type,
        $value,
        ?float $confidence = null,
        array $probabilities = []
    ) {
        if ($value === null) {
            if ($confidence !== null || $probabilities !== []) {
                throw new InvalidArgumentException(
                    'Decision answers that refuse to answer do not accept a confidence or probabilities.'
                );
            }
        } elseif ($type->isBinary()) {
            if ($confidence !== null) {
                throw new InvalidArgumentException(
                    'Decision answers to binary questions do not accept a confidence, '
                    . 'as their value is the probability.'
                );
            }

            $value = self::validateProbability($value, 'Decision answer probability');
        } elseif ($type->isChoice()) {
            if (!is_string($value) || trim($value) === '') {
                throw new InvalidArgumentException('Decision answer choice must be a non-empty option value.');
            }
        } else {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value < -self::TOLERANCE) {
                throw new InvalidArgumentException('Decision answer score must be a non-negative number.');
            }

            $value = max(0.0, (float) $value);
        }

        if ($confidence !== null) {
            $confidence = self::validateProbability($confidence, 'Decision answer confidence');
        }

        if ($probabilities !== []) {
            if ($type->isBinary()) {
                throw new InvalidArgumentException(
                    'Decision answers to binary questions do not accept probabilities, '
                    . 'as their value is the probability.'
                );
            }

            if ($type->isScore() && !array_is_list($probabilities)) {
                throw new InvalidArgumentException(
                    'Decision answer probabilities for a score question must be a list, '
                    . 'from the lowest level to the highest.'
                );
            }

            foreach ($probabilities as $key => $probability) {
                // PHP converts integer-like option values such as "5" to integer keys, so those are allowed.
                if ($type->isChoice() && is_string($key) && trim($key) === '') {
                    throw new InvalidArgumentException(
                        'Decision answer probabilities for a choice question must be keyed by option value.'
                    );
                }

                $probabilities[$key] = self::validateProbability(
                    $probability,
                    'Decision answer probabilities'
                );
            }
        }

        $this->type = $type;
        $this->value = $value;
        $this->confidence = $confidence;
        $this->probabilities = $probabilities;
    }

    /**
     * Gets the type of the question answered.
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
     * Checks whether the model refused to answer the question.
     *
     * A refusal has no value, so the typed getters throw for it.
     *
     * @since n.e.x.t
     *
     * @return bool True if the model refused to answer.
     */
    public function isRefusal(): bool
    {
        return $this->value === null;
    }

    /**
     * Gets the probability that the statement of a binary question is true.
     *
     * @since n.e.x.t
     *
     * @return float The probability, from 0 to 1.
     * @throws RuntimeException If this is not an answer to a binary question, or is a refusal.
     */
    public function getProbability(): float
    {
        $this->assertType(DecisionQuestionTypeEnum::binary());

        return (float) $this->value;
    }

    /**
     * Gets the chosen option of a choice question.
     *
     * @since n.e.x.t
     *
     * @return string The option value.
     * @throws RuntimeException If this is not an answer to a choice question, or is a refusal.
     */
    public function getChoice(): string
    {
        $this->assertType(DecisionQuestionTypeEnum::choice());

        return (string) $this->value;
    }

    /**
     * Gets the position on the scale of a score question.
     *
     * @since n.e.x.t
     *
     * @return float The position, from 0 for the lowest level to one less than the number of levels.
     * @throws RuntimeException If this is not an answer to a score question, or is a refusal.
     */
    public function getScore(): float
    {
        $this->assertType(DecisionQuestionTypeEnum::score());

        return (float) $this->value;
    }

    /**
     * Gets the model's confidence in the answer.
     *
     * Confidence measures how concentrated the probabilities of a choice or score answer are, and each provider
     * computes it in its own way, so it is not comparable across providers or a measure of correctness.
     *
     * @since n.e.x.t
     *
     * @return float|null The confidence between 0 and 1, or null if not reported. Always null for a binary
     *                    question, whose value is the probability, and for a refusal.
     */
    public function getConfidence(): ?float
    {
        return $this->confidence;
    }

    /**
     * Gets the probability of each option or level.
     *
     * @since n.e.x.t
     *
     * @return array<float> The probabilities keyed by option value for a choice question, or listed from the
     *                      lowest level to the highest for a score question. As with any PHP array, an option
     *                      value such as "5" becomes an integer key. Empty if not reported, and always empty
     *                      for a binary question or a refusal.
     */
    public function getProbabilities(): array
    {
        return $this->probabilities;
    }

    /**
     * Asserts that this is an answer to a question of the given type, and not a refusal.
     *
     * @since n.e.x.t
     *
     * @param DecisionQuestionTypeEnum $type The expected question type.
     * @return void
     * @throws RuntimeException If this is an answer to a question of another type, or is a refusal.
     */
    private function assertType(DecisionQuestionTypeEnum $type): void
    {
        if ($this->isRefusal()) {
            throw new RuntimeException('The model refused to answer this question, so the answer has no value.');
        }

        if (!$this->type->is($type)) {
            throw new RuntimeException(
                sprintf(
                    'This is an answer to a %s question, not a %s question.',
                    $this->type->value,
                    $type->value
                )
            );
        }
    }

    /**
     * Validates that a value is a number between 0 and 1.
     *
     * @since n.e.x.t
     *
     * @param mixed $value The value to validate.
     * @param string $label The label to use in the error message.
     * @return float The validated value, clamped between 0 and 1.
     * @throws InvalidArgumentException If the value is not a number between 0 and 1, within the tolerance.
     */
    private static function validateProbability($value, string $label): float
    {
        // NaN fails no comparison, so it is excluded explicitly.
        if (
            (!is_int($value) && !is_float($value))
            || is_nan((float) $value)
            || $value < -self::TOLERANCE
            || $value > 1 + self::TOLERANCE
        ) {
            throw new InvalidArgumentException(sprintf('%s must be a number between 0 and 1.', $label));
        }

        return min(1.0, max(0.0, (float) $value));
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function getJsonSchema(): array
    {
        $probabilitySchema = [
            'type' => 'number',
            'minimum' => 0,
            'maximum' => 1,
        ];

        return [
            'type' => 'object',
            'properties' => [
                self::KEY_TYPE => [
                    'type' => 'string',
                    'enum' => DecisionQuestionTypeEnum::getValues(),
                    'description' => 'The type of the question answered.',
                ],
                self::KEY_VALUE => [
                    'oneOf' => [
                        [
                            'type' => 'number',
                            'minimum' => 0,
                        ],
                        [
                            'type' => 'string',
                        ],
                        [
                            'type' => 'null',
                        ],
                    ],
                    'description' => 'The probability for a binary question, the chosen option value for a '
                        . 'choice question, the position on the scale for a score question, or null if the '
                        . 'model refused to answer.',
                ],
                self::KEY_CONFIDENCE => array_merge($probabilitySchema, [
                    'description' => 'The model\'s confidence in the answer.',
                ]),
                self::KEY_PROBABILITIES => [
                    'oneOf' => [
                        [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    self::KEY_VALUE => [
                                        'type' => 'string',
                                    ],
                                    self::KEY_PROBABILITY => $probabilitySchema,
                                ],
                                'required' => [self::KEY_VALUE, self::KEY_PROBABILITY],
                            ],
                        ],
                        [
                            'type' => 'array',
                            'items' => $probabilitySchema,
                        ],
                    ],
                    'description' => 'The probability of each option for a choice question, or of each level '
                        . 'from lowest to highest for a score question.',
                ],
            ],
            'required' => [self::KEY_TYPE, self::KEY_VALUE],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return DecisionAnswerArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_TYPE => $this->type->value,
            self::KEY_VALUE => $this->value,
        ];

        if ($this->confidence !== null) {
            $data[self::KEY_CONFIDENCE] = $this->confidence;
        }

        if ($this->probabilities !== []) {
            $data[self::KEY_PROBABILITIES] = $this->type->isChoice()
                ? $this->getOptionProbabilitiesList()
                : array_values($this->probabilities);
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
        static::validateFromArrayData($array, [self::KEY_TYPE, self::KEY_VALUE]);

        // Choice probabilities are listed with their option values, and score probabilities by level.
        $probabilities = [];
        foreach ($array[self::KEY_PROBABILITIES] ?? [] as $index => $entry) {
            if (is_array($entry)) {
                $probabilities[$entry[self::KEY_VALUE]] = $entry[self::KEY_PROBABILITY];
            } else {
                $probabilities[$index] = $entry;
            }
        }

        return new self(
            DecisionQuestionTypeEnum::from($array[self::KEY_TYPE]),
            $array[self::KEY_VALUE],
            $array[self::KEY_CONFIDENCE] ?? null,
            $probabilities
        );
    }

    /**
     * Lists the probability of each option of a choice question with its option value.
     *
     * Unlike an array keyed by option value, the list keeps option values such as "5" as strings, and always
     * serializes to a JSON array.
     *
     * @since n.e.x.t
     *
     * @return list<array{value: string, probability: float}> The option values and their probabilities.
     */
    private function getOptionProbabilitiesList(): array
    {
        $list = [];
        foreach ($this->probabilities as $value => $probability) {
            $list[] = [
                self::KEY_VALUE => (string) $value,
                self::KEY_PROBABILITY => $probability,
            ];
        }

        return $list;
    }
}

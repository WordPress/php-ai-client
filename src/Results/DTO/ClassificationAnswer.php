<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\Models\Classification\Enums\ClassificationQuestionTypeEnum;

/**
 * Represents a classification model's answer to a single question.
 *
 * An answer has the type of the question it answers, which determines its value:
 *
 * - binary: the probability, from 0 to 1, that the statement is true. See {@see self::getProbability()}.
 * - choice: the key of the chosen option. See {@see self::getChoice()}.
 * - score: the position on the scale, from 0 for the lowest level to one less than the number of levels.
 *   It may fall between levels, for example when a model reports a probability-weighted position. See
 *   {@see self::getScore()}.
 *
 * Models may also report how confident they are in the answer and, for choice and score questions, the
 * probability of each option or level.
 *
 * @since n.e.x.t
 *
 * @phpstan-type ClassificationAnswerArrayShape array{
 *     type: string,
 *     value: float|string,
 *     confidence?: float,
 *     probabilities?: array<string, float>|list<float>
 * }
 *
 * @extends AbstractDataTransferObject<ClassificationAnswerArrayShape>
 */
class ClassificationAnswer extends AbstractDataTransferObject
{
    public const KEY_TYPE = 'type';
    public const KEY_VALUE = 'value';
    public const KEY_CONFIDENCE = 'confidence';
    public const KEY_PROBABILITIES = 'probabilities';

    /**
     * @var ClassificationQuestionTypeEnum The type of the question answered.
     */
    private ClassificationQuestionTypeEnum $type;

    /**
     * @var float|string The probability, the chosen option key, or the position on the scale.
     */
    private $value;

    /**
     * @var float|null The model's confidence in the answer, if reported.
     */
    private ?float $confidence;

    /**
     * @var array<string, float>|list<float> The probability of each option or level, if reported.
     */
    private array $probabilities;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param ClassificationQuestionTypeEnum $type The type of the question answered.
     * @param float|string $value The probability for a binary question, the chosen option key for a choice
     *                            question, or the position on the scale for a score question.
     * @param float|null $confidence The model's confidence in the answer, if reported.
     * @param array<string, float>|list<float> $probabilities For a choice question, the probability of each
     *                                                        option keyed by option key. For a score question,
     *                                                        the probability of each level from lowest to
     *                                                        highest. Empty if not reported, and always empty
     *                                                        for a binary question.
     *
     * @throws InvalidArgumentException If the value, confidence, or probabilities do not suit the type, or a
     *                                  confidence or probability is not between 0 and 1.
     */
    public function __construct(
        ClassificationQuestionTypeEnum $type,
        $value,
        ?float $confidence = null,
        array $probabilities = []
    ) {
        if ($type->isBinary()) {
            $value = self::validateProbability($value, 'Classification answer probability');
        } elseif ($type->isChoice()) {
            if (!is_string($value) || trim($value) === '') {
                throw new InvalidArgumentException('Classification answer choice must be a non-empty option key.');
            }
        } else {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value < 0) {
                throw new InvalidArgumentException('Classification answer score must be a non-negative number.');
            }

            $value = (float) $value;
        }

        if ($confidence !== null) {
            $confidence = self::validateProbability($confidence, 'Classification answer confidence');
        }

        if ($probabilities !== []) {
            if ($type->isBinary()) {
                throw new InvalidArgumentException(
                    'Classification answers to binary questions do not accept probabilities, '
                    . 'as their value is the probability.'
                );
            }

            if ($type->isScore() && !array_is_list($probabilities)) {
                throw new InvalidArgumentException(
                    'Classification answer probabilities for a score question must be a list, '
                    . 'from the lowest level to the highest.'
                );
            }

            foreach ($probabilities as $key => $probability) {
                if ($type->isChoice() && (!is_string($key) || trim($key) === '')) {
                    throw new InvalidArgumentException(
                        'Classification answer probabilities for a choice question must be keyed by option key.'
                    );
                }

                $probabilities[$key] = self::validateProbability(
                    $probability,
                    'Classification answer probabilities'
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
     * @return ClassificationQuestionTypeEnum The question type.
     */
    public function getType(): ClassificationQuestionTypeEnum
    {
        return $this->type;
    }

    /**
     * Gets the probability that the statement of a binary question is true.
     *
     * @since n.e.x.t
     *
     * @return float The probability, from 0 to 1.
     * @throws RuntimeException If this is not an answer to a binary question.
     */
    public function getProbability(): float
    {
        $this->assertType(ClassificationQuestionTypeEnum::binary());

        return (float) $this->value;
    }

    /**
     * Gets the chosen option of a choice question.
     *
     * @since n.e.x.t
     *
     * @return string The option key.
     * @throws RuntimeException If this is not an answer to a choice question.
     */
    public function getChoice(): string
    {
        $this->assertType(ClassificationQuestionTypeEnum::choice());

        return (string) $this->value;
    }

    /**
     * Gets the position on the scale of a score question.
     *
     * @since n.e.x.t
     *
     * @return float The position, from 0 for the lowest level to one less than the number of levels.
     * @throws RuntimeException If this is not an answer to a score question.
     */
    public function getScore(): float
    {
        $this->assertType(ClassificationQuestionTypeEnum::score());

        return (float) $this->value;
    }

    /**
     * Gets the model's confidence in the answer.
     *
     * @since n.e.x.t
     *
     * @return float|null The confidence between 0 and 1, or null if not reported.
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
     * @return array<string, float>|list<float> The probabilities keyed by option key for a choice question, or
     *                                          listed from the lowest level to the highest for a score
     *                                          question. Empty if not reported, and always empty for a binary
     *                                          question.
     */
    public function getProbabilities(): array
    {
        return $this->probabilities;
    }

    /**
     * Asserts that this is an answer to a question of the given type.
     *
     * @since n.e.x.t
     *
     * @param ClassificationQuestionTypeEnum $type The expected question type.
     * @return void
     * @throws RuntimeException If this is an answer to a question of another type.
     */
    private function assertType(ClassificationQuestionTypeEnum $type): void
    {
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
     * @return float The validated value.
     * @throws InvalidArgumentException If the value is not a number between 0 and 1.
     */
    private static function validateProbability($value, string $label): float
    {
        // NaN fails no comparison, so it is excluded explicitly.
        if ((!is_int($value) && !is_float($value)) || is_nan((float) $value) || $value < 0 || $value > 1) {
            throw new InvalidArgumentException(sprintf('%s must be a number between 0 and 1.', $label));
        }

        return (float) $value;
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
                    'enum' => ClassificationQuestionTypeEnum::getValues(),
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
                    ],
                    'description' => 'The probability for a binary question, the chosen option key for a choice '
                        . 'question, or the position on the scale for a score question.',
                ],
                self::KEY_CONFIDENCE => array_merge($probabilitySchema, [
                    'description' => 'The model\'s confidence in the answer.',
                ]),
                self::KEY_PROBABILITIES => [
                    'oneOf' => [
                        [
                            'type' => 'object',
                            'additionalProperties' => $probabilitySchema,
                        ],
                        [
                            'type' => 'array',
                            'items' => $probabilitySchema,
                        ],
                    ],
                    'description' => 'The probability of each option keyed by option key for a choice question, '
                        . 'or of each level from lowest to highest for a score question.',
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
     * @return ClassificationAnswerArrayShape
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
            $data[self::KEY_PROBABILITIES] = $this->probabilities;
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

        return new self(
            ClassificationQuestionTypeEnum::from($array[self::KEY_TYPE]),
            $array[self::KEY_VALUE],
            $array[self::KEY_CONFIDENCE] ?? null,
            $array[self::KEY_PROBABILITIES] ?? []
        );
    }
}

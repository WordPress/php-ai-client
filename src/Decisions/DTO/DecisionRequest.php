<?php

declare(strict_types=1);

namespace WordPress\AiClient\Decisions\DTO;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Files\DTO\File;

/**
 * Represents a request to a decision model: the state to decide on, and the questions to answer about it.
 *
 * The state is a string, a single file, or an array of JSON-compatible values that may contain files at any
 * depth, such as `['comment' => $text, 'photo' => $file]`. Each provider sends files the way its API
 * expects, for example as references within the state or as separate image inputs.
 *
 * Unlike the other DTOs, a request cannot be converted to and from an array, because a file in the state
 * could not be told apart from an array that happens to have the same shape.
 *
 * @since n.e.x.t
 */
class DecisionRequest
{
    /**
     * @var string|File|array<mixed> The state to decide on.
     */
    private $state;

    /**
     * @var array<string, DecisionQuestion> The questions to answer, keyed by question key.
     */
    private array $questions;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string|File|array<mixed> $state The state to decide on.
     * @param array<string, DecisionQuestion> $questions The questions to answer, keyed by question key.
     *
     * @throws InvalidArgumentException If the state is empty or holds a value that is neither JSON-compatible
     *                                  nor a file, or if there are no questions or a question key is invalid.
     */
    public function __construct($state, array $questions)
    {
        if (is_string($state)) {
            if (trim($state) === '') {
                throw new InvalidArgumentException('Decision state cannot be empty.');
            }
        } elseif (is_array($state)) {
            if ($state === []) {
                throw new InvalidArgumentException('Decision state cannot be empty.');
            }

            self::validateStateValues($state);
        } elseif (!$state instanceof File) {
            throw new InvalidArgumentException('Decision state must be a string, a file, or an array.');
        }

        if ($questions === []) {
            throw new InvalidArgumentException('Decision requests require at least one question.');
        }

        foreach ($questions as $key => $question) {
            // PHP converts integer-like string keys to integers, so those are rejected along with lists.
            if (!is_string($key) || trim($key) === '') {
                throw new InvalidArgumentException('Decision question keys must be non-empty, non-integer strings.');
            }

            if (!$question instanceof DecisionQuestion) {
                throw new InvalidArgumentException('Decision questions must be DecisionQuestion instances.');
            }
        }

        $this->state = $state;
        $this->questions = $questions;
    }

    /**
     * Gets the state to decide on.
     *
     * @since n.e.x.t
     *
     * @return string|File|array<mixed> The state.
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Gets the questions to answer.
     *
     * @since n.e.x.t
     *
     * @return array<string, DecisionQuestion> The questions, keyed by question key.
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    /**
     * Validates that every value in the state is JSON-compatible or a file.
     *
     * @since n.e.x.t
     *
     * @param array<mixed> $values The state values to validate.
     * @return void
     * @throws InvalidArgumentException If a value is neither JSON-compatible nor a file.
     */
    private static function validateStateValues(array $values): void
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                self::validateStateValues($value);
                continue;
            }

            if (is_float($value) && !is_finite($value)) {
                throw new InvalidArgumentException('Decision state numbers must be finite.');
            }

            if ($value !== null && !is_scalar($value) && !$value instanceof File) {
                throw new InvalidArgumentException(
                    'Decision state values must be strings, numbers, booleans, null, arrays, or files.'
                );
            }
        }
    }
}

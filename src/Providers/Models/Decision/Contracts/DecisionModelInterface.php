<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Decision\Contracts;

use WordPress\AiClient\Providers\Models\Decision\DTO\DecisionQuestion;
use WordPress\AiClient\Results\DTO\DecisionResult;

/**
 * Interface for models that support decisions.
 *
 * @since n.e.x.t
 */
interface DecisionModelInterface
{
    /**
     * Answers typed questions about the given state.
     *
     * Each answer must have the type of its question. The answer to a choice question must be one of the
     * question's options, and the answer to a score question must lie on the question's scale.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $state The state to decide on, keyed by name.
     * @param array<string, DecisionQuestion> $questions The questions to answer, keyed by question key.
     * @return DecisionResult Result containing one answer per question, keyed by question key.
     */
    public function decideResult(array $state, array $questions): DecisionResult;
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Classification\Contracts;

use WordPress\AiClient\Providers\Models\Classification\DTO\ClassificationQuestion;
use WordPress\AiClient\Results\DTO\ClassificationResult;

/**
 * Interface for models that support classification.
 *
 * @since n.e.x.t
 */
interface ClassificationModelInterface
{
    /**
     * Answers typed questions about the given state.
     *
     * Each answer must have the type of its question. The answer to a choice question must be one of the
     * question's options, and the answer to a score question must lie on the question's scale.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $state The state to classify, keyed by name.
     * @param array<string, ClassificationQuestion> $questions The questions to answer, keyed by question key.
     * @return ClassificationResult Result containing one answer per question, keyed by question key.
     */
    public function classifyResult(array $state, array $questions): ClassificationResult;
}

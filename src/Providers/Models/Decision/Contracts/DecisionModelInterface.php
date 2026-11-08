<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Decision\Contracts;

use WordPress\AiClient\Decisions\DTO\DecisionRequest;
use WordPress\AiClient\Results\DTO\DecisionResult;

/**
 * Interface for models that support decisions.
 *
 * As with text generation and embeddings, a decision model is only found by model discovery if its metadata
 * declares the `inputModalities` option with the combinations of input it accepts. Every decision requires
 * text input, and each file in the state adds its modality, so a text-only model declares `[[text]]` and a
 * model that also accepts images in the state declares `[[text], [text, image]]`.
 *
 * @since n.e.x.t
 */
interface DecisionModelInterface
{
    /**
     * Answers typed questions about the given state.
     *
     * The result must hold one answer per question, keyed by question key. Each answer must have the type of
     * its question, and is either a refusal or fits its question: the answer to a choice question must be the
     * value of one of the question's options, and the answer to a score question must lie on the question's
     * scale.
     *
     * @since n.e.x.t
     *
     * @param DecisionRequest $request The state to decide on, and the questions to answer about it.
     * @return DecisionResult Result containing one answer per question, keyed by question key.
     */
    public function decideResult(DecisionRequest $request): DecisionResult;
}

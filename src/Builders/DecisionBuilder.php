<?php

declare(strict_types=1);

namespace WordPress\AiClient\Builders;

use Psr\EventDispatcher\EventDispatcherInterface;
use WordPress\AiClient\Builders\Traits\ModelResolutionTrait;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Decisions\DTO\DecisionOption;
use WordPress\AiClient\Decisions\DTO\DecisionQuestion;
use WordPress\AiClient\Decisions\DTO\DecisionRequest;
use WordPress\AiClient\Events\AfterDecideEvent;
use WordPress\AiClient\Events\BeforeDecideEvent;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Providers\ModelResolver;
use WordPress\AiClient\Providers\Models\Decision\Contracts\DecisionModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\DecisionAnswer;
use WordPress\AiClient\Results\DTO\DecisionResult;

/**
 * Fluent builder for decisions.
 *
 * Decision models answer typed questions about the given state, rather than generating content. Each
 * answer is a probability, a chosen option, or a position on a scale, optionally with a confidence and a
 * probability for each option or level. A model may also refuse to answer a question.
 *
 * The state is a string, a file, or an array of JSON-compatible values that may contain files, such as
 * `['comment' => $text, 'photo' => $file]`. Files in the state are taken into account when finding a model
 * that supports their input modality.
 *
 * Like {@see PromptBuilder}, this builder discovers a suitable model across the configured providers if
 * none is specified.
 *
 * @since n.e.x.t
 */
class DecisionBuilder
{
    use ModelResolutionTrait;

    /**
     * @var string|File|array<mixed>|null The state to decide on.
     */
    protected $state;

    /**
     * @var array<string, DecisionQuestion> The questions to answer, keyed by question key.
     */
    protected array $questions = [];

    /**
     * @var EventDispatcherInterface|null The event dispatcher for decision lifecycle events.
     */
    private ?EventDispatcherInterface $eventDispatcher;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The provider registry for finding suitable models.
     * @param string|File|array<mixed>|null $state Optional state to decide on.
     * @param EventDispatcherInterface|null $eventDispatcher Optional event dispatcher for lifecycle events.
     */
    public function __construct(
        ProviderRegistry $registry,
        $state = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->modelConfig = new ModelConfig();
        $this->modelResolver = new ModelResolver($registry);
        $this->eventDispatcher = $eventDispatcher;

        if ($state !== null) {
            $this->withState($state);
        }
    }

    /**
     * Creates a deep clone of this builder.
     *
     * Questions are immutable and therefore shared between clones. The event dispatcher is a service object
     * and is intentionally NOT cloned.
     *
     * @since n.e.x.t
     */
    public function __clone()
    {
        $this->modelConfig = clone $this->modelConfig;
        $this->modelResolver = clone $this->modelResolver;
    }

    /**
     * Sets the state to decide on, replacing any state set before.
     *
     * The state is validated when the questions are answered, see {@see DecisionRequest}.
     *
     * @since n.e.x.t
     *
     * @param string|File|array<mixed> $state The state: a string, a file, or an array of JSON-compatible
     *                                        values that may contain files.
     * @return self
     */
    public function withState($state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Adds a question to answer about the state.
     *
     * A question added with an existing key replaces the previous question.
     *
     * @since n.e.x.t
     *
     * @param string $key The key identifying the question and its answer.
     * @param DecisionQuestion $question The question.
     * @return self
     * @throws InvalidArgumentException If the key is empty or an integer.
     */
    public function withQuestion(string $key, DecisionQuestion $question): self
    {
        // PHP converts integer-like array keys such as "1" to integers, so the questions could no longer be
        // keyed by name.
        if (trim($key) === '' || (string) (int) $key === $key) {
            throw new InvalidArgumentException('Decision question keys must be non-empty, non-integer strings.');
        }

        $this->questions[$key] = $question;

        return $this;
    }

    /**
     * Checks whether a model is available for decisions with the current configuration.
     *
     * @since n.e.x.t
     *
     * @return bool True if the set model or any registered model supports decisions.
     */
    public function isSupported(): bool
    {
        return $this->modelResolver->isSupported($this->getModelRequirements());
    }

    /**
     * Answers the configured questions about the state.
     *
     * @since n.e.x.t
     *
     * @return DecisionResult The result containing one answer or refusal per question.
     * @throws InvalidArgumentException If no state or questions are configured, the state is invalid, or no
     *                                  suitable model is found.
     * @throws RuntimeException If the model does not support decisions, does not give a valid answer or a
     *                          refusal for every question, or answers a question that was not asked.
     */
    public function decideResult(): DecisionResult
    {
        if ($this->state === null) {
            throw new InvalidArgumentException('Cannot decide without state. Add state using withState().');
        }

        if ($this->questions === []) {
            throw new InvalidArgumentException(
                'Cannot decide without questions. Add questions using withQuestion().'
            );
        }

        $request = new DecisionRequest($this->state, $this->questions);

        $capability = CapabilityEnum::decision();
        $model = $this->modelResolver->resolve($this->getModelRequirements(), $this->modelConfig);

        if (!$model instanceof DecisionModelInterface) {
            throw new RuntimeException(
                sprintf(
                    'Model "%s" does not support decisions.',
                    $model->metadata()->getId()
                )
            );
        }

        $this->dispatchEvent(new BeforeDecideEvent($request, $model, $capability));

        $result = $model->decideResult($request);

        $this->assertValidAnswers($result);

        $this->dispatchEvent(new AfterDecideEvent($request, $model, $capability, $result));

        return $result;
    }

    /**
     * Gets the requirements a model must meet to decide with the current configuration.
     *
     * @since n.e.x.t
     *
     * @return ModelRequirements The model requirements.
     */
    private function getModelRequirements(): ModelRequirements
    {
        return ModelRequirements::fromDecisionData(self::findFiles($this->state), $this->modelConfig);
    }

    /**
     * Finds the files in the state, at any depth.
     *
     * @since n.e.x.t
     *
     * @param mixed $state The state, or a value within it.
     * @return list<File> The files in the state.
     */
    private static function findFiles($state): array
    {
        if ($state instanceof File) {
            return [$state];
        }

        if (!is_array($state)) {
            return [];
        }

        $files = [];
        foreach ($state as $value) {
            $files = array_merge($files, self::findFiles($value));
        }

        return $files;
    }

    /**
     * Asserts that the result holds a valid answer or a refusal for every question.
     *
     * @since n.e.x.t
     *
     * @param DecisionResult $result The result from the model.
     * @return void
     * @throws RuntimeException If a question is unanswered, a question that was not asked is answered, or an
     *                          answer does not fit its question.
     */
    private function assertValidAnswers(DecisionResult $result): void
    {
        $answers = $result->getAnswers();

        // Answers map to questions by key, so every question must be answered. A model that will not answer a
        // question must say so with a refusal, rather than leave the answer out.
        $unanswered = array_diff_key($this->questions, $answers);
        if ($unanswered !== []) {
            throw new RuntimeException(
                sprintf(
                    'The model did not answer the following questions: %s.',
                    implode(', ', array_keys($unanswered))
                )
            );
        }

        // An answer to a question that was not asked points to the same kind of mistake in the model.
        $unasked = array_diff_key($answers, $this->questions);
        if ($unasked !== []) {
            throw new RuntimeException(
                sprintf(
                    'The model answered questions that were not asked: %s.',
                    implode(', ', array_keys($unasked))
                )
            );
        }

        foreach ($this->questions as $key => $question) {
            $problem = $this->describeInvalidAnswer($question, $answers[$key]);
            if ($problem !== null) {
                throw new RuntimeException(
                    sprintf('The model gave an invalid answer to the question "%s". %s', $key, $problem)
                );
            }
        }
    }

    /**
     * Describes why an answer does not fit the question it answers.
     *
     * @since n.e.x.t
     *
     * @param DecisionQuestion $question The question.
     * @param DecisionAnswer $answer The answer to the question.
     * @return string|null A description of the problem, or null if the answer fits the question.
     */
    private function describeInvalidAnswer(DecisionQuestion $question, DecisionAnswer $answer): ?string
    {
        $type = $question->getType();

        if (!$answer->getType()->is($type)) {
            return sprintf(
                'Expected an answer to a %s question, but received an answer to a %s question.',
                $type->value,
                $answer->getType()->value
            );
        }

        if ($answer->isRefusal()) {
            return null;
        }

        $probabilities = $answer->getProbabilities();

        if ($type->isChoice()) {
            $optionValues = array_map(
                static function (DecisionOption $option): string {
                    return $option->getValue();
                },
                $question->getOptions()
            );

            if (!in_array($answer->getChoice(), $optionValues, true)) {
                return sprintf('"%s" is not one of its options.', $answer->getChoice());
            }

            // PHP converts integer-like option values to integer keys, so the keys are compared as strings.
            $unknownOptions = array_diff(array_map('strval', array_keys($probabilities)), $optionValues);
            if ($unknownOptions !== []) {
                return sprintf(
                    'It has probabilities for options the question does not have: %s.',
                    implode(', ', $unknownOptions)
                );
            }
        }

        if ($type->isScore()) {
            $levelCount = count($question->getLevels());

            if ($answer->getScore() > $levelCount - 1 + DecisionAnswer::TOLERANCE) {
                return sprintf(
                    'The score %s is outside the scale, which runs from 0 to %d.',
                    $answer->getScore(),
                    $levelCount - 1
                );
            }

            if ($probabilities !== [] && count($probabilities) !== $levelCount) {
                return sprintf(
                    'It has probabilities for %d levels, but the question has %d.',
                    count($probabilities),
                    $levelCount
                );
            }
        }

        return null;
    }

    /**
     * Dispatches an event if an event dispatcher is registered.
     *
     * @since n.e.x.t
     *
     * @param object $event The event to dispatch.
     * @return void
     */
    private function dispatchEvent(object $event): void
    {
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}

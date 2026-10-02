<?php

declare(strict_types=1);

namespace WordPress\AiClient\Builders;

use Psr\EventDispatcher\EventDispatcherInterface;
use WordPress\AiClient\Builders\Traits\ModelResolutionTrait;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Events\AfterClassifyEvent;
use WordPress\AiClient\Events\BeforeClassifyEvent;
use WordPress\AiClient\Providers\ModelResolver;
use WordPress\AiClient\Providers\Models\Classification\Contracts\ClassificationModelInterface;
use WordPress\AiClient\Providers\Models\Classification\DTO\ClassificationQuestion;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\ClassificationAnswer;
use WordPress\AiClient\Results\DTO\ClassificationResult;

/**
 * Fluent builder for classification.
 *
 * Classification answers typed questions about the given state, rather than generating content. Each
 * answer is a probability, a chosen option, or a position on a scale, optionally with a confidence and a
 * probability for each option or level.
 *
 * Like {@see PromptBuilder}, this builder discovers a suitable model across the configured providers if
 * none is specified.
 *
 * @since n.e.x.t
 */
class ClassificationBuilder
{
    use ModelResolutionTrait;

    /**
     * @var array<string, mixed> The state to classify.
     */
    protected array $state = [];

    /**
     * @var array<string, ClassificationQuestion> The questions to answer, keyed by question key.
     */
    protected array $questions = [];

    /**
     * @var EventDispatcherInterface|null The event dispatcher for classification lifecycle events.
     */
    private ?EventDispatcherInterface $eventDispatcher;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The provider registry for finding suitable models.
     * @param array<string, mixed>|null $state Optional initial state to classify.
     * @param EventDispatcherInterface|null $eventDispatcher Optional event dispatcher for lifecycle events.
     */
    public function __construct(
        ProviderRegistry $registry,
        ?array $state = null,
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
     * Adds state to classify.
     *
     * Keys that are already present are overwritten.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $state The state to add, keyed by name.
     * @return self
     * @throws InvalidArgumentException If the state is empty or not keyed by name.
     */
    public function withState(array $state): self
    {
        if ($state === []) {
            throw new InvalidArgumentException('Classification state cannot be empty.');
        }

        foreach (array_keys($state) as $key) {
            // PHP converts integer-like string keys to integers, so those are rejected along with lists.
            if (!is_string($key) || trim($key) === '') {
                throw new InvalidArgumentException('Classification state keys must be non-empty, non-integer strings.');
            }
        }

        $this->state = array_replace($this->state, $state);

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
     * @param ClassificationQuestion $question The question.
     * @return self
     * @throws InvalidArgumentException If the key is empty or an integer.
     */
    public function withQuestion(string $key, ClassificationQuestion $question): self
    {
        // PHP converts integer-like array keys such as "1" to integers, so the questions could no longer be
        // keyed by name.
        if (trim($key) === '' || (string) (int) $key === $key) {
            throw new InvalidArgumentException('Classification question keys must be non-empty, non-integer strings.');
        }

        $this->questions[$key] = $question;

        return $this;
    }

    /**
     * Checks whether a model is available for classification with the current configuration.
     *
     * @since n.e.x.t
     *
     * @return bool True if the set model or any registered model supports classification.
     */
    public function isSupported(): bool
    {
        return $this->modelResolver->isSupported($this->getModelRequirements());
    }

    /**
     * Classifies the state by answering the configured questions.
     *
     * @since n.e.x.t
     *
     * @return ClassificationResult The result containing one answer per question.
     * @throws InvalidArgumentException If no state or questions are configured, or no suitable model is found.
     * @throws RuntimeException If the model does not support classification, or does not give a valid answer
     *                          to every question.
     */
    public function classifyResult(): ClassificationResult
    {
        if ($this->state === []) {
            throw new InvalidArgumentException('Cannot classify empty state. Add state using withState().');
        }

        if ($this->questions === []) {
            throw new InvalidArgumentException(
                'Cannot classify without questions. Add questions using withQuestion().'
            );
        }

        $capability = CapabilityEnum::classification();
        $model = $this->modelResolver->resolve($this->getModelRequirements(), $this->modelConfig);

        if (!$model instanceof ClassificationModelInterface) {
            throw new RuntimeException(
                sprintf(
                    'Model "%s" does not support classification.',
                    $model->metadata()->getId()
                )
            );
        }

        $this->dispatchEvent(new BeforeClassifyEvent($this->state, $this->questions, $model, $capability));

        $result = $model->classifyResult($this->state, $this->questions);

        $this->assertValidAnswers($result);

        $this->dispatchEvent(
            new AfterClassifyEvent($this->state, $this->questions, $model, $capability, $result)
        );

        return $result;
    }

    /**
     * Gets the requirements a model must meet to classify with the current configuration.
     *
     * @since n.e.x.t
     *
     * @return ModelRequirements The model requirements.
     */
    private function getModelRequirements(): ModelRequirements
    {
        // Classification takes state and questions rather than messages, so only the capability and the
        // model configuration contribute requirements.
        return ModelRequirements::fromPromptData(CapabilityEnum::classification(), [], $this->modelConfig);
    }

    /**
     * Asserts that the result holds a valid answer to every question.
     *
     * @since n.e.x.t
     *
     * @param ClassificationResult $result The result from the model.
     * @return void
     * @throws RuntimeException If a question is unanswered, or an answer does not fit its question.
     */
    private function assertValidAnswers(ClassificationResult $result): void
    {
        $answers = $result->getAnswers();

        // Answers map to questions by key, so every question must be answered.
        $unanswered = array_diff_key($this->questions, $answers);
        if ($unanswered !== []) {
            throw new RuntimeException(
                sprintf(
                    'The model did not answer the following questions: %s.',
                    implode(', ', array_keys($unanswered))
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
     * @param ClassificationQuestion $question The question.
     * @param ClassificationAnswer $answer The answer to the question.
     * @return string|null A description of the problem, or null if the answer fits the question.
     */
    private function describeInvalidAnswer(ClassificationQuestion $question, ClassificationAnswer $answer): ?string
    {
        $type = $question->getType();

        if (!$answer->getType()->is($type)) {
            return sprintf(
                'Expected an answer to a %s question, but received an answer to a %s question.',
                $type->value,
                $answer->getType()->value
            );
        }

        $criteria = $question->getCriteria();
        $probabilities = $answer->getProbabilities();

        if ($type->isChoice()) {
            if (!array_key_exists($answer->getChoice(), $criteria)) {
                return sprintf('"%s" is not one of its options.', $answer->getChoice());
            }

            $unknownOptions = array_diff_key($probabilities, $criteria);
            if ($unknownOptions !== []) {
                return sprintf(
                    'It has probabilities for options the question does not have: %s.',
                    implode(', ', array_keys($unknownOptions))
                );
            }
        }

        if ($type->isScore()) {
            $levelCount = count($criteria);

            if ($answer->getScore() > $levelCount - 1) {
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

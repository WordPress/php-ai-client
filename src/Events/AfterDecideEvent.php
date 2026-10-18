<?php

declare(strict_types=1);

namespace WordPress\AiClient\Events;

use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Decision\DTO\DecisionQuestion;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\DecisionResult;

/**
 * Event dispatched after a decision model has answered the questions about the state.
 *
 * @since n.e.x.t
 */
class AfterDecideEvent
{
    /**
     * @var array<string, mixed> The state that was sent to the model.
     */
    private array $state;

    /**
     * @var array<string, DecisionQuestion> The questions that were sent to the model, keyed by question key.
     */
    private array $questions;

    /**
     * @var ModelInterface The model that decided on the state.
     */
    private ModelInterface $model;

    /**
     * @var CapabilityEnum The capability that was used for decisions.
     */
    private CapabilityEnum $capability;

    /**
     * @var DecisionResult The result from the model.
     */
    private DecisionResult $result;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed>                  $state The state that was sent to the model.
     * @param array<string, DecisionQuestion> $questions The questions that were sent to the model, keyed
     *                                                         by question key.
     * @param ModelInterface                        $model The model that decided on the state.
     * @param CapabilityEnum                        $capability The capability that was used for decisions.
     * @param DecisionResult                  $result The result from the model.
     */
    public function __construct(
        array $state,
        array $questions,
        ModelInterface $model,
        CapabilityEnum $capability,
        DecisionResult $result
    ) {
        $this->state = $state;
        $this->questions = $questions;
        $this->model = $model;
        $this->capability = $capability;
        $this->result = $result;
    }

    /**
     * Gets the state that was sent to the model.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The state.
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * Gets the questions that were sent to the model.
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
     * Gets the model that decided on the state.
     *
     * @since n.e.x.t
     *
     * @return ModelInterface The model.
     */
    public function getModel(): ModelInterface
    {
        return $this->model;
    }

    /**
     * Gets the capability that was used for decisions.
     *
     * @since n.e.x.t
     *
     * @return CapabilityEnum The capability.
     */
    public function getCapability(): CapabilityEnum
    {
        return $this->capability;
    }

    /**
     * Gets the result from the model.
     *
     * @since n.e.x.t
     *
     * @return DecisionResult The result.
     */
    public function getResult(): DecisionResult
    {
        return $this->result;
    }

    /**
     * Performs a deep clone of the event.
     *
     * @since n.e.x.t
     */
    public function __clone()
    {
        $clonedQuestions = [];
        foreach ($this->questions as $key => $question) {
            $clonedQuestions[$key] = clone $question;
        }
        $this->questions = $clonedQuestions;
        $this->result = clone $this->result;
    }
}

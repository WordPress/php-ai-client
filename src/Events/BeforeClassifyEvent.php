<?php

declare(strict_types=1);

namespace WordPress\AiClient\Events;

use WordPress\AiClient\Providers\Models\Classification\DTO\ClassificationQuestion;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Event dispatched before state and questions are sent to a classification model.
 *
 * @since n.e.x.t
 */
class BeforeClassifyEvent
{
    /**
     * @var array<string, mixed> The state to be sent to the model.
     */
    private array $state;

    /**
     * @var array<string, ClassificationQuestion> The questions to be sent to the model, keyed by question key.
     */
    private array $questions;

    /**
     * @var ModelInterface The model that will classify the state.
     */
    private ModelInterface $model;

    /**
     * @var CapabilityEnum The capability being used for classification.
     */
    private CapabilityEnum $capability;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed>                  $state The state to be sent to the model.
     * @param array<string, ClassificationQuestion> $questions The questions to be sent to the model, keyed by
     *                                                         question key.
     * @param ModelInterface                        $model The model that will classify the state.
     * @param CapabilityEnum                        $capability The capability being used for classification.
     */
    public function __construct(array $state, array $questions, ModelInterface $model, CapabilityEnum $capability)
    {
        $this->state = $state;
        $this->questions = $questions;
        $this->model = $model;
        $this->capability = $capability;
    }

    /**
     * Gets the state to be sent to the model.
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
     * Gets the questions to be sent to the model.
     *
     * @since n.e.x.t
     *
     * @return array<string, ClassificationQuestion> The questions, keyed by question key.
     */
    public function getQuestions(): array
    {
        return $this->questions;
    }

    /**
     * Gets the model that will classify the state.
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
     * Gets the capability being used for classification.
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
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Events;

use WordPress\AiClient\Decisions\DTO\DecisionRequest;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
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
     * @var DecisionRequest The state and questions that were sent to the model.
     */
    private DecisionRequest $request;

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
     * @param DecisionRequest $request The state and questions that were sent to the model.
     * @param ModelInterface $model The model that decided on the state.
     * @param CapabilityEnum $capability The capability that was used for decisions.
     * @param DecisionResult $result The result from the model.
     */
    public function __construct(
        DecisionRequest $request,
        ModelInterface $model,
        CapabilityEnum $capability,
        DecisionResult $result
    ) {
        $this->request = $request;
        $this->model = $model;
        $this->capability = $capability;
        $this->result = $result;
    }

    /**
     * Gets the state and questions that were sent to the model.
     *
     * @since n.e.x.t
     *
     * @return DecisionRequest The request.
     */
    public function getRequest(): DecisionRequest
    {
        return $this->request;
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
        $this->request = clone $this->request;
        $this->result = clone $this->result;
    }
}

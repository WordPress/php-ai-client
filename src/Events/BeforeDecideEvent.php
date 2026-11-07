<?php

declare(strict_types=1);

namespace WordPress\AiClient\Events;

use WordPress\AiClient\Decisions\DTO\DecisionRequest;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Event dispatched before state and questions are sent to a decision model.
 *
 * @since n.e.x.t
 */
class BeforeDecideEvent
{
    /**
     * @var DecisionRequest The state and questions to be sent to the model.
     */
    private DecisionRequest $request;

    /**
     * @var ModelInterface The model that will decide on the state.
     */
    private ModelInterface $model;

    /**
     * @var CapabilityEnum The capability being used for decisions.
     */
    private CapabilityEnum $capability;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param DecisionRequest $request The state and questions to be sent to the model.
     * @param ModelInterface $model The model that will decide on the state.
     * @param CapabilityEnum $capability The capability being used for decisions.
     */
    public function __construct(DecisionRequest $request, ModelInterface $model, CapabilityEnum $capability)
    {
        $this->request = $request;
        $this->model = $model;
        $this->capability = $capability;
    }

    /**
     * Gets the state and questions to be sent to the model.
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
     * Gets the model that will decide on the state.
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
     * Gets the capability being used for decisions.
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
        $this->request = clone $this->request;
    }
}

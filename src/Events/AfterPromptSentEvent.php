<?php

declare(strict_types=1);

namespace WordPress\AiClient\Events;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Event dispatched after a prompt has been sent to the AI model and a response received.
 *
 * This event allows listeners to inspect the result of the model call for logging,
 * analytics, or other post-processing purposes. The result object is immutable.
 *
 * @since n.e.x.t
 */
class AfterPromptSentEvent
{
    /**
     * @var list<Message> The messages that were sent to the model.
     */
    private array $messages;

    /**
     * @var ModelInterface The model that processed the prompt.
     */
    private ModelInterface $model;

    /**
     * @var CapabilityEnum|null The capability that was used for generation.
     */
    private ?CapabilityEnum $capability;

    /**
     * @var GenerativeAiResult The result from the model.
     */
    private GenerativeAiResult $result;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages that were sent to the model.
     * @param ModelInterface $model The model that processed the prompt.
     * @param CapabilityEnum|null $capability The capability that was used for generation.
     * @param GenerativeAiResult $result The result from the model.
     */
    public function __construct(
        array $messages,
        ModelInterface $model,
        ?CapabilityEnum $capability,
        GenerativeAiResult $result
    ) {
        $this->messages = $messages;
        $this->model = $model;
        $this->capability = $capability;
        $this->result = $result;
    }

    /**
     * Gets the messages that were sent to the model.
     *
     * @since n.e.x.t
     *
     * @return list<Message> The messages.
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Gets the model that processed the prompt.
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
     * Gets the capability that was used for generation.
     *
     * @since n.e.x.t
     *
     * @return CapabilityEnum|null The capability, or null if not specified.
     */
    public function getCapability(): ?CapabilityEnum
    {
        return $this->capability;
    }

    /**
     * Gets the result from the model.
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiResult The result.
     */
    public function getResult(): GenerativeAiResult
    {
        return $this->result;
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Events;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Event dispatched before a prompt is sent to the AI model.
 *
 * This event allows listeners to inspect and modify the messages before they
 * are sent to the model. The event is not stoppable, meaning the model call
 * will always proceed regardless of listener actions.
 *
 * @since n.e.x.t
 */
class BeforeGenerateResultEvent
{
    /**
     * @var list<Message> The messages to be sent to the model.
     */
    private array $messages;

    /**
     * @var ModelInterface The model that will process the prompt.
     */
    private ModelInterface $model;

    /**
     * @var CapabilityEnum|null The capability being used for generation.
     */
    private ?CapabilityEnum $capability;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to be sent to the model.
     * @param ModelInterface $model The model that will process the prompt.
     * @param CapabilityEnum|null $capability The capability being used for generation.
     */
    public function __construct(array $messages, ModelInterface $model, ?CapabilityEnum $capability)
    {
        $this->messages = $messages;
        $this->model = $model;
        $this->capability = $capability;
    }

    /**
     * Gets the messages to be sent to the model.
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
     * Sets the messages to be sent to the model.
     *
     * This allows listeners to modify the messages before they are sent.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The modified messages.
     * @return void
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * Gets the model that will process the prompt.
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
     * Gets the capability being used for generation.
     *
     * @since n.e.x.t
     *
     * @return CapabilityEnum|null The capability, or null if not specified.
     */
    public function getCapability(): ?CapabilityEnum
    {
        return $this->capability;
    }
}

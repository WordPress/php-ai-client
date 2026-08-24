<?php

declare(strict_types=1);

namespace WordPress\AiClient\Events;

use Throwable;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Class GenerateResultErrorEvent.
 *
 * @since n.e.x.t
 */
class GenerateResultErrorEvent
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
     * @var Throwable The error that occurred during generation.
     */
    private Throwable $error;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages that were sent to the model.
     * @param ModelInterface $model The model that processed the prompt.
     * @param CapabilityEnum|null $capability The capability that was used for generation.
     * @param Throwable $error The error that occurred during generation.
     */
    public function __construct(
        array $messages,
        ModelInterface $model,
        ?CapabilityEnum $capability,
        Throwable $error
    ) {
        $this->messages = $messages;
        $this->model = $model;
        $this->capability = $capability;
        $this->error = $error;
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
     * Gets the error that occurred during generation.
     *
     * @since n.e.x.t
     *
     * @return Throwable The error.
     */
    public function getError(): Throwable
    {
        return $this->error;
    }

    /**
     * Performs a deep clone of the event.
     *
     * @since n.e.x.t
     */
    public function __clone()
    {
        $clonedMessages = [];
        foreach ($this->messages as $message) {
            $clonedMessages[] = clone $message;
        }
        $this->messages = $clonedMessages;
    }
}

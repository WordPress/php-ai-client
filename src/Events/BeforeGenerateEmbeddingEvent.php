<?php

declare(strict_types=1);

namespace WordPress\AiClient\Events;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Event dispatched before prompts are sent to an embedding generation model.
 *
 * @since n.e.x.t
 */
class BeforeGenerateEmbeddingEvent
{
    /**
     * @var list<list<Message>> The prompts to be sent to the model.
     */
    private array $prompts;

    /**
     * @var ModelInterface The model that will generate embeddings.
     */
    private ModelInterface $model;

    /**
     * @var CapabilityEnum The capability being used for generation.
     */
    private CapabilityEnum $capability;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param list<list<Message>> $prompts The prompts to be sent to the model.
     * @param ModelInterface      $model The model that will generate embeddings.
     * @param CapabilityEnum      $capability The capability being used for generation.
     */
    public function __construct(array $prompts, ModelInterface $model, CapabilityEnum $capability)
    {
        $this->prompts = $prompts;
        $this->model = $model;
        $this->capability = $capability;
    }

    /**
     * Gets the prompts to be sent to the model.
     *
     * @since n.e.x.t
     *
     * @return list<list<Message>> The prompts.
     */
    public function getPrompts(): array
    {
        return $this->prompts;
    }

    /**
     * Gets the model that will generate embeddings.
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
        $clonedPrompts = [];
        foreach ($this->prompts as $prompt) {
            $clonedPrompt = [];
            foreach ($prompt as $message) {
                $clonedPrompt[] = clone $message;
            }
            $clonedPrompts[] = $clonedPrompt;
        }
        $this->prompts = $clonedPrompts;
    }
}

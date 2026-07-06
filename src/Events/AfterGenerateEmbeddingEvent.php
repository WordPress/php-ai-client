<?php

declare(strict_types=1);

namespace WordPress\AiClient\Events;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\EmbeddingResult;

/**
 * Event dispatched after embeddings have been generated.
 *
 * @since n.e.x.t
 */
class AfterGenerateEmbeddingEvent
{
    /**
     * @var list<list<Message>> The prompts that were sent to the model.
     */
    private array $prompts;

    /**
     * @var ModelInterface The model that generated embeddings.
     */
    private ModelInterface $model;

    /**
     * @var CapabilityEnum The capability that was used for generation.
     */
    private CapabilityEnum $capability;

    /**
     * @var EmbeddingResult The result from the model.
     */
    private EmbeddingResult $result;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param list<list<Message>> $prompts The prompts that were sent to the model.
     * @param ModelInterface      $model The model that generated embeddings.
     * @param CapabilityEnum      $capability The capability that was used for generation.
     * @param EmbeddingResult     $result The result from the model.
     */
    public function __construct(
        array $prompts,
        ModelInterface $model,
        CapabilityEnum $capability,
        EmbeddingResult $result
    ) {
        $this->prompts = $prompts;
        $this->model = $model;
        $this->capability = $capability;
        $this->result = $result;
    }

    /**
     * Gets the prompts that were sent to the model.
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
     * Gets the model that generated embeddings.
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
     * @return EmbeddingResult The result.
     */
    public function getResult(): EmbeddingResult
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
        $clonedPrompts = [];
        foreach ($this->prompts as $prompt) {
            $clonedPrompt = [];
            foreach ($prompt as $message) {
                $clonedPrompt[] = clone $message;
            }
            $clonedPrompts[] = $clonedPrompt;
        }
        $this->prompts = $clonedPrompts;
        $this->result = clone $this->result;
    }
}

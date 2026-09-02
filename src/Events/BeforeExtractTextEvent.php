<?php

declare(strict_types=1);

namespace WordPress\AiClient\Events;

use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Event dispatched before a document is sent to a text extraction model.
 *
 * @since n.e.x.t
 */
class BeforeExtractTextEvent
{
    /**
     * @var File The document to be sent to the model.
     */
    private File $document;

    /**
     * @var ModelInterface The model that will extract text.
     */
    private ModelInterface $model;

    /**
     * @var CapabilityEnum The capability being used for extraction.
     */
    private CapabilityEnum $capability;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param File           $document The document to be sent to the model.
     * @param ModelInterface $model The model that will extract text.
     * @param CapabilityEnum $capability The capability being used for extraction.
     */
    public function __construct(File $document, ModelInterface $model, CapabilityEnum $capability)
    {
        $this->document = $document;
        $this->model = $model;
        $this->capability = $capability;
    }

    /**
     * Gets the document to be sent to the model.
     *
     * @since n.e.x.t
     *
     * @return File The document.
     */
    public function getDocument(): File
    {
        return $this->document;
    }

    /**
     * Gets the model that will extract text.
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
     * Gets the capability being used for extraction.
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
        $this->document = clone $this->document;
    }
}

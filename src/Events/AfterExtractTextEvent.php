<?php

declare(strict_types=1);

namespace WordPress\AiClient\Events;

use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\TextExtractionResult;

/**
 * Event dispatched after text has been extracted from a document.
 *
 * @since n.e.x.t
 */
class AfterExtractTextEvent
{
    /**
     * @var File The document that was sent to the model.
     */
    private File $document;

    /**
     * @var ModelInterface The model that extracted text.
     */
    private ModelInterface $model;

    /**
     * @var CapabilityEnum The capability that was used for extraction.
     */
    private CapabilityEnum $capability;

    /**
     * @var TextExtractionResult The result from the model.
     */
    private TextExtractionResult $result;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param File                 $document The document that was sent to the model.
     * @param ModelInterface       $model The model that extracted text.
     * @param CapabilityEnum       $capability The capability that was used for extraction.
     * @param TextExtractionResult $result The result from the model.
     */
    public function __construct(
        File $document,
        ModelInterface $model,
        CapabilityEnum $capability,
        TextExtractionResult $result
    ) {
        $this->document = $document;
        $this->model = $model;
        $this->capability = $capability;
        $this->result = $result;
    }

    /**
     * Gets the document that was sent to the model.
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
     * Gets the model that extracted text.
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
     * Gets the capability that was used for extraction.
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
     * @return TextExtractionResult The result.
     */
    public function getResult(): TextExtractionResult
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
        $this->document = clone $this->document;
        $this->result = clone $this->result;
    }
}

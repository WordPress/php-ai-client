<?php

declare(strict_types=1);

namespace WordPress\AiClient\Builders;

use WordPress\AiClient\Builders\Traits\ModelResolutionTrait;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Providers\ModelResolver;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\TextExtraction\Contracts\TextExtractionModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\TextExtractionResult;

/**
 * Fluent builder for extracting text from documents (OCR / document parsing).
 *
 * Text extraction transforms a document into structured, per-page content rather than generating
 * a conversational response. Model selection and configuration are shared with
 * {@see PromptBuilder} via the {@see ModelResolutionTrait}.
 *
 * @since n.e.x.t
 *
 * @phpstan-type DocumentInput string|File
 */
class TextExtractionBuilder
{
    use ModelResolutionTrait;

    /**
     * @var File|null The document to extract text from.
     */
    protected ?File $document = null;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The provider registry for finding suitable models.
     * @param DocumentInput|null $document Optional initial document to extract text from.
     */
    public function __construct(ProviderRegistry $registry, $document = null)
    {
        $this->modelConfig = new ModelConfig();
        $this->modelResolver = new ModelResolver($registry);

        if ($document !== null) {
            $this->withDocument($document);
        }
    }

    /**
     * Creates a deep clone of this builder.
     *
     * Clones the document and model configuration. Service objects are intentionally NOT cloned
     * as they are shared dependencies.
     *
     * @since n.e.x.t
     */
    public function __clone()
    {
        if ($this->document !== null) {
            $this->document = clone $this->document;
        }

        $this->modelConfig = clone $this->modelConfig;
        $this->modelResolver = clone $this->modelResolver;
    }

    /**
     * Sets the document to extract text from.
     *
     * @since n.e.x.t
     *
     * @param DocumentInput $document The document: a File instance, or a string URL,
     *                                data URI, base64 data, or local file path.
     * @param string|null $mimeType Optional MIME type of the document. Required when it
     *                              cannot be inferred from the input (e.g. an extensionless
     *                              URL such as `https://arxiv.org/pdf/1805.04770`). Ignored
     *                              when a File instance is given.
     * @return self
     * @throws InvalidArgumentException If the document input is invalid.
     */
    public function withDocument($document, ?string $mimeType = null): self
    {
        if (is_string($document)) {
            if (trim($document) === '') {
                throw new InvalidArgumentException('Cannot create a document from an empty string.');
            }
            $document = new File($document, $mimeType);
        }

        if (!$document instanceof File) {
            throw new InvalidArgumentException('Document must be a File instance or a string.');
        }

        $this->document = $document;

        return $this;
    }

    /**
     * Checks whether the current document and configuration are supported by an available model.
     *
     * @since n.e.x.t
     *
     * @return bool True if a suitable text extraction model is available.
     */
    public function isSupported(): bool
    {
        if ($this->document === null) {
            return false;
        }

        $requirements = ModelRequirements::fromExtractionData($this->document, $this->modelConfig);

        return $this->modelResolver->isSupported($requirements);
    }

    /**
     * Extracts text from the configured document.
     *
     * @since n.e.x.t
     *
     * @return TextExtractionResult The structured extraction result.
     * @throws InvalidArgumentException If no document is configured or model validation fails.
     * @throws RuntimeException If the resolved model doesn't support text extraction.
     */
    public function extractTextResult(): TextExtractionResult
    {
        if ($this->document === null) {
            throw new InvalidArgumentException(
                'Cannot extract text without a document. Add one using withDocument().'
            );
        }

        $requirements = ModelRequirements::fromExtractionData($this->document, $this->modelConfig);
        $model = $this->modelResolver->resolve($requirements, $this->modelConfig);

        if (!$model instanceof TextExtractionModelInterface) {
            throw new RuntimeException(
                sprintf(
                    'Model "%s" does not support text extraction.',
                    $model->metadata()->getId()
                )
            );
        }

        return $model->extractTextResult($this->document);
    }

    /**
     * Extracts text from the configured document and returns it as a single markdown string.
     *
     * @since n.e.x.t
     *
     * @return string The extracted content, pages joined in order.
     * @throws InvalidArgumentException If no document is configured or model validation fails.
     * @throws RuntimeException If the resolved model doesn't support text extraction.
     */
    public function extractText(): string
    {
        return $this->extractTextResult()->toMarkdown();
    }
}

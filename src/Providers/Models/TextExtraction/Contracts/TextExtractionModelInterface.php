<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\TextExtraction\Contracts;

use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Results\DTO\TextExtractionResult;

/**
 * Interface for a model that supports text extraction (OCR / document parsing).
 *
 * @since n.e.x.t
 */
interface TextExtractionModelInterface
{
    /**
     * Extracts text and structure from a document.
     *
     * Extraction options (page selection, image inclusion, provider-specific options) are
     * provided via the model's configuration, consistent with the other capability interfaces.
     *
     * @since n.e.x.t
     *
     * @param File $document The document to process (remote URL or inline data).
     * @return TextExtractionResult The structured extraction result.
     */
    public function extractTextResult(File $document): TextExtractionResult;
}

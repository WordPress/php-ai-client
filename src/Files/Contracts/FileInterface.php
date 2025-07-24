<?php

declare(strict_types=1);

namespace WordPress\AiClient\Files\Contracts;

use WordPress\AiClient\Files\ValueObjects\MimeType;

/**
 * Interface for file representations in the AI client.
 *
 * This interface defines the common contract for various file types that can be
 * used as input or output in AI operations.
 *
 * @since n.e.x.t
 */
interface FileInterface
{
    /**
     * Gets the MIME type of the file.
     *
     * @since n.e.x.t
     * @return MimeType The MIME type (e.g., 'image/png', 'audio/mp3').
     */
    public function getMimeType(): MimeType;
}

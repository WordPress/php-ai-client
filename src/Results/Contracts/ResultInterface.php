<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\Contracts;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * Interface for AI operation results.
 *
 * Results contain the output from AI operations along with metadata
 * such as token usage and provider-specific information.
 *
 * @since n.e.x.t
 */
interface ResultInterface extends WithJsonSchemaInterface
{
    /**
     * Gets the result ID.
     *
     * @since n.e.x.t
     *
     * @return string The unique result identifier.
     */
    public function getId(): string;

    /**
     * Gets token usage information.
     *
     * @since n.e.x.t
     *
     * @return TokenUsage Token usage statistics.
     */
    public function getTokenUsage(): TokenUsage;

    /**
     * Gets provider-specific metadata.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> Provider metadata.
     */
    public function getProviderMetadata(): array;
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Common\Exception;

/**
 * Exception thrown when a token limit is reached during prompt fulfillment.
 *
 * This exception is thrown when the token usage for a request exceeds the allowed limit, whether
 * that is the model's context window or a configured maximum. It covers two cases:
 *
 * - Proactively (input side): {@see \WordPress\AiClient\Builders\PromptBuilder} throws it before a
 *   request is sent when the estimated prompt size exceeds the resolved model's context window
 *   (see {@see \WordPress\AiClient\Providers\Models\DTO\ModelMetadata::getContextWindow()}). This is
 *   distinct from the per-request output cap configured via
 *   {@see \WordPress\AiClient\Providers\Models\DTO\ModelConfig::setMaxTokens()}.
 * - Reactively (output side): providers may throw it when a response indicates generation was
 *   truncated because the token limit was reached.
 *
 * The associated limit, when known, is available via {@see self::getMaxTokens()}.
 *
 * @since 1.0.0
 */
class TokenLimitReachedException extends RuntimeException
{
    /**
     * The token limit that was reached, if known.
     *
     * @since 1.0.0
     *
     * @var int|null
     */
    private $maxTokens;

    /**
     * Creates a new TokenLimitReachedException.
     *
     * @since 1.0.0
     *
     * @param string         $message   The exception message.
     * @param int|null       $maxTokens The token limit that was reached, if known.
     * @param \Throwable|null $previous  The previous throwable used for exception chaining.
     */
    public function __construct(string $message = '', ?int $maxTokens = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->maxTokens = $maxTokens;
    }

    /**
     * Returns the token limit that was reached, if known.
     *
     * @since 1.0.0
     *
     * @return int|null The token limit, or null if not provided.
     */
    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }
}

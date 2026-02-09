<?php

declare(strict_types=1);

namespace WordPress\AiClient\Common\Exception;

use Throwable;

/**
 * Exception thrown when generation stops due to reaching token limits.
 *
 * @since n.e.x.t
 */
class TokenLimitReachedException extends RuntimeException
{
    /**
     * @var int|null The configured max token limit for the request, if known.
     */
    private ?int $maxTokens;

    /**
     * @var string The provider stop reason that indicated token limit exhaustion.
     */
    private string $providerStopReason;

    /**
     * @var string|null The function name, if token limit was reached during tool-call generation.
     */
    private ?string $functionName;

    /**
     * @var list<string> Required function parameters that were missing, if any.
     */
    private array $missingRequiredParameters;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $message Exception message.
     * @param int|null $maxTokens The configured max token limit for the request, if known.
     * @param string $providerStopReason The provider stop reason.
     * @param string|null $functionName The function name, if relevant.
     * @param list<string> $missingRequiredParameters Missing required parameters, if relevant.
     * @param int $code Exception code.
     * @param Throwable|null $previous Previous throwable.
     */
    public function __construct(
        string $message,
        ?int $maxTokens,
        string $providerStopReason,
        ?string $functionName = null,
        array $missingRequiredParameters = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->maxTokens = $maxTokens;
        $this->providerStopReason = $providerStopReason;
        $this->functionName = $functionName;
        $this->missingRequiredParameters = $missingRequiredParameters;
    }

    /**
     * Gets the max token limit configured for the request.
     *
     * @since n.e.x.t
     *
     * @return int|null The max token limit, or null if unknown.
     */
    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    /**
     * Gets the provider stop reason that triggered this exception.
     *
     * @since n.e.x.t
     *
     * @return string The provider stop reason.
     */
    public function getProviderStopReason(): string
    {
        return $this->providerStopReason;
    }

    /**
     * Gets the related function name, if applicable.
     *
     * @since n.e.x.t
     *
     * @return string|null The function name.
     */
    public function getFunctionName(): ?string
    {
        return $this->functionName;
    }

    /**
     * Gets missing required function parameters, if applicable.
     *
     * @since n.e.x.t
     *
     * @return list<string> Missing required parameter names.
     */
    public function getMissingRequiredParameters(): array
    {
        return $this->missingRequiredParameters;
    }
}

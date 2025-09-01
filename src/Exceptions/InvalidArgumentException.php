<?php

declare(strict_types=1);

namespace WordPress\AiClient\Exceptions;

/**
 * Exception thrown when an invalid argument is provided.
 *
 * This extends PHP's built-in InvalidArgumentException while implementing
 * the AI Client exception interface for consistent catch handling.
 *
 * @since 0.2.0
 */
class InvalidArgumentException extends \InvalidArgumentException implements AiClientExceptionInterface
{
}
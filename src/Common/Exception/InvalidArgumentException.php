<?php

declare(strict_types=1);

namespace WordPress\AiClient\Common\Exception;

use WordPress\AiClient\Exceptions\AiClientExceptionInterface;

/**
 * Exception thrown when an invalid argument is provided.
 *
 * This extends PHP's built-in InvalidArgumentException while implementing
 * the AI Client exception interface for consistent catch handling.
 *
 * @since n.e.x.t
 */
class InvalidArgumentException extends \InvalidArgumentException implements AiClientExceptionInterface
{
}

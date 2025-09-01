<?php

declare(strict_types=1);

namespace WordPress\AiClient\Exceptions;

use RuntimeException;

/**
 * Exception thrown for network-related errors.
 *
 * This includes HTTP transport errors, connection failures,
 * timeouts, and other network-related issues.
 *
 * @since 0.2.0
 */
class NetworkException extends RuntimeException implements AiClientExceptionInterface
{
}
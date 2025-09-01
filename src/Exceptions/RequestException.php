<?php

declare(strict_types=1);

namespace WordPress\AiClient\Exceptions;

use RuntimeException;

/**
 * Exception thrown for AI API request errors.
 *
 * This includes authentication failures, rate limiting,
 * malformed requests, and API-specific errors.
 *
 * @since 0.2.0
 */
class RequestException extends RuntimeException implements AiClientExceptionInterface
{
}
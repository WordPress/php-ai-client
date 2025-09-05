<?php

declare(strict_types=1);

namespace WordPress\AiClient\Exceptions;

use Throwable;

/**
 * Base interface for all AI Client exceptions.
 *
 * This interface allows callers to catch all AI Client specific exceptions
 * with a single catch statement.
 *
 * @since n.e.x.t
 */
interface AiClientExceptionInterface extends Throwable
{
}

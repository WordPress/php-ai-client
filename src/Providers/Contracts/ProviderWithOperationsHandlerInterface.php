<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Contracts;

/**
 * Interface for providers that support operations handlers.
 *
 * Providers implementing this interface can return an operations handler
 * for managing long-running operations across all their models.
 *
 * @since n.e.x.t
 */
interface ProviderWithOperationsHandlerInterface
{
    /**
     * Gets the operations handler for this provider.
     *
     * @since n.e.x.t
     *
     * @return ProviderOperationsHandlerInterface The operations handler.
     */
    public static function operationsHandler(): ProviderOperationsHandlerInterface;
}

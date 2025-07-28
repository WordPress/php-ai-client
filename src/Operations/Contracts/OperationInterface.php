<?php

declare(strict_types=1);

namespace WordPress\AiClient\Operations\Contracts;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Common\Contracts\WithJsonSerialization;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;

/**
 * Interface for AI operations.
 *
 * Operations represent long-running AI tasks that may not complete immediately.
 * They provide a way to track the progress and retrieve results asynchronously.
 *
 * @since n.e.x.t
 */
interface OperationInterface extends WithJsonSchemaInterface, WithJsonSerialization
{
    /**
     * Gets the operation ID.
     *
     * @since n.e.x.t
     *
     * @return string The unique operation identifier.
     */
    public function getId(): string;

    /**
     * Gets the current state of the operation.
     *
     * @since n.e.x.t
     *
     * @return OperationStateEnum The operation state.
     */
    public function getState(): OperationStateEnum;
}

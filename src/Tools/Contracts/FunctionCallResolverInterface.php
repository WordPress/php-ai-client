<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\Contracts;

use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Interface for resolving function calls requested by a model.
 *
 * A function call resolver executes the function calls that a model requests
 * during generation and returns the results as function responses. It enables
 * the automatic function call resolution loop of the
 * {@see \WordPress\AiClient\Builders\PromptBuilder}: each round the resolver
 * executes the requested calls, and the responses are appended to the
 * conversation for a follow-up request.
 *
 * Resolution is split into two steps so that a round is only executed when
 * every requested call can be handled:
 * - {@see self::canResolve()} checks whether a call can be handled. It must be
 *   free of side effects, as it is invoked for every call in a round before
 *   any call is executed.
 * - {@see self::resolve()} executes a call and returns its response. Execution
 *   errors should be returned as part of the function response, so the model
 *   can process them, rather than thrown.
 *
 * @since n.e.x.t
 */
interface FunctionCallResolverInterface
{
    /**
     * Checks whether the given function call can be resolved.
     *
     * This method must not have side effects. It is invoked for every function
     * call in a model response before any of them is executed. If any call in
     * a response cannot be resolved, none of them are executed and the
     * resolution loop stops, handing the response back to the caller.
     *
     * @since n.e.x.t
     *
     * @param FunctionCall $functionCall The function call to check.
     * @return bool True if the function call can be resolved.
     */
    public function canResolve(FunctionCall $functionCall): bool;

    /**
     * Resolves the given function call by executing it.
     *
     * Only called for function calls that {@see self::canResolve()} reported
     * as resolvable. Execution errors should be encoded in the returned
     * function response, so the model can react to them.
     *
     * @since n.e.x.t
     *
     * @param FunctionCall $functionCall The function call to resolve.
     * @return FunctionResponse The response for the function call.
     */
    public function resolve(FunctionCall $functionCall): FunctionResponse;
}

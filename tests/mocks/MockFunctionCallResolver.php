<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Tools\Contracts\FunctionCallResolverInterface;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Mock function call resolver for testing.
 *
 * Records all checked and resolved calls. Behavior can be customized through
 * optional callbacks; by default every call is resolvable and resolves to a
 * simple success response.
 */
class MockFunctionCallResolver implements FunctionCallResolverInterface
{
    /**
     * @var callable|null Callback deciding whether a call can be resolved.
     */
    private $canResolveCallback;

    /**
     * @var callable|null Callback producing the response for a call.
     */
    private $resolveCallback;

    /**
     * @var list<FunctionCall> The calls passed to canResolve().
     */
    public array $checkedCalls = [];

    /**
     * @var list<FunctionCall> The calls passed to resolve().
     */
    public array $resolvedCalls = [];

    /**
     * @param callable|null $canResolveCallback Optional callback receiving a FunctionCall and returning a bool.
     * @param callable|null $resolveCallback Optional callback receiving a FunctionCall and returning a
     *                                       FunctionResponse.
     */
    public function __construct(?callable $canResolveCallback = null, ?callable $resolveCallback = null)
    {
        $this->canResolveCallback = $canResolveCallback;
        $this->resolveCallback = $resolveCallback;
    }

    /**
     * {@inheritDoc}
     */
    public function canResolve(FunctionCall $functionCall): bool
    {
        $this->checkedCalls[] = $functionCall;

        if ($this->canResolveCallback !== null) {
            return (bool) ($this->canResolveCallback)($functionCall);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(FunctionCall $functionCall): FunctionResponse
    {
        $this->resolvedCalls[] = $functionCall;

        if ($this->resolveCallback !== null) {
            return ($this->resolveCallback)($functionCall);
        }

        return new FunctionResponse(
            $functionCall->getId(),
            $functionCall->getName(),
            ['status' => 'ok']
        );
    }
}

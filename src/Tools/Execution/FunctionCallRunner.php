<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\Execution;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Tools\Contracts\FunctionCallResolverInterface;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Runs a bounded loop of generation and tool calls.
 *
 * @since n.e.x.t
 * @internal
 */
final class FunctionCallRunner
{
    private const KEY_RESOLUTION = 'functionCallResolution';
    private const COMPLETED = 'completed';
    private const UNRESOLVED = 'unresolvedFunctionCalls';
    private const INCOMPLETE = 'incompleteFunctionCalls';
    private const MAX_ITERATIONS = 'maxIterations';

    /**
     * Runs generation and resolves complete tool calls from a single candidate.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The initial conversation.
     * @param FunctionCallResolverInterface $resolver The tool executor.
     * @param int $maxIterations The maximum number of tool rounds.
     * @param callable(list<Message>): GenerativeAiResult $generate Generates one response.
     * @return GenerativeAiResult The final result with total usage and the conversation.
     */
    public function run(
        array $messages,
        FunctionCallResolverInterface $resolver,
        int $maxIterations,
        callable $generate
    ): GenerativeAiResult {
        $result = $generate($messages);
        $usage = $result->getTokenUsage();
        $rounds = 0;
        $resolved = [];
        $stopReason = self::COMPLETED;

        while (true) {
            if (count($result->getCandidates()) !== 1) {
                throw new InvalidArgumentException('Automatic function call resolution requires one candidate.');
            }
            $candidate = $result->getCandidates()[0];
            $message = $candidate->getMessage();
            $messages[] = $message;
            $calls = $this->getFunctionCalls($message);
            if ($calls === []) {
                break;
            }
            if ($rounds >= $maxIterations) {
                $stopReason = self::MAX_ITERATIONS;
                break;
            }
            if (!$candidate->getFinishReason()->isToolCalls() || !$this->haveNames($calls)) {
                $stopReason = self::INCOMPLETE;
                break;
            }
            // Check support before executing any call. Execution itself can still fail.
            foreach ($calls as $call) {
                if (!$resolver->canResolve($call)) {
                    $stopReason = self::UNRESOLVED;
                    break 2;
                }
            }

            $parts = [];
            foreach ($calls as $call) {
                $response = $resolver->resolve($call);
                $this->validateResponse($call, $response);
                $parts[] = new MessagePart($response);
                $resolved[] = ['id' => $call->getId(), 'name' => $call->getName()];
            }
            $messages[] = new UserMessage($parts);
            $rounds++;
            $result = $generate($messages);
            $usage = $this->addUsage($usage, $result->getTokenUsage());
        }

        $data = $result->getAdditionalData();
        $data[self::KEY_RESOLUTION] = [
            'rounds' => $rounds,
            'stopReason' => $stopReason,
            'resolvedCalls' => $resolved,
            'messages' => array_map(static fn(Message $message): array => $message->toArray(), $messages),
        ];
        return new GenerativeAiResult(
            $result->getId(),
            $result->getCandidates(),
            $usage,
            $result->getProviderMetadata(),
            $result->getModelMetadata(),
            $data
        );
    }

    /**
     * Gets the tool calls in a message.
     *
     * @since n.e.x.t
     *
     * @param Message $message The model message.
     * @return list<FunctionCall> The tool calls.
     */
    private function getFunctionCalls(Message $message): array
    {
        $calls = [];
        foreach ($message->getParts() as $part) {
            $call = $part->getFunctionCall();
            if ($call !== null) {
                $calls[] = $call;
            }
        }
        return $calls;
    }

    /**
     * Checks that every call has a name.
     *
     * @since n.e.x.t
     *
     * @param list<FunctionCall> $calls The calls to check.
     * @return bool Whether every call has a name.
     */
    private function haveNames(array $calls): bool
    {
        foreach ($calls as $call) {
            if (trim($call->getName() ?? '') === '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks that a response identifies the call it answers.
     *
     * @since n.e.x.t
     *
     * @param FunctionCall $call The requested call.
     * @param FunctionResponse $response The resolver response.
     * @return void
     */
    private function validateResponse(FunctionCall $call, FunctionResponse $response): void
    {
        if (
            ($call->getId() !== null && $response->getId() !== $call->getId()) ||
            ($call->getId() === null && $response->getName() !== $call->getName()) ||
            ($response->getName() !== null && $response->getName() !== $call->getName())
        ) {
            throw new InvalidArgumentException('The function response does not match the requested call.');
        }
    }

    /**
     * Adds usage from a generation round.
     *
     * @since n.e.x.t
     *
     * @param TokenUsage $total The total so far.
     * @param TokenUsage $next The next round's usage.
     * @return TokenUsage The combined usage.
     */
    private function addUsage(TokenUsage $total, TokenUsage $next): TokenUsage
    {
        $thoughts = $total->getThoughtTokens() === null && $next->getThoughtTokens() === null
            ? null : (int) $total->getThoughtTokens() + (int) $next->getThoughtTokens();
        return new TokenUsage(
            $total->getPromptTokens() + $next->getPromptTokens(),
            $total->getCompletionTokens() + $next->getCompletionTokens(),
            $total->getTotalTokens() + $next->getTotalTokens(),
            $thoughts
        );
    }
}

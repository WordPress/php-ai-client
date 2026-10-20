<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Tools\Execution;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tests\mocks\MockFunctionCallResolver;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;
use WordPress\AiClient\Tools\Execution\FunctionCallRunner;

/**
 * @covers \WordPress\AiClient\Tools\Execution\FunctionCallRunner
 */
class FunctionCallRunnerTest extends TestCase
{
    use MockModelCreationTrait;

    /** @var int The number of model requests. */
    private int $requestCount = 0;

    /**
     * Runs the loop with a scripted model.
     *
     * @param ModelInterface&TextGenerationModelInterface $model The model to call.
     * @param MockFunctionCallResolver $resolver The resolver to use.
     * @return GenerativeAiResult The final result.
     */
    private function runModel(ModelInterface $model, MockFunctionCallResolver $resolver): GenerativeAiResult
    {
        return (new FunctionCallRunner())->run(
            [new UserMessage([new MessagePart('Hello')])],
            $resolver,
            5,
            function (array $messages) use ($model): GenerativeAiResult {
                $this->requestCount++;
                return $model->generateTextResult($messages);
            }
        );
    }

    /**
     * Tests that a truncated function call response is not executed.
     *
     * @return void
     */
    public function testDoesNotResolveFunctionCallsFromTruncatedResponse(): void
    {
        $truncatedResult = $this->createTestResultWithMessage(
            new ModelMessage([
                new MessagePart(new FunctionCall('call-1', 'get_weather', ['city' => 'Ber']))
            ]),
            null,
            FinishReasonEnum::length()
        );
        $model = $this->createScriptedTextGenerationModel([
            $truncatedResult,
            $this->createTestResult('Never reached'),
        ]);
        $resolver = new MockFunctionCallResolver();

        $result = $this->runModel($model, $resolver);

        $this->assertSame([], $resolver->checkedCalls);
        $this->assertSame([], $resolver->resolvedCalls);
        $this->assertTrue($result->toMessage()->getParts()[0]->getType()->isFunctionCall());
        $this->assertSame(1, $this->requestCount);

        $resolution = $result->getAdditionalData()[FunctionCallRunner::KEY_RESOLUTION];
        $this->assertSame(0, $resolution['rounds']);
        $this->assertSame(FunctionCallRunner::INCOMPLETE, $resolution['stopReason']);
        $this->assertSame([], $resolution['resolvedCalls']);
    }

    /**
     * Tests that one incomplete call prevents a parallel batch from executing.
     *
     * @return void
     */
    public function testDoesNotResolveParallelCallsWhenOneHasNoName(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->toolResult(
                new FunctionCall('call-1', 'get_weather', []),
                new FunctionCall('call-2', null, [])
            ),
            $this->createTestResult('Never reached'),
        ]);
        $resolver = new MockFunctionCallResolver();

        $result = $this->runModel($model, $resolver);

        $this->assertSame([], $resolver->checkedCalls);
        $this->assertSame([], $resolver->resolvedCalls);

        $resolution = $result->getAdditionalData()[FunctionCallRunner::KEY_RESOLUTION];
        $this->assertSame(0, $resolution['rounds']);
        $this->assertSame(FunctionCallRunner::INCOMPLETE, $resolution['stopReason']);
        $this->assertSame([], $resolution['resolvedCalls']);
    }

    /**
     * Tests that a completed name-only function call remains resolvable.
     *
     * @return void
     */
    public function testResolvesCompletedFunctionCallWithoutId(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->toolResult(new FunctionCall(null, 'get_weather', [])),
            $this->createTestResult('Final answer'),
        ]);
        $resolver = new MockFunctionCallResolver();

        $result = $this->runModel($model, $resolver);

        $this->assertSame('Final answer', $result->toText());
        $this->assertCount(1, $resolver->checkedCalls);
        $this->assertCount(1, $resolver->resolvedCalls);
        $this->assertSame('get_weather', $resolver->resolvedCalls[0]->getName());

        $resolution = $result->getAdditionalData()[FunctionCallRunner::KEY_RESOLUTION];
        $this->assertSame(1, $resolution['rounds']);
        $this->assertSame(FunctionCallRunner::COMPLETED, $resolution['stopReason']);
    }

    /**
     * Tests that token usage is aggregated across all rounds.
     *
     * @return void
     */
    public function testAggregatesTokenUsageAcrossRounds(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->createTestResultWithMessage(
                new ModelMessage([new MessagePart(new FunctionCall('call-1', 'get_weather', []))]),
                new TokenUsage(1, 2, 3),
                FinishReasonEnum::toolCalls()
            ),
            $this->createTestResultWithMessage(
                new ModelMessage([new MessagePart('Final answer')]),
                new TokenUsage(10, 20, 30)
            ),
        ]);
        $resolver = new MockFunctionCallResolver();

        $result = $this->runModel($model, $resolver);

        $tokenUsage = $result->getTokenUsage();
        $this->assertSame(11, $tokenUsage->getPromptTokens());
        $this->assertSame(22, $tokenUsage->getCompletionTokens());
        $this->assertSame(33, $tokenUsage->getTotalTokens());
        $this->assertNull($tokenUsage->getThoughtTokens());
    }

    /**
     * Tests that a missing thought token count in one round counts as zero.
     *
     * @return void
     */
    public function testAggregatesThoughtTokensWhenOnlySomeRoundsReportThem(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->createTestResultWithMessage(
                new ModelMessage([new MessagePart(new FunctionCall('call-1', 'get_weather', []))]),
                new TokenUsage(1, 2, 3),
                FinishReasonEnum::toolCalls()
            ),
            $this->createTestResultWithMessage(
                new ModelMessage([new MessagePart('Final answer')]),
                new TokenUsage(10, 20, 30, 5)
            ),
        ]);

        $result = $this->runModel($model, new MockFunctionCallResolver());

        $this->assertSame(5, $result->getTokenUsage()->getThoughtTokens());
    }

    /**
     * Tests that a response without function calls completes with zero rounds.
     *
     * @return void
     */
    public function testCompletesWithZeroRoundsWithoutFunctionCalls(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->createTestResult('Immediate answer'),
        ]);
        $resolver = new MockFunctionCallResolver();

        $result = $this->runModel($model, $resolver);

        $this->assertSame('Immediate answer', $result->toText());

        $resolution = $result->getAdditionalData()[FunctionCallRunner::KEY_RESOLUTION];
        $this->assertSame(0, $resolution['rounds']);
        $this->assertSame(FunctionCallRunner::COMPLETED, $resolution['stopReason']);
        $this->assertSame([], $resolution['resolvedCalls']);
        $this->assertCount(2, $resolution['messages']);
    }

    /**
     * Creates a tool request, using two calls by default.
     *
     * @param FunctionCall ...$calls The calls to request.
     *
     * @return GenerativeAiResult The tool request.
     */
    private function toolResult(FunctionCall ...$calls): GenerativeAiResult
    {
        $calls = $calls ?: [new FunctionCall('first', 'create', []), new FunctionCall('second', 'publish', [])];
        $parts = array_map(static fn(FunctionCall $call): MessagePart => new MessagePart($call), $calls);
        return $this->createTestResultWithMessage(new ModelMessage($parts), null, FinishReasonEnum::toolCalls());
    }

    /**
     * Tests that tool errors pass to the caller without retrying.
     *
     * @return void
     */
    public function testToolFailurePropagates(): void
    {
        $error = new RuntimeException('Publish failed');
        $result = $this->toolResult();
        $resolve = static function (FunctionCall $call) use ($error): FunctionResponse {
            if ($call->getId() === 'second') {
                throw $error;
            }
            return new FunctionResponse($call->getId(), $call->getName(), 'created');
        };
        $resolver = new MockFunctionCallResolver(null, $resolve);
        try {
            $messages = [new UserMessage([new MessagePart('Create and publish')])];
            (new FunctionCallRunner())->run($messages, $resolver, 2, fn() => $result);
            $this->fail('Expected an execution failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame($error, $exception);
            $this->assertCount(2, $resolver->resolvedCalls);
        }
    }

    /**
     * Tests that follow-up errors pass to the caller without retrying.
     *
     * @return void
     */
    public function testFollowUpFailurePropagates(): void
    {
        $result = $this->toolResult();
        $resolver = new MockFunctionCallResolver();
        $requests = 0;
        $error = new RuntimeException('Request failed');
        $generate = static function () use (&$requests, $result, $error): GenerativeAiResult {
            if (++$requests === 2) {
                throw $error;
            }
            return $result;
        };
        try {
            $messages = [new UserMessage([new MessagePart('Create and publish')])];
            (new FunctionCallRunner())->run($messages, $resolver, 2, $generate);
            $this->fail('Expected a follow-up failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame($error, $exception);
            $this->assertSame(2, $requests);
            $this->assertCount(2, $resolver->resolvedCalls);
        }
    }

    /**
     * Tests that a mismatched response is never sent to the model.
     *
     * @return void
     */
    public function testRejectsMismatchedResponse(): void
    {
        $result = $this->toolResult();
        $cases = [
            [$result, new FunctionResponse('wrong', 'create', [])],
            [$result, new FunctionResponse('first', 'wrong', [])],
            [$this->toolResult(new FunctionCall(null, 'create', [])), new FunctionResponse(null, 'wrong', [])],
        ];
        foreach ($cases as [$result, $response]) {
            $resolver = new MockFunctionCallResolver(null, static fn() => $response);
            $requests = 0;
            try {
                (new FunctionCallRunner())->run([], $resolver, 2, static function () use (&$requests, $result) {
                    $requests++;
                    return $result;
                });
                $this->fail('Expected a mismatched response failure.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('The function response does not match the requested call.', $exception->getMessage());
                $this->assertCount(1, $resolver->resolvedCalls);
                $this->assertSame(1, $requests);
            }
        }
    }

    /**
     * Tests that a response can identify a call by its ID alone.
     *
     * @return void
     */
    public function testAcceptsResponseWithIdOnly(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->toolResult(new FunctionCall('a', 'create', [])),
            $this->createTestResult('Done'),
        ]);
        $resolver = new MockFunctionCallResolver(null, static fn() => new FunctionResponse('a', null, 'created'));
        $this->assertSame('Done', $this->runModel($model, $resolver)->toText());
        $this->assertSame(2, $this->requestCount);
    }

    /**
     * Tests that extra candidates cannot silently lose tool calls.
     *
     * @return void
     */
    public function testRejectsMultipleCandidatesBeforeExecutingTools(): void
    {
        $result = $this->toolResult();
        $multiple = new GenerativeAiResult(
            $result->getId(),
            [$result->getCandidates()[0], $result->getCandidates()[0]],
            $result->getTokenUsage(),
            $result->getProviderMetadata(),
            $result->getModelMetadata()
        );
        $resolver = new MockFunctionCallResolver();
        try {
            (new FunctionCallRunner())->run([], $resolver, 2, static fn() => $multiple);
            $this->fail('Expected a candidate count failure.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame([], $resolver->resolvedCalls);
            $this->assertSame([], $resolver->checkedCalls);
        }
    }
}

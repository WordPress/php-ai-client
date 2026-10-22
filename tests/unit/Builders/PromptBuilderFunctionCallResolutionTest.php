<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Events\AfterGenerateResultEvent;
use WordPress\AiClient\Events\BeforeGenerateResultEvent;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tests\mocks\MockEventDispatcher;
use WordPress\AiClient\Tests\mocks\MockFunctionCallResolver;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;
use WordPress\AiClient\Tools\DTO\FunctionCall;

/**
 * Tests for the automatic function call resolution loop in PromptBuilder.
 *
 * @covers \WordPress\AiClient\Builders\PromptBuilder
 */
class PromptBuilderFunctionCallResolutionTest extends TestCase
{
    use MockModelCreationTrait;

    /**
     * @var ProviderRegistry
     */
    private ProviderRegistry $registry;

    /**
     * @var MockEventDispatcher
     */
    private MockEventDispatcher $dispatcher;

    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->registry = $this->createRegistryWithMockProvider();
        $this->dispatcher = new MockEventDispatcher();
    }

    /**
     * Creates a result whose message requests the given function calls.
     *
     * @param FunctionCall ...$functionCalls The function calls to include.
     * @return GenerativeAiResult The result.
     */
    private function createFunctionCallResult(FunctionCall ...$functionCalls): GenerativeAiResult
    {
        $parts = [];
        foreach ($functionCalls as $functionCall) {
            $parts[] = new MessagePart($functionCall);
        }

        return $this->createTestResultWithMessage(
            new ModelMessage($parts),
            null,
            FinishReasonEnum::toolCalls()
        );
    }

    /**
     * Creates a prompt builder with the event dispatcher wired.
     *
     * @param string $prompt The prompt text.
     * @return PromptBuilder The builder.
     */
    private function createBuilder(string $prompt = 'Hello, world!'): PromptBuilder
    {
        return new PromptBuilder($this->registry, $prompt, $this->dispatcher);
    }

    /**
     * Tests that a function call is resolved and the final answer returned.
     *
     * @return void
     */
    public function testResolvesFunctionCallsAndReturnsFinalResult(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->createFunctionCallResult(new FunctionCall('call-1', 'get_weather', ['city' => 'Berlin'])),
            $this->createTestResult('Final answer'),
        ]);
        $resolver = new MockFunctionCallResolver();

        $result = $this->createBuilder()
            ->usingModel($model)
            ->usingFunctionCallResolver($resolver)
            ->generateTextResult();

        $this->assertSame('Final answer', $result->toText());
        $this->assertCount(1, $resolver->resolvedCalls);
        $this->assertSame('get_weather', $resolver->resolvedCalls[0]->getName());

        $resolution = $result->getAdditionalData()['functionCallResolution'];
        $this->assertSame(1, $resolution['rounds']);
        $this->assertSame('completed', $resolution['stopReason']);
        $this->assertSame([['id' => 'call-1', 'name' => 'get_weather']], $resolution['resolvedCalls']);
    }

    /**
     * Tests the ordering and roles of the transcript sent in follow-up rounds.
     *
     * @return void
     */
    public function testFollowUpRequestContainsFullTranscript(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->createFunctionCallResult(new FunctionCall('call-1', 'get_weather', ['city' => 'Berlin'])),
            $this->createTestResult('Final answer'),
        ]);
        $resolver = new MockFunctionCallResolver();

        $result = $this->createBuilder()
            ->usingModel($model)
            ->usingFunctionCallResolver($resolver)
            ->generateTextResult();

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class);
        $this->assertCount(2, $beforeEvents);

        $followUpMessages = $beforeEvents[1]->getMessages();
        $this->assertCount(3, $followUpMessages);
        $this->assertTrue($followUpMessages[0]->getRole()->isUser());
        $this->assertTrue($followUpMessages[1]->getRole()->isModel());
        $this->assertTrue($followUpMessages[2]->getRole()->isUser());

        // The model message carries the function call, the user message the response.
        $this->assertTrue($followUpMessages[1]->getParts()[0]->getType()->isFunctionCall());
        $responsePart = $followUpMessages[2]->getParts()[0];
        $this->assertTrue($responsePart->getType()->isFunctionResponse());
        $functionResponse = $responsePart->getFunctionResponse();
        $this->assertNotNull($functionResponse);
        $this->assertSame('call-1', $functionResponse->getId());
        $this->assertSame(['status' => 'ok'], $functionResponse->getResponse());

        // The exposed transcript also contains the final model response.
        $resolution = $result->getAdditionalData()['functionCallResolution'];
        $this->assertCount(4, $resolution['messages']);
    }

    /**
     * Tests that multiple function calls in one response are resolved into one message.
     *
     * @return void
     */
    public function testResolvesMultipleFunctionCallsInOneResponse(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->createFunctionCallResult(
                new FunctionCall('call-1', 'get_weather', ['city' => 'Berlin']),
                new FunctionCall('call-2', 'get_time', ['city' => 'Berlin'])
            ),
            $this->createTestResult('Final answer'),
        ]);
        $resolver = new MockFunctionCallResolver();

        $this->createBuilder()
            ->usingModel($model)
            ->usingFunctionCallResolver($resolver)
            ->generateTextResult();

        $this->assertCount(2, $resolver->resolvedCalls);

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class);
        $responseMessage = $beforeEvents[1]->getMessages()[2];
        $this->assertCount(2, $responseMessage->getParts());
        $this->assertTrue($responseMessage->getParts()[0]->getType()->isFunctionResponse());
        $this->assertTrue($responseMessage->getParts()[1]->getType()->isFunctionResponse());
    }

    /**
     * Tests that the loop stops without executing any call when one call cannot be resolved.
     *
     * @return void
     */
    public function testStopsWithoutExecutingWhenACallCannotBeResolved(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->createFunctionCallResult(
                new FunctionCall('call-1', 'known_function', []),
                new FunctionCall('call-2', 'unknown_function', [])
            ),
            $this->createTestResult('Never reached'),
        ]);
        $resolver = new MockFunctionCallResolver(
            static function (FunctionCall $functionCall): bool {
                return $functionCall->getName() === 'known_function';
            }
        );

        $result = $this->createBuilder()
            ->usingModel($model)
            ->usingFunctionCallResolver($resolver)
            ->generateTextResult();

        // No call was executed, and the function call response is handed back.
        $this->assertSame([], $resolver->resolvedCalls);
        $this->assertTrue($result->toMessage()->getParts()[0]->getType()->isFunctionCall());
        $this->assertCount(1, $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class));

        $resolution = $result->getAdditionalData()['functionCallResolution'];
        $this->assertSame(0, $resolution['rounds']);
        $this->assertSame('unresolvedFunctionCalls', $resolution['stopReason']);
        $this->assertSame([], $resolution['resolvedCalls']);
    }

    /**
     * Tests that the loop stops after the maximum number of iterations.
     *
     * @return void
     */
    public function testStopsAtMaxIterations(): void
    {
        // The model requests a function call on every round.
        $model = $this->createScriptedTextGenerationModel([
            $this->createFunctionCallResult(new FunctionCall('call-1', 'get_weather', [])),
        ], null, true);
        $resolver = new MockFunctionCallResolver();

        $result = $this->createBuilder()
            ->usingModel($model)
            ->usingFunctionCallResolver($resolver)
            ->usingMaxFunctionCallIterations(2)
            ->generateTextResult();

        $resolution = $result->getAdditionalData()['functionCallResolution'];
        $this->assertSame(2, $resolution['rounds']);
        $this->assertSame('maxIterations', $resolution['stopReason']);
        // Calls from a response beyond the iteration limit must not be inspected or resolved.
        $this->assertCount(2, $resolver->checkedCalls);
        $this->assertCount(2, $resolver->resolvedCalls);
        // Initial request plus one follow-up per round.
        $this->assertCount(3, $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class));
    }

    /**
     * Tests that automatic resolution requires chat history support.
     *
     * @return void
     */
    public function testResolverRequiresChatHistorySupport(): void
    {
        $metadata = new ModelMetadata(
            'text-only-model',
            'Text-only Model',
            [CapabilityEnum::textGeneration()],
            [new SupportedOption(OptionEnum::inputModalities())]
        );
        $model = $this->createMockTextGenerationModel($this->createTestResult('Answer'), $metadata);
        $builder = $this->createBuilder()->usingModel($model);

        $this->assertTrue($builder->isSupportedForTextGeneration());

        $builder->usingFunctionCallResolver(new MockFunctionCallResolver());

        $this->assertFalse($builder->isSupportedForTextGeneration());
    }

    /**
     * Tests that automatic resolution accepts a model with chat history support.
     *
     * @return void
     */
    public function testResolverSupportsModelWithChatHistory(): void
    {
        $metadata = new ModelMetadata(
            'chat-model',
            'Chat Model',
            [CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory()],
            [new SupportedOption(OptionEnum::inputModalities())]
        );
        $model = $this->createMockTextGenerationModel($this->createTestResult('Answer'), $metadata);

        $builder = $this->createBuilder()
            ->usingModel($model)
            ->usingFunctionCallResolver(new MockFunctionCallResolver());

        $this->assertTrue($builder->isSupportedForTextGeneration());
    }

    /**
     * Tests that multiple candidates are rejected before making a request.
     *
     * @return void
     */
    public function testRejectsMultipleCandidatesBeforeRequest(): void
    {
        $builder = $this->createBuilder()
            ->usingModel($this->createScriptedTextGenerationModel([$this->createTestResult()]))
            ->usingCandidateCount(2)
            ->usingFunctionCallResolver(new MockFunctionCallResolver());

        try {
            $builder->generateTextResult();
            $this->fail('Expected a candidate count error.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame([], $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class));
        }
    }

    /**
     * Tests that an invalid candidate count skips discovery and reports unsupported.
     *
     * @return void
     */
    public function testCandidateCountIsCheckedBeforeDiscovery(): void
    {
        $registry = $this->createMock(ProviderRegistry::class);
        $registry->expects($this->never())->method('findModelsMetadataForSupport');
        $builder = (new PromptBuilder($registry, 'Hello'))
            ->usingCandidateCount(2)
            ->usingFunctionCallResolver(new MockFunctionCallResolver());

        $this->assertFalse($builder->isSupportedForTextGeneration());
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Automatic function call resolution requires one candidate.');
        $builder->generateTextResult();
    }

    /**
     * Tests events across two tool rounds and reuse of the original prompt.
     *
     * @return void
     */
    public function testMultipleRoundsDispatchEventsAndPreservePrompt(): void
    {
        $results = [
            $this->createFunctionCallResult(new FunctionCall('a', 'first', [])),
            $this->createFunctionCallResult(new FunctionCall('b', 'second', [])),
            $this->createTestResult('Done'),
            $this->createTestResult('New answer'),
        ];
        $resolver = new MockFunctionCallResolver();
        $builder = $this->createBuilder('Original prompt')
            ->usingModel($this->createScriptedTextGenerationModel($results))
            ->usingFunctionCallResolver($resolver);

        $result = $builder->generateTextResult();
        $this->assertSame('Done', $result->toText());
        $this->assertSame(2, $result->getAdditionalData()['functionCallResolution']['rounds']);
        $this->assertCount(2, $resolver->resolvedCalls);
        $events = $this->dispatcher->getDispatchedEvents();
        $this->assertCount(6, $events);
        for ($round = 0; $round < 3; $round++) {
            $this->assertInstanceOf(BeforeGenerateResultEvent::class, $events[2 * $round]);
            $this->assertInstanceOf(AfterGenerateResultEvent::class, $events[2 * $round + 1]);
            $this->assertSame($results[$round], $events[2 * $round + 1]->getResult());
            $this->assertCount(1 + 2 * $round, $events[2 * $round]->getMessages());
        }

        $this->assertSame('New answer', $builder->generateText());
        $before = $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class);
        $this->assertCount(1, $before[3]->getMessages());
        $this->assertSame('Original prompt', $before[3]->getMessages()[0]->getParts()[0]->getText());
        $this->assertCount(2, $resolver->resolvedCalls);
    }

    /**
     * Tests that an invalid maximum number of iterations is rejected.
     *
     * @return void
     */
    public function testRejectsInvalidMaxIterations(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createBuilder()->usingMaxFunctionCallIterations(0);
    }

    /**
     * Tests that the resolver is ignored for non-text generation.
     *
     * @return void
     */
    public function testResolverIsIgnoredForImageGeneration(): void
    {
        $model = $this->createMockImageGenerationModel($this->createTestResult('image'));
        $resolver = new MockFunctionCallResolver();

        $result = $this->createBuilder()
            ->usingModel($model)
            ->usingFunctionCallResolver($resolver)
            ->generateImageResult();

        $this->assertArrayNotHasKey(
            'functionCallResolution',
            $result->getAdditionalData()
        );
        $this->assertSame([], $resolver->checkedCalls);
    }

    /**
     * Tests that withMessages() appends full messages to the conversation.
     *
     * @return void
     */
    public function testWithMessagesAppendsToConversation(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->createTestResult('Answer'),
        ]);

        $this->createBuilder('First question')
            ->withMessages(
                new ModelMessage([new MessagePart('First answer')]),
                new UserMessage([new MessagePart('Follow-up question')])
            )
            ->usingModel($model)
            ->generateTextResult();

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class);
        $sentMessages = $beforeEvents[0]->getMessages();

        $this->assertCount(3, $sentMessages);
        $this->assertTrue($sentMessages[0]->getRole()->isUser());
        $this->assertSame('First question', $sentMessages[0]->getParts()[0]->getText());
        $this->assertTrue($sentMessages[1]->getRole()->isModel());
        $this->assertSame('First answer', $sentMessages[1]->getParts()[0]->getText());
        $this->assertTrue($sentMessages[2]->getRole()->isUser());
        $this->assertSame('Follow-up question', $sentMessages[2]->getParts()[0]->getText());
    }

    /**
     * Tests that withMessages() appends after the current message while withHistory() prepends.
     *
     * @return void
     */
    public function testWithMessagesAndWithHistoryOrdering(): void
    {
        $model = $this->createScriptedTextGenerationModel([
            $this->createTestResult('Answer'),
        ]);

        $this->createBuilder('Current question')
            ->withHistory(
                new UserMessage([new MessagePart('Historical question')]),
                new ModelMessage([new MessagePart('Historical answer')])
            )
            ->withMessages(
                new ModelMessage([new MessagePart('Appended answer')]),
                new UserMessage([new MessagePart('Appended question')])
            )
            ->usingModel($model)
            ->generateTextResult();

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class);
        $sentMessages = $beforeEvents[0]->getMessages();

        $this->assertCount(5, $sentMessages);
        $this->assertSame('Historical question', $sentMessages[0]->getParts()[0]->getText());
        $this->assertSame('Historical answer', $sentMessages[1]->getParts()[0]->getText());
        $this->assertSame('Current question', $sentMessages[2]->getParts()[0]->getText());
        $this->assertSame('Appended answer', $sentMessages[3]->getParts()[0]->getText());
        $this->assertSame('Appended question', $sentMessages[4]->getParts()[0]->getText());
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Events\AfterGenerateResultEvent;
use WordPress\AiClient\Events\BeforeGenerateResultEvent;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Results\ValueObjects\GenerativeAiResultChunk;
use WordPress\AiClient\Tests\mocks\MockEventDispatcher;
use WordPress\AiClient\Tests\mocks\MockProvider;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * Tests for event dispatching in PromptBuilder.
 *
 * @covers \WordPress\AiClient\Builders\PromptBuilder
 */
class PromptBuilderEventDispatchingTest extends TestCase
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
        $this->registry = new ProviderRegistry();
        $this->registry->registerProvider(MockProvider::class);
        $this->dispatcher = new MockEventDispatcher();
    }

    /**
     * Tests that events are dispatched when a dispatcher is injected.
     *
     * @return void
     */
    public function testEventsAreDispatchedWhenDispatcherIsInjected(): void
    {
        $result = $this->createTestResult();
        $model = $this->createMockTextGenerationModel($result);

        $builder = new PromptBuilder($this->registry, 'Hello, world!', $this->dispatcher);
        $builder->usingModel($model);

        $builder->generateTextResult();

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class);
        $afterEvents = $this->dispatcher->getDispatchedEventsOfType(AfterGenerateResultEvent::class);

        $this->assertCount(1, $beforeEvents);
        $this->assertCount(1, $afterEvents);
    }

    /**
     * Tests that no events are dispatched when dispatcher is not set.
     *
     * @return void
     */
    public function testNoEventsDispatchedWithoutDispatcher(): void
    {
        $result = $this->createTestResult();
        $model = $this->createMockTextGenerationModel($result);

        $builder = new PromptBuilder($this->registry, 'Hello, world!');
        $builder->usingModel($model);

        $builder->generateTextResult();

        // No dispatcher set, so no events should be dispatched
        $this->assertCount(0, $this->dispatcher->getDispatchedEvents());
    }

    /**
     * Tests that BeforeGenerateResultEvent contains correct data.
     *
     * @return void
     */
    public function testBeforeGenerateResultEventContainsCorrectData(): void
    {
        $result = $this->createTestResult();
        $model = $this->createMockTextGenerationModel($result);

        $builder = new PromptBuilder($this->registry, 'Test prompt', $this->dispatcher);
        $builder->usingModel($model);

        $builder->generateTextResult();

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class);
        $this->assertCount(1, $beforeEvents);

        $event = $beforeEvents[0];
        $this->assertCount(1, $event->getMessages());
        $this->assertSame($model, $event->getModel());
        $this->assertEquals(CapabilityEnum::textGeneration(), $event->getCapability());
    }

    /**
     * Tests that AfterGenerateResultEvent contains correct data.
     *
     * @return void
     */
    public function testAfterGenerateResultEventContainsCorrectData(): void
    {
        $result = $this->createTestResult('Generated response');
        $model = $this->createMockTextGenerationModel($result);

        $builder = new PromptBuilder($this->registry, 'Test prompt', $this->dispatcher);
        $builder->usingModel($model);

        $returnedResult = $builder->generateTextResult();

        $afterEvents = $this->dispatcher->getDispatchedEventsOfType(AfterGenerateResultEvent::class);
        $this->assertCount(1, $afterEvents);

        $event = $afterEvents[0];
        $this->assertCount(1, $event->getMessages());
        $this->assertSame($model, $event->getModel());
        $this->assertEquals(CapabilityEnum::textGeneration(), $event->getCapability());
        $this->assertSame($result, $event->getResult());
        $this->assertSame($returnedResult, $event->getResult());
    }

    /**
     * Tests that events are dispatched in correct order.
     *
     * @return void
     */
    public function testEventsDispatchedInCorrectOrder(): void
    {
        $result = $this->createTestResult();
        $model = $this->createMockTextGenerationModel($result);

        $builder = new PromptBuilder($this->registry, 'Hello', $this->dispatcher);
        $builder->usingModel($model);

        $builder->generateTextResult();

        $events = $this->dispatcher->getDispatchedEvents();
        $this->assertCount(2, $events);
        $this->assertInstanceOf(BeforeGenerateResultEvent::class, $events[0]);
        $this->assertInstanceOf(AfterGenerateResultEvent::class, $events[1]);
    }

    /**
     * Creates a builder wired with the injected dispatcher and a model that streams the given chunks.
     *
     * @return PromptBuilder
     */
    private function createStreamingBuilderWithDispatcher(): PromptBuilder
    {
        $model = $this->createMockStreamingTextGenerationModel([
            $this->createStreamingTextChunk('Hel'),
            $this->createStreamingTextChunk('lo', FinishReasonEnum::stop()),
            new GenerativeAiResultChunk(null, new TokenUsage(3, 5, 8), [], []),
        ]);

        $builder = new PromptBuilder($this->registry, 'Hello', $this->dispatcher);
        $builder->usingModel($model);

        return $builder;
    }

    /**
     * Tests that the Before event is dispatched before the stream is consumed.
     *
     * @return void
     */
    public function testStreamingDispatchesBeforeEventBeforeConsumption(): void
    {
        $this->createStreamingBuilderWithDispatcher()->streamGenerateTextResult();

        $this->assertCount(1, $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class));
        $this->assertCount(0, $this->dispatcher->getDispatchedEventsOfType(AfterGenerateResultEvent::class));
    }

    /**
     * Tests that the After event fires once with the assembled result.
     *
     * @return void
     */
    public function testStreamingDispatchesAfterEventOnceWithAssembledResult(): void
    {
        $result = $this->createStreamingBuilderWithDispatcher()
            ->streamGenerateTextResult()
            ->getFinalResult();

        $afterEvents = $this->dispatcher->getDispatchedEventsOfType(AfterGenerateResultEvent::class);
        $this->assertCount(1, $afterEvents);

        $event = $afterEvents[0];
        $this->assertSame($result, $event->getResult());
        $this->assertSame('Hello', $event->getResult()->toText());
        $this->assertSame(8, $event->getResult()->getTokenUsage()->getTotalTokens());
        $this->assertEquals(CapabilityEnum::textGeneration(), $event->getCapability());
        $this->assertCount(1, $event->getMessages());
    }

    /**
     * Tests that the After event is not dispatched on an early break.
     *
     * @return void
     */
    public function testStreamingDoesNotDispatchAfterEventOnEarlyBreak(): void
    {
        foreach ($this->createStreamingBuilderWithDispatcher()->streamGenerateTextResult() as $chunk) {
            break;
        }

        $this->assertCount(1, $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateResultEvent::class));
        $this->assertCount(0, $this->dispatcher->getDispatchedEventsOfType(AfterGenerateResultEvent::class));
    }

    /**
     * Tests that the After event fires only once across iteration and getFinalResult().
     *
     * @return void
     */
    public function testStreamingDispatchesAfterEventOnlyOnce(): void
    {
        $handle = $this->createStreamingBuilderWithDispatcher()->streamGenerateTextResult();

        foreach ($handle as $chunk) {
        }
        $handle->getFinalResult();

        $this->assertCount(1, $this->dispatcher->getDispatchedEventsOfType(AfterGenerateResultEvent::class));
    }

    /**
     * Tests that streaming dispatches nothing when no dispatcher is set.
     *
     * @return void
     */
    public function testStreamingDispatchesNoEventsWithoutDispatcher(): void
    {
        $model = $this->createMockStreamingTextGenerationModel([
            $this->createStreamingTextChunk('Hello', FinishReasonEnum::stop()),
        ]);

        $builder = new PromptBuilder($this->registry, 'Hello');
        $builder->usingModel($model);

        $result = $builder->streamGenerateTextResult()->getFinalResult();

        $this->assertSame('Hello', $result->toText());
        $this->assertCount(0, $this->dispatcher->getDispatchedEvents());
    }

    /**
     * Tests that streaming dispatches Before then After.
     *
     * @return void
     */
    public function testStreamingDispatchesEventsInOrder(): void
    {
        $this->createStreamingBuilderWithDispatcher()->streamGenerateTextResult()->getFinalResult();

        $events = $this->dispatcher->getDispatchedEvents();
        $this->assertCount(2, $events);
        $this->assertInstanceOf(BeforeGenerateResultEvent::class, $events[0]);
        $this->assertInstanceOf(AfterGenerateResultEvent::class, $events[1]);
    }
}

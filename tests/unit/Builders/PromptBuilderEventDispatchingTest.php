<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Events\AfterPromptSentEvent;
use WordPress\AiClient\Events\BeforePromptSentEvent;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
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
     * Cleans up after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Clean up global event dispatcher
        AiClient::setEventDispatcher(null);
    }

    /**
     * Tests that events are dispatched when a dispatcher is set globally.
     *
     * @return void
     */
    public function testEventsAreDispatchedWhenDispatcherIsSet(): void
    {
        AiClient::setEventDispatcher($this->dispatcher);

        $result = $this->createTestResult();
        $model = $this->createMockTextGenerationModel($result);

        $builder = new PromptBuilder($this->registry, 'Hello, world!');
        $builder->usingModel($model);

        $builder->generateTextResult();

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforePromptSentEvent::class);
        $afterEvents = $this->dispatcher->getDispatchedEventsOfType(AfterPromptSentEvent::class);

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
     * Tests that BeforePromptSentEvent contains correct data.
     *
     * @return void
     */
    public function testBeforePromptSentEventContainsCorrectData(): void
    {
        AiClient::setEventDispatcher($this->dispatcher);

        $result = $this->createTestResult();
        $model = $this->createMockTextGenerationModel($result);

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);

        $builder->generateTextResult();

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforePromptSentEvent::class);
        $this->assertCount(1, $beforeEvents);

        $event = $beforeEvents[0];
        $this->assertCount(1, $event->getMessages());
        $this->assertSame($model, $event->getModel());
        $this->assertEquals(CapabilityEnum::textGeneration(), $event->getCapability());
    }

    /**
     * Tests that AfterPromptSentEvent contains correct data.
     *
     * @return void
     */
    public function testAfterPromptSentEventContainsCorrectData(): void
    {
        AiClient::setEventDispatcher($this->dispatcher);

        $result = $this->createTestResult('Generated response');
        $model = $this->createMockTextGenerationModel($result);

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);

        $returnedResult = $builder->generateTextResult();

        $afterEvents = $this->dispatcher->getDispatchedEventsOfType(AfterPromptSentEvent::class);
        $this->assertCount(1, $afterEvents);

        $event = $afterEvents[0];
        $this->assertCount(1, $event->getMessages());
        $this->assertSame($model, $event->getModel());
        $this->assertEquals(CapabilityEnum::textGeneration(), $event->getCapability());
        $this->assertSame($result, $event->getResult());
        $this->assertSame($returnedResult, $event->getResult());
    }

    /**
     * Tests that BeforePromptSentEvent can modify messages.
     *
     * @return void
     */
    public function testBeforePromptSentEventCanModifyMessages(): void
    {
        AiClient::setEventDispatcher($this->dispatcher);

        $result = $this->createTestResult();
        $model = $this->createMockTextGenerationModel($result);

        // Register a listener that modifies the messages
        $modifiedMessages = [
            new UserMessage([new MessagePart('Modified message')])
        ];

        $this->dispatcher->addListener(
            BeforePromptSentEvent::class,
            static function (BeforePromptSentEvent $event) use ($modifiedMessages): void {
                $event->setMessages($modifiedMessages);
            }
        );

        $builder = new PromptBuilder($this->registry, 'Original message');
        $builder->usingModel($model);

        $builder->generateTextResult();

        // Verify the modification happened
        $afterEvents = $this->dispatcher->getDispatchedEventsOfType(AfterPromptSentEvent::class);
        $this->assertCount(1, $afterEvents);

        $afterEvent = $afterEvents[0];
        $this->assertSame($modifiedMessages, $afterEvent->getMessages());
    }

    /**
     * Tests that events are dispatched in correct order.
     *
     * @return void
     */
    public function testEventsDispatchedInCorrectOrder(): void
    {
        AiClient::setEventDispatcher($this->dispatcher);

        $result = $this->createTestResult();
        $model = $this->createMockTextGenerationModel($result);

        $builder = new PromptBuilder($this->registry, 'Hello');
        $builder->usingModel($model);

        $builder->generateTextResult();

        $events = $this->dispatcher->getDispatchedEvents();
        $this->assertCount(2, $events);
        $this->assertInstanceOf(BeforePromptSentEvent::class, $events[0]);
        $this->assertInstanceOf(AfterPromptSentEvent::class, $events[1]);
    }
}

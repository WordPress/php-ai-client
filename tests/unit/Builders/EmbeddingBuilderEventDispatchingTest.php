<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Builders\EmbeddingBuilder;
use WordPress\AiClient\Events\AfterGenerateEmbeddingEvent;
use WordPress\AiClient\Events\BeforeGenerateEmbeddingEvent;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Tests\mocks\MockEventDispatcher;
use WordPress\AiClient\Tests\mocks\MockProvider;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * Tests for event dispatching in EmbeddingBuilder.
 *
 * @covers \WordPress\AiClient\Builders\EmbeddingBuilder
 */
class EmbeddingBuilderEventDispatchingTest extends TestCase
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
     * Tests that events are dispatched for single embedding generation.
     *
     * @return void
     */
    public function testEventsAreDispatchedForEmbeddingGeneration(): void
    {
        $result = $this->createTestEmbeddingResult();
        $model = $this->createMockEmbeddingGenerationModel($result);

        $builder = new EmbeddingBuilder($this->registry, 'Hello', $this->dispatcher);
        $builder->usingModel($model);

        $returnedResult = $builder->generateEmbeddingResult();

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateEmbeddingEvent::class);
        $afterEvents = $this->dispatcher->getDispatchedEventsOfType(AfterGenerateEmbeddingEvent::class);

        $this->assertCount(1, $beforeEvents);
        $this->assertCount(1, $afterEvents);
        $this->assertCount(1, $beforeEvents[0]->getInputs());
        $this->assertInstanceOf(MessagePart::class, $beforeEvents[0]->getInputs()[0]);
        $this->assertEquals(CapabilityEnum::embeddingGeneration(), $beforeEvents[0]->getCapability());
        $this->assertEquals(CapabilityEnum::embeddingGeneration(), $afterEvents[0]->getCapability());
        $this->assertSame($result, $afterEvents[0]->getResult());
        $this->assertSame($returnedResult, $afterEvents[0]->getResult());
    }

    /**
     * Tests that events are dispatched for batch embedding generation.
     *
     * @return void
     */
    public function testEventsAreDispatchedForBatchEmbeddingGeneration(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2, 0.3], [0.4, 0.5, 0.6]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $builder = new EmbeddingBuilder($this->registry, ['Hello', 'World'], $this->dispatcher);
        $builder->usingModel($model);

        $embeddings = $builder->generateEmbeddings();

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforeGenerateEmbeddingEvent::class);
        $afterEvents = $this->dispatcher->getDispatchedEventsOfType(AfterGenerateEmbeddingEvent::class);

        $this->assertCount(2, $embeddings);
        $this->assertCount(1, $beforeEvents);
        $this->assertCount(1, $afterEvents);
        $this->assertCount(2, $beforeEvents[0]->getInputs());
        $this->assertInstanceOf(MessagePart::class, $beforeEvents[0]->getInputs()[0]);
        $this->assertInstanceOf(MessagePart::class, $beforeEvents[0]->getInputs()[1]);
        $this->assertEquals(CapabilityEnum::embeddingGeneration(), $beforeEvents[0]->getCapability());
        $this->assertSame($result, $afterEvents[0]->getResult());
    }
}

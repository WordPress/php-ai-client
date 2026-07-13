<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Builders\EmbeddingBuilder;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Events\AfterGenerateEmbeddingEvent;
use WordPress\AiClient\Events\BeforeGenerateEmbeddingEvent;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Tests\mocks\MockEventDispatcher;
use WordPress\AiClient\Tests\mocks\MockProvider;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * Tests the embedding builder.
 *
 * @covers \WordPress\AiClient\Builders\EmbeddingBuilder
 */
class EmbeddingBuilderTest extends TestCase
{
    use MockModelCreationTrait;

    public function testGeneratesABatchAndDispatchesTextInputs(): void
    {
        $dispatcher = new MockEventDispatcher();
        $result = $this->createTestEmbeddingResult([[0.1, 0.2], [0.3, 0.4]]);
        $registry = new ProviderRegistry();
        $registry->registerProvider(MockProvider::class);
        $builder = new EmbeddingBuilder(['First', 'Second'], $registry, $dispatcher);
        $builder->usingModel($this->createMockEmbeddingGenerationModel($result));

        $this->assertCount(2, $builder->generateEmbeddings());
        $before = $dispatcher->getDispatchedEventsOfType(BeforeGenerateEmbeddingEvent::class);
        $after = $dispatcher->getDispatchedEventsOfType(AfterGenerateEmbeddingEvent::class);
        $this->assertSame(['First', 'Second'], $before[0]->getInputs());
        $this->assertSame($result, $after[0]->getResult());
    }

    public function testSingleGenerationRejectsABatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new EmbeddingBuilder(['First', 'Second'], new ProviderRegistry()))->generateEmbedding();
    }
}

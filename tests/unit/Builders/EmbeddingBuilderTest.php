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

    /**
     * @dataProvider invalidInputsProvider
     *
     * @param array<mixed> $inputs Invalid embedding inputs.
     */
    public function testRejectsInvalidInputs(array $inputs): void
    {
        $this->expectException(InvalidArgumentException::class);
        new EmbeddingBuilder($inputs, new ProviderRegistry());
    }

    /**
     * @return iterable<string, array{0: array<mixed>}>
     */
    public static function invalidInputsProvider(): iterable
    {
        yield 'empty list' => [[]];
        yield 'non-list array' => [['first' => 'First']];
        yield 'non-string value' => [['First', 2]];
        yield 'blank string' => [['First', '  ']];
    }

    public function testRejectsAnEmbeddingResultWithTheWrongBatchSize(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2]]);
        $builder = new EmbeddingBuilder(['First', 'Second'], $this->createRegistry());
        $builder->usingModel($this->createMockEmbeddingGenerationModel($result));

        $this->expectException(\WordPress\AiClient\Common\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Expected 2 embedding(s) from the model, but received 1.');

        $builder->generateEmbeddings();
    }

    public function testRejectsAModelWithoutEmbeddingGenerationSupport(): void
    {
        $builder = new EmbeddingBuilder(['First'], $this->createRegistry());
        $builder->usingModel($this->createMockUnsupportedModel('text-only'));

        $this->expectException(\WordPress\AiClient\Common\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Model "text-only" does not support embedding generation.');

        $builder->generateEmbedding();
    }

    public function testAppliesDimensionsToAnExplicitModel(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2]]);
        $model = $this->createMockEmbeddingGenerationModel($result);
        $builder = new EmbeddingBuilder(['First'], $this->createRegistry());
        $builder->usingModel($model)->usingDimensions(2)->generateEmbedding();

        $this->assertSame(2, $model->getConfig()->getDimensions());
    }

    private function createRegistry(): ProviderRegistry
    {
        $registry = new ProviderRegistry();
        $registry->registerProvider(MockProvider::class);
        return $registry;
    }
}

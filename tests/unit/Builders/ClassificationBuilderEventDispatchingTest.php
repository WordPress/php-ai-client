<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Builders\ClassificationBuilder;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Events\AfterClassifyEvent;
use WordPress\AiClient\Events\BeforeClassifyEvent;
use WordPress\AiClient\Providers\Models\Classification\DTO\ClassificationQuestion;
use WordPress\AiClient\Providers\Models\Classification\Enums\ClassificationQuestionTypeEnum;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\ClassificationAnswer;
use WordPress\AiClient\Tests\mocks\MockEventDispatcher;
use WordPress\AiClient\Tests\mocks\MockProvider;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * Tests for event dispatching in ClassificationBuilder.
 *
 * @covers \WordPress\AiClient\Builders\ClassificationBuilder
 */
class ClassificationBuilderEventDispatchingTest extends TestCase
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
     * Tests that events are dispatched for classification.
     *
     * @return void
     */
    public function testEventsAreDispatchedForClassification(): void
    {
        $result = $this->createTestClassificationResult();
        $model = $this->createMockClassificationModel($result);
        $question = new ClassificationQuestion(ClassificationQuestionTypeEnum::binary(), 'Is this comment spam?');

        $builder = new ClassificationBuilder($this->registry, ['comment' => 'Buy cheap pills!'], $this->dispatcher);
        $builder->withQuestion('spam', $question)->usingModel($model);

        $returnedResult = $builder->classifyResult();

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforeClassifyEvent::class);
        $afterEvents = $this->dispatcher->getDispatchedEventsOfType(AfterClassifyEvent::class);

        $this->assertCount(1, $beforeEvents);
        $this->assertCount(1, $afterEvents);
        $this->assertSame(['comment' => 'Buy cheap pills!'], $beforeEvents[0]->getState());
        $this->assertSame(['spam' => $question], $beforeEvents[0]->getQuestions());
        $this->assertSame($model, $beforeEvents[0]->getModel());
        $this->assertEquals(CapabilityEnum::classification(), $beforeEvents[0]->getCapability());
        $this->assertSame(['comment' => 'Buy cheap pills!'], $afterEvents[0]->getState());
        $this->assertSame(['spam' => $question], $afterEvents[0]->getQuestions());
        $this->assertEquals(CapabilityEnum::classification(), $afterEvents[0]->getCapability());
        $this->assertSame($result, $afterEvents[0]->getResult());
        $this->assertSame($returnedResult, $afterEvents[0]->getResult());
    }

    /**
     * Tests that no after event is dispatched when the model gives an invalid answer.
     *
     * @return void
     */
    public function testAfterEventIsNotDispatchedForInvalidAnswers(): void
    {
        $model = $this->createMockClassificationModel(
            $this->createTestClassificationResult([
                'spam' => new ClassificationAnswer(ClassificationQuestionTypeEnum::choice(), 'yes'),
            ])
        );

        $builder = new ClassificationBuilder($this->registry, ['comment' => 'Buy cheap pills!'], $this->dispatcher);
        $builder->withQuestion(
            'spam',
            new ClassificationQuestion(ClassificationQuestionTypeEnum::binary(), 'Is this comment spam?')
        )->usingModel($model);

        try {
            $builder->classifyResult();
            $this->fail('Expected the invalid answer to be rejected.');
        } catch (RuntimeException $e) {
            $this->assertCount(1, $this->dispatcher->getDispatchedEventsOfType(BeforeClassifyEvent::class));
            $this->assertCount(0, $this->dispatcher->getDispatchedEventsOfType(AfterClassifyEvent::class));
        }
    }
}

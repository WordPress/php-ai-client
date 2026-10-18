<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Builders\DecisionBuilder;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Events\AfterDecideEvent;
use WordPress\AiClient\Events\BeforeDecideEvent;
use WordPress\AiClient\Providers\Models\Decision\DTO\DecisionQuestion;
use WordPress\AiClient\Providers\Models\Decision\Enums\DecisionQuestionTypeEnum;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\DecisionAnswer;
use WordPress\AiClient\Tests\mocks\MockEventDispatcher;
use WordPress\AiClient\Tests\mocks\MockProvider;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * Tests for event dispatching in DecisionBuilder.
 *
 * @covers \WordPress\AiClient\Builders\DecisionBuilder
 */
class DecisionBuilderEventDispatchingTest extends TestCase
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
     * Tests that events are dispatched for decisions.
     *
     * @return void
     */
    public function testEventsAreDispatchedForDecision(): void
    {
        $result = $this->createTestDecisionResult();
        $model = $this->createMockDecisionModel($result);
        $question = new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Is this comment spam?');

        $builder = new DecisionBuilder($this->registry, ['comment' => 'Buy cheap pills!'], $this->dispatcher);
        $builder->withQuestion('spam', $question)->usingModel($model);

        $returnedResult = $builder->decideResult();

        $beforeEvents = $this->dispatcher->getDispatchedEventsOfType(BeforeDecideEvent::class);
        $afterEvents = $this->dispatcher->getDispatchedEventsOfType(AfterDecideEvent::class);

        $this->assertCount(1, $beforeEvents);
        $this->assertCount(1, $afterEvents);
        $this->assertSame(['comment' => 'Buy cheap pills!'], $beforeEvents[0]->getState());
        $this->assertSame(['spam' => $question], $beforeEvents[0]->getQuestions());
        $this->assertSame($model, $beforeEvents[0]->getModel());
        $this->assertEquals(CapabilityEnum::decision(), $beforeEvents[0]->getCapability());
        $this->assertSame(['comment' => 'Buy cheap pills!'], $afterEvents[0]->getState());
        $this->assertSame(['spam' => $question], $afterEvents[0]->getQuestions());
        $this->assertEquals(CapabilityEnum::decision(), $afterEvents[0]->getCapability());
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
        $model = $this->createMockDecisionModel(
            $this->createTestDecisionResult([
                'spam' => new DecisionAnswer(DecisionQuestionTypeEnum::choice(), 'yes'),
            ])
        );

        $builder = new DecisionBuilder($this->registry, ['comment' => 'Buy cheap pills!'], $this->dispatcher);
        $builder->withQuestion(
            'spam',
            new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Is this comment spam?')
        )->usingModel($model);

        try {
            $builder->decideResult();
            $this->fail('Expected the invalid answer to be rejected.');
        } catch (RuntimeException $e) {
            $this->assertCount(1, $this->dispatcher->getDispatchedEventsOfType(BeforeDecideEvent::class));
            $this->assertCount(0, $this->dispatcher->getDispatchedEventsOfType(AfterDecideEvent::class));
        }
    }
}

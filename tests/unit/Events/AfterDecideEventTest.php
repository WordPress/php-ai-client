<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Events;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Events\AfterDecideEvent;
use WordPress\AiClient\Providers\Models\Decision\DTO\DecisionQuestion;
use WordPress\AiClient\Providers\Models\Decision\Enums\DecisionQuestionTypeEnum;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Events\AfterDecideEvent
 */
class AfterDecideEventTest extends TestCase
{
    use MockModelCreationTrait;

    /**
     * Tests event construction with all parameters.
     *
     * @return void
     */
    public function testConstruction(): void
    {
        $state = ['comment' => 'Buy cheap pills!'];
        $questions = ['spam' => new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Is it spam?')];
        $result = $this->createTestDecisionResult();
        $model = $this->createMockDecisionModel($result);
        $capability = CapabilityEnum::decision();

        $event = new AfterDecideEvent($state, $questions, $model, $capability, $result);

        $this->assertSame($state, $event->getState());
        $this->assertSame($questions, $event->getQuestions());
        $this->assertSame($model, $event->getModel());
        $this->assertSame($capability, $event->getCapability());
        $this->assertSame($result, $event->getResult());
    }

    /**
     * Tests that cloning the event clones its questions and result.
     *
     * @return void
     */
    public function testCloneClonesQuestionsAndResult(): void
    {
        $question = new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Is it spam?');
        $result = $this->createTestDecisionResult();

        $event = new AfterDecideEvent(
            ['comment' => 'Buy cheap pills!'],
            ['spam' => $question],
            $this->createMockDecisionModel($result),
            CapabilityEnum::decision(),
            $result
        );
        $clone = clone $event;

        $this->assertSame(['spam'], array_keys($clone->getQuestions()));
        $this->assertNotSame($question, $clone->getQuestions()['spam']);
        $this->assertNotSame($result, $clone->getResult());
        $this->assertEquals($result, $clone->getResult());
    }
}

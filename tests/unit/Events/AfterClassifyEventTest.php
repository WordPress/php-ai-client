<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Events;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Events\AfterClassifyEvent;
use WordPress\AiClient\Providers\Models\Classification\DTO\ClassificationQuestion;
use WordPress\AiClient\Providers\Models\Classification\Enums\ClassificationQuestionTypeEnum;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Events\AfterClassifyEvent
 */
class AfterClassifyEventTest extends TestCase
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
        $questions = ['spam' => new ClassificationQuestion(ClassificationQuestionTypeEnum::binary(), 'Is it spam?')];
        $result = $this->createTestClassificationResult();
        $model = $this->createMockClassificationModel($result);
        $capability = CapabilityEnum::classification();

        $event = new AfterClassifyEvent($state, $questions, $model, $capability, $result);

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
        $question = new ClassificationQuestion(ClassificationQuestionTypeEnum::binary(), 'Is it spam?');
        $result = $this->createTestClassificationResult();

        $event = new AfterClassifyEvent(
            ['comment' => 'Buy cheap pills!'],
            ['spam' => $question],
            $this->createMockClassificationModel($result),
            CapabilityEnum::classification(),
            $result
        );
        $clone = clone $event;

        $this->assertSame(['spam'], array_keys($clone->getQuestions()));
        $this->assertNotSame($question, $clone->getQuestions()['spam']);
        $this->assertNotSame($result, $clone->getResult());
        $this->assertEquals($result, $clone->getResult());
    }
}

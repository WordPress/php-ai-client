<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Events;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Events\BeforeClassifyEvent;
use WordPress\AiClient\Providers\Models\Classification\DTO\ClassificationQuestion;
use WordPress\AiClient\Providers\Models\Classification\Enums\ClassificationQuestionTypeEnum;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Events\BeforeClassifyEvent
 */
class BeforeClassifyEventTest extends TestCase
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
        $model = $this->createMockClassificationModel($this->createTestClassificationResult());
        $capability = CapabilityEnum::classification();

        $event = new BeforeClassifyEvent($state, $questions, $model, $capability);

        $this->assertSame($state, $event->getState());
        $this->assertSame($questions, $event->getQuestions());
        $this->assertSame($model, $event->getModel());
        $this->assertSame($capability, $event->getCapability());
    }

    /**
     * Tests that cloning the event clones its questions but keeps their keys.
     *
     * @return void
     */
    public function testCloneClonesQuestions(): void
    {
        $question = new ClassificationQuestion(ClassificationQuestionTypeEnum::binary(), 'Is it spam?');
        $model = $this->createMockClassificationModel($this->createTestClassificationResult());

        $event = new BeforeClassifyEvent(
            ['comment' => 'Buy cheap pills!'],
            ['spam' => $question],
            $model,
            CapabilityEnum::classification()
        );
        $clone = clone $event;

        $this->assertSame(['spam'], array_keys($clone->getQuestions()));
        $this->assertNotSame($question, $clone->getQuestions()['spam']);
        $this->assertEquals($question, $clone->getQuestions()['spam']);
        $this->assertSame($model, $clone->getModel());
    }
}

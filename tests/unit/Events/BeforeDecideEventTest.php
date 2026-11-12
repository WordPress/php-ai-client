<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Events;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Decisions\DTO\DecisionQuestion;
use WordPress\AiClient\Decisions\DTO\DecisionRequest;
use WordPress\AiClient\Decisions\Enums\DecisionQuestionTypeEnum;
use WordPress\AiClient\Events\BeforeDecideEvent;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Events\BeforeDecideEvent
 */
class BeforeDecideEventTest extends TestCase
{
    use MockModelCreationTrait;

    /**
     * Creates a decision request.
     *
     * @return DecisionRequest
     */
    private function createRequest(): DecisionRequest
    {
        return new DecisionRequest(
            ['comment' => 'Buy cheap pills!'],
            ['spam' => new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Is it spam?')]
        );
    }

    /**
     * Tests event construction with all parameters.
     *
     * @return void
     */
    public function testConstruction(): void
    {
        $request = $this->createRequest();
        $model = $this->createMockDecisionModel($this->createTestDecisionResult());
        $capability = CapabilityEnum::decision();

        $event = new BeforeDecideEvent($request, $model, $capability);

        $this->assertSame($request, $event->getRequest());
        $this->assertSame($model, $event->getModel());
        $this->assertSame($capability, $event->getCapability());
    }

    /**
     * Tests that cloning the event clones its request.
     *
     * @return void
     */
    public function testCloneClonesRequest(): void
    {
        $request = $this->createRequest();
        $model = $this->createMockDecisionModel($this->createTestDecisionResult());

        $event = new BeforeDecideEvent($request, $model, CapabilityEnum::decision());
        $clone = clone $event;

        $this->assertNotSame($request, $clone->getRequest());
        $this->assertEquals($request, $clone->getRequest());
        $this->assertSame($model, $clone->getModel());
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Events;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Decisions\DTO\DecisionQuestion;
use WordPress\AiClient\Decisions\DTO\DecisionRequest;
use WordPress\AiClient\Decisions\Enums\DecisionQuestionTypeEnum;
use WordPress\AiClient\Events\AfterDecideEvent;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Events\AfterDecideEvent
 */
class AfterDecideEventTest extends TestCase
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
        $result = $this->createTestDecisionResult();
        $model = $this->createMockDecisionModel($result);
        $capability = CapabilityEnum::decision();

        $event = new AfterDecideEvent($request, $model, $capability, $result);

        $this->assertSame($request, $event->getRequest());
        $this->assertSame($model, $event->getModel());
        $this->assertSame($capability, $event->getCapability());
        $this->assertSame($result, $event->getResult());
    }

    /**
     * Tests that cloning the event clones its request and result.
     *
     * @return void
     */
    public function testCloneClonesRequestAndResult(): void
    {
        $request = $this->createRequest();
        $result = $this->createTestDecisionResult();

        $event = new AfterDecideEvent(
            $request,
            $this->createMockDecisionModel($result),
            CapabilityEnum::decision(),
            $result
        );
        $clone = clone $event;

        $this->assertNotSame($request, $clone->getRequest());
        $this->assertEquals($request, $clone->getRequest());
        $this->assertNotSame($result, $clone->getResult());
        $this->assertEquals($result, $clone->getResult());
    }
}

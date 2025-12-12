<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Events;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Events\AfterGenerateResultEvent;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Events\AfterPromptSentEvent
 */
class AfterPromptSentEventTest extends TestCase
{
    use MockModelCreationTrait;

    /**
     * Tests event construction with all parameters.
     *
     * @return void
     */
    public function testConstruction(): void
    {
        $messages = [
            new UserMessage([new MessagePart('Hello, world!')])
        ];
        $result = $this->createTestResult('Hello!');
        $model = $this->createMockTextGenerationModel($result);
        $capability = CapabilityEnum::textGeneration();

        $event = new AfterGenerateResultEvent($messages, $model, $capability, $result);

        $this->assertSame($messages, $event->getMessages());
        $this->assertSame($model, $event->getModel());
        $this->assertSame($capability, $event->getCapability());
        $this->assertSame($result, $event->getResult());
    }

    /**
     * Tests event construction with null capability.
     *
     * @return void
     */
    public function testConstructionWithNullCapability(): void
    {
        $messages = [
            new UserMessage([new MessagePart('Hello')])
        ];
        $result = $this->createTestResult('Response');
        $model = $this->createMockTextGenerationModel($result);

        $event = new AfterGenerateResultEvent($messages, $model, null, $result);

        $this->assertNull($event->getCapability());
    }

    /**
     * Tests that result is accessible.
     *
     * @return void
     */
    public function testGetResult(): void
    {
        $messages = [
            new UserMessage([new MessagePart('Test prompt')])
        ];
        $result = $this->createTestResult('Test response');
        $model = $this->createMockTextGenerationModel($result);

        $event = new AfterGenerateResultEvent(
            $messages,
            $model,
            CapabilityEnum::textGeneration(),
            $result
        );

        $this->assertSame($result, $event->getResult());
        $this->assertCount(1, $event->getResult()->getCandidates());
    }
}

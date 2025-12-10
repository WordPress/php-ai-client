<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Events;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Events\BeforeGenerateResultEvent;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Events\BeforePromptSentEvent
 */
class BeforePromptSentEventTest extends TestCase
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
        $result = $this->createTestResult();
        $model = $this->createMockTextGenerationModel($result);
        $capability = CapabilityEnum::textGeneration();

        $event = new BeforeGenerateResultEvent($messages, $model, $capability);

        $this->assertSame($messages, $event->getMessages());
        $this->assertSame($model, $event->getModel());
        $this->assertSame($capability, $event->getCapability());
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
        $result = $this->createTestResult();
        $model = $this->createMockTextGenerationModel($result);

        $event = new BeforeGenerateResultEvent($messages, $model, null);

        $this->assertNull($event->getCapability());
    }

    /**
     * Tests that the event can hold multiple messages.
     *
     * @return void
     */
    public function testMultipleMessages(): void
    {
        $messages = [
            new UserMessage([new MessagePart('First message')]),
            new UserMessage([new MessagePart('Second message')]),
            new UserMessage([new MessagePart('Third message')])
        ];
        $result = $this->createTestResult();
        $model = $this->createMockTextGenerationModel($result);

        $event = new BeforeGenerateResultEvent($messages, $model, CapabilityEnum::textGeneration());

        $this->assertCount(3, $event->getMessages());
    }
}

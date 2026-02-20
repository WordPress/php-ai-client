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
 * @covers \WordPress\AiClient\Events\AfterGenerateResultEvent
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

    /**
     * Tests that cloning the event creates independent message and result copies.
     *
     * @return void
     */
    public function testCloneClonesMessagesAndResult(): void
    {
        $messages = [
            new UserMessage([new MessagePart('Hello, world!')]),
        ];
        $result = $this->createTestResult('Test response');
        $model = $this->createMockTextGenerationModel($result);
        $capability = CapabilityEnum::textGeneration();

        $original = new AfterGenerateResultEvent($messages, $model, $capability, $result);
        $cloned = clone $original;

        // Messages should be different instances
        $this->assertNotSame($original->getMessages()[0], $cloned->getMessages()[0]);

        // Result should be a different instance
        $this->assertNotSame($original->getResult(), $cloned->getResult());

        // Model should be the same instance (service objects are not cloned)
        $this->assertSame($original->getModel(), $cloned->getModel());

        // Capability enum should be the same instance (enums are singletons)
        $this->assertSame($original->getCapability(), $cloned->getCapability());
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Events;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\Events\GenerateResultErrorEvent;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Events\GenerateResultErrorEvent
 */
class GenerateResultErrorEventTest extends TestCase
{
    use MockModelCreationTrait;

    /**
     * Tests event construction with all parameters.
     *
     * @return void
     */
    public function testConstruction(): void
    {
        $messages = [new UserMessage([new MessagePart('Hello')])];
        $model = $this->createMockTextGenerationModel($this->createTestResult());
        $capability = CapabilityEnum::textGeneration();
        $error = new RuntimeException('stream failed');

        $event = new GenerateResultErrorEvent($messages, $model, $capability, $error);

        $this->assertSame($messages, $event->getMessages());
        $this->assertSame($model, $event->getModel());
        $this->assertSame($capability, $event->getCapability());
        $this->assertSame($error, $event->getError());
    }

    /**
     * Tests event construction with null capability.
     *
     * @return void
     */
    public function testConstructionWithNullCapability(): void
    {
        $messages = [new UserMessage([new MessagePart('Hello')])];
        $model = $this->createMockTextGenerationModel($this->createTestResult());

        $event = new GenerateResultErrorEvent($messages, $model, null, new RuntimeException('boom'));

        $this->assertNull($event->getCapability());
    }

    /**
     * Tests that cloning copies messages but keeps the model and error instances.
     *
     * @return void
     */
    public function testCloneClonesMessagesOnly(): void
    {
        $messages = [new UserMessage([new MessagePart('Hello')])];
        $model = $this->createMockTextGenerationModel($this->createTestResult());
        $error = new RuntimeException('boom');

        $original = new GenerateResultErrorEvent($messages, $model, CapabilityEnum::textGeneration(), $error);
        $cloned = clone $original;

        $this->assertNotSame($original->getMessages()[0], $cloned->getMessages()[0]);
        $this->assertSame($original->getModel(), $cloned->getModel());
        $this->assertSame($original->getError(), $cloned->getError());
    }
}

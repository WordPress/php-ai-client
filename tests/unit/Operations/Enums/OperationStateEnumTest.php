<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Operations\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;
use WordPress\AiClient\Tests\traits\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Operations\Enums\OperationStateEnum
 */
class OperationStateEnumTest extends TestCase
{
    use EnumTestTrait;

    /**
     * Gets the enum class to test.
     *
     * @return string
     */
    protected function getEnumClass(): string
    {
        return OperationStateEnum::class;
    }

    /**
     * Gets the expected enum values.
     *
     * @return array
     */
    protected function getExpectedValues(): array
    {
        return [
            'STARTING' => 'starting',
            'PROCESSING' => 'processing',
            'SUCCEEDED' => 'succeeded',
            'FAILED' => 'failed',
            'CANCELED' => 'canceled',
        ];
    }

    /**
     * Tests the specific enum methods.
     *
     * @return void
     */
    public function testSpecificEnumMethods(): void
    {
        $starting = OperationStateEnum::starting();
        $this->assertTrue($starting->isStarting());
        $this->assertFalse($starting->isProcessing());

        $succeeded = OperationStateEnum::succeeded();
        $this->assertTrue($succeeded->isSucceeded());
        $this->assertFalse($succeeded->isFailed());

        $failed = OperationStateEnum::failed();
        $this->assertTrue($failed->isFailed());
        $this->assertFalse($failed->isCanceled());
    }
}

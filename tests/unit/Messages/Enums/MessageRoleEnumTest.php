<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Tests\traits\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Messages\Enums\MessageRoleEnum
 */
class MessageRoleEnumTest extends TestCase
{
    use EnumTestTrait;

    /**
     * Gets the enum class to test.
     *
     * @return string
     */
    protected function getEnumClass(): string
    {
        return MessageRoleEnum::class;
    }

    /**
     * Gets the expected enum values.
     *
     * @return array
     */
    protected function getExpectedValues(): array
    {
        return [
            'USER' => 'user',
            'MODEL' => 'model',
            'SYSTEM' => 'system',
        ];
    }

    /**
     * Tests the specific enum methods.
     *
     * @return void
     */
    public function testSpecificEnumMethods(): void
    {
        $user = MessageRoleEnum::user();
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isModel());
        $this->assertFalse($user->isSystem());

        $model = MessageRoleEnum::model();
        $this->assertFalse($model->isUser());
        $this->assertTrue($model->isModel());
        $this->assertFalse($model->isSystem());

        $system = MessageRoleEnum::system();
        $this->assertFalse($system->isUser());
        $this->assertFalse($system->isModel());
        $this->assertTrue($system->isSystem());
    }
}

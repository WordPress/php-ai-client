<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Tests\traits\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Messages\Enums\MessagePartTypeEnum
 */
class MessagePartTypeEnumTest extends TestCase
{
    use EnumTestTrait;

    /**
     * Gets the enum class to test.
     *
     * @return string
     */
    protected function getEnumClass(): string
    {
        return MessagePartTypeEnum::class;
    }

    /**
     * Gets the expected enum values.
     *
     * @return array
     */
    protected function getExpectedValues(): array
    {
        return [
            'TEXT' => 'text',
            'FILE' => 'file',
            'FUNCTION_CALL' => 'function_call',
            'FUNCTION_RESPONSE' => 'function_response',
        ];
    }

    /**
     * Tests the specific enum methods.
     *
     * @return void
     */
    public function testSpecificEnumMethods(): void
    {
        $text = MessagePartTypeEnum::text();
        $this->assertTrue($text->isText());
        $this->assertFalse($text->isFile());

        $file = MessagePartTypeEnum::file();
        $this->assertTrue($file->isFile());
        $this->assertFalse($file->isText());

        $functionCall = MessagePartTypeEnum::functionCall();
        $this->assertTrue($functionCall->isFunctionCall());
        $this->assertFalse($functionCall->isFunctionResponse());
    }
}

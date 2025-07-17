<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Tests\unit\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Messages\Enums\MessagePartTypeEnum
 */
class MessagePartTypeEnumTest extends TestCase
{
    use EnumTestTrait;

    protected function getEnumClass(): string
    {
        return MessagePartTypeEnum::class;
    }

    protected function getExpectedValues(): array
    {
        return [
            'TEXT' => 'text',
            'INLINE_FILE' => 'inline_file',
            'REMOTE_FILE' => 'remote_file',
            'FUNCTION_CALL' => 'function_call',
            'FUNCTION_RESPONSE' => 'function_response',
        ];
    }

    public function testSpecificEnumMethods(): void
    {
        $text = MessagePartTypeEnum::text();
        $this->assertTrue($text->isText());
        $this->assertFalse($text->isInlineFile());

        $inlineFile = MessagePartTypeEnum::inlineFile();
        $this->assertTrue($inlineFile->isInlineFile());
        $this->assertFalse($inlineFile->isRemoteFile());

        $functionCall = MessagePartTypeEnum::functionCall();
        $this->assertTrue($functionCall->isFunctionCall());
        $this->assertFalse($functionCall->isFunctionResponse());
    }
}

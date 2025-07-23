<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Enums\ToolTypeEnum;
use WordPress\AiClient\Tests\unit\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Providers\Enums\ToolTypeEnum
 */
class ToolTypeEnumTest extends TestCase
{
    use EnumTestTrait;

    /**
     * Gets the enum class to test.
     *
     * @return string
     */
    protected function getEnumClass(): string
    {
        return ToolTypeEnum::class;
    }

    /**
     * Gets the expected enum values.
     *
     * @return array
     */
    protected function getExpectedValues(): array
    {
        return [
            'FUNCTION_DECLARATIONS' => 'function_declarations',
            'WEB_SEARCH' => 'web_search',
        ];
    }

    /**
     * Tests the specific enum methods.
     *
     * @return void
     */
    public function testSpecificEnumMethods(): void
    {
        $functionDeclarations = ToolTypeEnum::functionDeclarations();
        $this->assertTrue($functionDeclarations->isFunctionDeclarations());
        $this->assertFalse($functionDeclarations->isWebSearch());

        $webSearch = ToolTypeEnum::webSearch();
        $this->assertFalse($webSearch->isFunctionDeclarations());
        $this->assertTrue($webSearch->isWebSearch());
    }
}

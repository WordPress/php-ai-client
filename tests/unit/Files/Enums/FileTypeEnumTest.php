<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Files\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Tests\traits\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Files\Enums\FileTypeEnum
 */
class FileTypeEnumTest extends TestCase
{
    use EnumTestTrait;

    /**
     * Gets the enum class to test.
     *
     * @return string
     */
    protected function getEnumClass(): string
    {
        return FileTypeEnum::class;
    }

    /**
     * Gets the expected enum values.
     *
     * @return array
     */
    protected function getExpectedValues(): array
    {
        return [
            'INLINE' => 'inline',
            'REMOTE' => 'remote',
        ];
    }

    /**
     * Tests the specific enum methods.
     *
     * @return void
     */
    public function testSpecificEnumMethods(): void
    {
        $inline = FileTypeEnum::inline();
        $this->assertTrue($inline->isInline());
        $this->assertFalse($inline->isRemote());

        $remote = FileTypeEnum::remote();
        $this->assertTrue($remote->isRemote());
        $this->assertFalse($remote->isInline());
    }
}
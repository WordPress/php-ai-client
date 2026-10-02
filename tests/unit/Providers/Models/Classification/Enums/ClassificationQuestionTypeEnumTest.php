<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\Classification\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\Classification\Enums\ClassificationQuestionTypeEnum;
use WordPress\AiClient\Tests\traits\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Providers\Models\Classification\Enums\ClassificationQuestionTypeEnum
 */
class ClassificationQuestionTypeEnumTest extends TestCase
{
    use EnumTestTrait;

    /**
     * Gets the enum class to test.
     *
     * @return string
     */
    protected function getEnumClass(): string
    {
        return ClassificationQuestionTypeEnum::class;
    }

    /**
     * Gets the expected enum values.
     *
     * @return array
     */
    protected function getExpectedValues(): array
    {
        return [
            'BINARY' => 'binary',
            'CHOICE' => 'choice',
            'SCORE' => 'score',
        ];
    }

    /**
     * Tests the specific enum methods.
     *
     * @return void
     */
    public function testSpecificEnumMethods(): void
    {
        $binary = ClassificationQuestionTypeEnum::binary();
        $this->assertTrue($binary->isBinary());
        $this->assertFalse($binary->isChoice());

        $choice = ClassificationQuestionTypeEnum::choice();
        $this->assertTrue($choice->isChoice());
        $this->assertFalse($choice->isScore());

        $score = ClassificationQuestionTypeEnum::score();
        $this->assertTrue($score->isScore());
        $this->assertFalse($score->isBinary());
    }
}

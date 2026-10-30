<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\Decision\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\Decision\Enums\DecisionQuestionTypeEnum;
use WordPress\AiClient\Tests\traits\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Providers\Models\Decision\Enums\DecisionQuestionTypeEnum
 */
class DecisionQuestionTypeEnumTest extends TestCase
{
    use EnumTestTrait;

    /**
     * Gets the enum class to test.
     *
     * @return string
     */
    protected function getEnumClass(): string
    {
        return DecisionQuestionTypeEnum::class;
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
        $binary = DecisionQuestionTypeEnum::binary();
        $this->assertTrue($binary->isBinary());
        $this->assertFalse($binary->isChoice());

        $choice = DecisionQuestionTypeEnum::choice();
        $this->assertTrue($choice->isChoice());
        $this->assertFalse($choice->isScore());

        $score = DecisionQuestionTypeEnum::score();
        $this->assertTrue($score->isScore());
        $this->assertFalse($score->isBinary());
    }
}

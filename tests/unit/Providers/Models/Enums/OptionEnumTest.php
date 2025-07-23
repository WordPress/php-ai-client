<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Tests\unit\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Providers\Models\Enums\OptionEnum
 */
class OptionEnumTest extends TestCase
{
    use EnumTestTrait;

    /**
     * Gets the enum class to test.
     *
     * @return string
     */
    protected function getEnumClass(): string
    {
        return OptionEnum::class;
    }

    /**
     * Gets the expected enum values.
     *
     * @return array
     */
    protected function getExpectedValues(): array
    {
        return [
            'INPUT_MODALITIES' => 'input_modalities',
            'OUTPUT_MODALITIES' => 'output_modalities',
            'SYSTEM_INSTRUCTION' => 'system_instruction',
            'CANDIDATE_COUNT' => 'candidate_count',
            'MAX_TOKENS' => 'max_tokens',
            'TEMPERATURE' => 'temperature',
            'TOP_K' => 'top_k',
            'TOP_P' => 'top_p',
            'OUTPUT_MIME_TYPE' => 'output_mime_type',
            'OUTPUT_SCHEMA' => 'output_schema',
        ];
    }

    /**
     * Tests the specific enum methods.
     *
     * @return void
     */
    public function testSpecificEnumMethods(): void
    {
        $inputModalities = OptionEnum::inputModalities();
        $this->assertTrue($inputModalities->isInputModalities());
        $this->assertFalse($inputModalities->isOutputModalities());

        $temperature = OptionEnum::temperature();
        $this->assertTrue($temperature->isTemperature());
        $this->assertFalse($temperature->isTopK());

        $outputSchema = OptionEnum::outputSchema();
        $this->assertTrue($outputSchema->isOutputSchema());
        $this->assertFalse($outputSchema->isOutputMimeType());
    }
}

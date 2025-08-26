<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Tests\traits\EnumTestTrait;

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
            // Explicitly defined constant (not in ModelConfig)
            'INPUT_MODALITIES' => 'input_modalities',

            // Dynamically added from ModelConfig KEY_* constants
            'OUTPUT_MODALITIES' => 'outputModalities',
            'SYSTEM_INSTRUCTION' => 'systemInstruction',
            'CANDIDATE_COUNT' => 'candidateCount',
            'MAX_TOKENS' => 'maxTokens',
            'TEMPERATURE' => 'temperature',
            'TOP_P' => 'topP',
            'TOP_K' => 'topK',
            'STOP_SEQUENCES' => 'stopSequences',
            'PRESENCE_PENALTY' => 'presencePenalty',
            'FREQUENCY_PENALTY' => 'frequencyPenalty',
            'LOGPROBS' => 'logprobs',
            'TOP_LOGPROBS' => 'topLogprobs',
            'FUNCTION_DECLARATIONS' => 'functionDeclarations',
            'WEB_SEARCH' => 'webSearch',
            'OUTPUT_FILE_TYPE' => 'outputFileType',
            'OUTPUT_MIME_TYPE' => 'outputMimeType',
            'OUTPUT_SCHEMA' => 'outputSchema',
            'OUTPUT_MEDIA_ORIENTATION' => 'outputMediaOrientation',
            'OUTPUT_MEDIA_ASPECT_RATIO' => 'outputMediaAspectRatio',
            'CUSTOM_OPTIONS' => 'customOptions',
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

    /**
     * Tests that dynamically loaded constants from ModelConfig work.
     *
     * @return void
     */
    public function testDynamicallyLoadedConstants(): void
    {
        // Test a dynamically loaded constant
        $stopSequences = OptionEnum::stopSequences();
        $this->assertInstanceOf(OptionEnum::class, $stopSequences);
        $this->assertEquals('stopSequences', $stopSequences->value);
        $this->assertTrue($stopSequences->isStopSequences());
        $this->assertFalse($stopSequences->isTemperature());

        // Test another dynamically loaded constant
        $presencePenalty = OptionEnum::presencePenalty();
        $this->assertInstanceOf(OptionEnum::class, $presencePenalty);
        $this->assertEquals('presencePenalty', $presencePenalty->value);
        $this->assertTrue($presencePenalty->isPresencePenalty());
        $this->assertFalse($presencePenalty->isFrequencyPenalty());

        // Test that all expected dynamic constants are available
        $this->assertInstanceOf(OptionEnum::class, OptionEnum::frequencyPenalty());
        $this->assertInstanceOf(OptionEnum::class, OptionEnum::logprobs());
        $this->assertInstanceOf(OptionEnum::class, OptionEnum::topLogprobs());
        $this->assertInstanceOf(OptionEnum::class, OptionEnum::functionDeclarations());
        $this->assertInstanceOf(OptionEnum::class, OptionEnum::webSearch());
        $this->assertInstanceOf(OptionEnum::class, OptionEnum::outputFileType());
        $this->assertInstanceOf(OptionEnum::class, OptionEnum::outputMediaOrientation());
        $this->assertInstanceOf(OptionEnum::class, OptionEnum::outputMediaAspectRatio());
        $this->assertInstanceOf(OptionEnum::class, OptionEnum::customOptions());
    }

    /**
     * Tests that getValues includes all dynamically loaded constants.
     *
     * @return void
     */
    public function testGetValuesIncludesDynamicConstants(): void
    {
        $values = OptionEnum::getValues();

        // Check that dynamic values are included
        $this->assertContains('stopSequences', $values);
        $this->assertContains('presencePenalty', $values);
        $this->assertContains('frequencyPenalty', $values);
        $this->assertContains('logprobs', $values);
        $this->assertContains('topLogprobs', $values);
        $this->assertContains('functionDeclarations', $values);
        $this->assertContains('webSearch', $values);
        $this->assertContains('outputFileType', $values);
        $this->assertContains('outputMediaOrientation', $values);
        $this->assertContains('outputMediaAspectRatio', $values);
        $this->assertContains('customOptions', $values);
    }
}

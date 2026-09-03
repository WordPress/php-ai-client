<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\ApiBasedImplementation;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

/**
 * Tests for model capability introspection methods:
 * getCapabilities(), supportsInput(), supportsOutput().
 *
 * @covers \WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel
 */
class ModelCapabilitiesTest extends TestCase
{
    /**
     * @var ProviderMetadata
     */
    private $providerMetadata;

    protected function setUp(): void
    {
        parent::setUp();
        $this->providerMetadata = $this->createStub(ProviderMetadata::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Builds a MockApiBasedModel with the given supported options.
     *
     * @param list<SupportedOption> $options
     * @return MockApiBasedModel
     */
    private function makeModel(array $options): MockApiBasedModel
    {
        $metadata = new ModelMetadata(
            'test-model',
            'Test Model',
            [CapabilityEnum::textGeneration()],
            $options
        );
        return new MockApiBasedModel($metadata, $this->providerMetadata);
    }

    // -------------------------------------------------------------------------
    // getCapabilities()
    // -------------------------------------------------------------------------

    /**
     * Tests that getCapabilities() returns the correct structure when both
     * input and output modalities are defined using ModalityEnum instances.
     *
     * @return void
     */
    public function testGetCapabilitiesReturnsBothModalities(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::inputModalities(),
                [ModalityEnum::text(), ModalityEnum::image()]
            ),
            new SupportedOption(
                OptionEnum::outputModalities(),
                [ModalityEnum::text()]
            ),
        ]);

        $capabilities = $model->getCapabilities();

        $this->assertArrayHasKey('input', $capabilities);
        $this->assertArrayHasKey('output', $capabilities);

        $this->assertCount(2, $capabilities['input']);
        $this->assertSame(ModalityEnum::TEXT, $capabilities['input'][0]->value);
        $this->assertSame(ModalityEnum::IMAGE, $capabilities['input'][1]->value);

        $this->assertCount(1, $capabilities['output']);
        $this->assertSame(ModalityEnum::TEXT, $capabilities['output'][0]->value);
    }

    /**
     * Tests that getCapabilities() returns empty lists when no modality options are defined.
     *
     * @return void
     */
    public function testGetCapabilitiesReturnsEmptyListsWhenNoModalitiesDefined(): void
    {
        $model = $this->makeModel([]);

        $capabilities = $model->getCapabilities();

        $this->assertArrayHasKey('input', $capabilities);
        $this->assertArrayHasKey('output', $capabilities);
        $this->assertSame([], $capabilities['input']);
        $this->assertSame([], $capabilities['output']);
    }

    /**
     * Tests that getCapabilities() returns an empty input list when only
     * output modalities are present.
     *
     * @return void
     */
    public function testGetCapabilitiesWithOnlyOutputModalitiesDefined(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::outputModalities(),
                [ModalityEnum::audio()]
            ),
        ]);

        $capabilities = $model->getCapabilities();

        $this->assertSame([], $capabilities['input']);
        $this->assertCount(1, $capabilities['output']);
        $this->assertSame(ModalityEnum::AUDIO, $capabilities['output'][0]->value);
    }

    /**
     * Tests that getCapabilities() returns an empty output list when only
     * input modalities are present.
     *
     * @return void
     */
    public function testGetCapabilitiesWithOnlyInputModalitiesDefined(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::inputModalities(),
                [ModalityEnum::text(), ModalityEnum::audio()]
            ),
        ]);

        $capabilities = $model->getCapabilities();

        $this->assertCount(2, $capabilities['input']);
        $this->assertSame([], $capabilities['output']);
    }

    /**
     * Tests that getCapabilities() converts string values (e.g. from
     * deserialized metadata) to ModalityEnum instances.
     *
     * @return void
     */
    public function testGetCapabilitiesConvertsStringValuesToModalityEnum(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::inputModalities(),
                [ModalityEnum::TEXT, ModalityEnum::IMAGE]   // plain strings
            ),
            new SupportedOption(
                OptionEnum::outputModalities(),
                [ModalityEnum::TEXT]
            ),
        ]);

        $capabilities = $model->getCapabilities();

        $this->assertInstanceOf(ModalityEnum::class, $capabilities['input'][0]);
        $this->assertSame(ModalityEnum::TEXT, $capabilities['input'][0]->value);
        $this->assertInstanceOf(ModalityEnum::class, $capabilities['output'][0]);
    }

    /**
     * Tests that getCapabilities() returns empty lists when the supported
     * values for a modality option is null (i.e. "any value accepted").
     *
     * @return void
     */
    public function testGetCapabilitiesReturnsEmptyListWhenSupportedValuesIsNull(): void
    {
        $model = $this->makeModel([
            new SupportedOption(OptionEnum::inputModalities(), null),
            new SupportedOption(OptionEnum::outputModalities(), null),
        ]);

        $capabilities = $model->getCapabilities();

        $this->assertSame([], $capabilities['input']);
        $this->assertSame([], $capabilities['output']);
    }

    /**
     * Tests getCapabilities() with all available ModalityEnum values.
     *
     * @return void
     */
    public function testGetCapabilitiesWithAllModalityValues(): void
    {
        $allModalities = [
            ModalityEnum::text(),
            ModalityEnum::document(),
            ModalityEnum::image(),
            ModalityEnum::audio(),
            ModalityEnum::video(),
        ];

        $model = $this->makeModel([
            new SupportedOption(OptionEnum::inputModalities(), $allModalities),
            new SupportedOption(OptionEnum::outputModalities(), $allModalities),
        ]);

        $capabilities = $model->getCapabilities();

        $this->assertCount(5, $capabilities['input']);
        $this->assertCount(5, $capabilities['output']);
    }

    // -------------------------------------------------------------------------
    // supportsInput()
    // -------------------------------------------------------------------------

    /**
     * Tests that supportsInput() returns true for a modality the model supports.
     *
     * @return void
     */
    public function testSupportsInputReturnsTrueForSupportedModality(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::inputModalities(),
                [ModalityEnum::text(), ModalityEnum::image()]
            ),
        ]);

        $this->assertTrue($model->supportsInput(ModalityEnum::text()));
        $this->assertTrue($model->supportsInput(ModalityEnum::image()));
    }

    /**
     * Tests that supportsInput() returns false for a modality not in the list.
     *
     * @return void
     */
    public function testSupportsInputReturnsFalseForUnsupportedModality(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::inputModalities(),
                [ModalityEnum::text()]
            ),
        ]);

        $this->assertFalse($model->supportsInput(ModalityEnum::audio()));
        $this->assertFalse($model->supportsInput(ModalityEnum::video()));
        $this->assertFalse($model->supportsInput(ModalityEnum::image()));
    }

    /**
     * Tests that supportsInput() returns false when no input modalities are defined.
     *
     * @return void
     */
    public function testSupportsInputReturnsFalseWhenNoModalitiesDefined(): void
    {
        $model = $this->makeModel([]);

        $this->assertFalse($model->supportsInput(ModalityEnum::text()));
    }

    /**
     * Tests that supportsInput() does NOT check output modalities.
     *
     * @return void
     */
    public function testSupportsInputDoesNotCheckOutputModalities(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::outputModalities(),
                [ModalityEnum::audio()]
            ),
        ]);

        // audio is only in output, supportsInput should return false
        $this->assertFalse($model->supportsInput(ModalityEnum::audio()));
    }

    // -------------------------------------------------------------------------
    // supportsOutput()
    // -------------------------------------------------------------------------

    /**
     * Tests that supportsOutput() returns true for a modality the model supports.
     *
     * @return void
     */
    public function testSupportsOutputReturnsTrueForSupportedModality(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::outputModalities(),
                [ModalityEnum::text(), ModalityEnum::audio()]
            ),
        ]);

        $this->assertTrue($model->supportsOutput(ModalityEnum::text()));
        $this->assertTrue($model->supportsOutput(ModalityEnum::audio()));
    }

    /**
     * Tests that supportsOutput() returns false for a modality not in the list.
     *
     * @return void
     */
    public function testSupportsOutputReturnsFalseForUnsupportedModality(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::outputModalities(),
                [ModalityEnum::text()]
            ),
        ]);

        $this->assertFalse($model->supportsOutput(ModalityEnum::image()));
        $this->assertFalse($model->supportsOutput(ModalityEnum::video()));
        $this->assertFalse($model->supportsOutput(ModalityEnum::audio()));
    }

    /**
     * Tests that supportsOutput() returns false when no output modalities are defined.
     *
     * @return void
     */
    public function testSupportsOutputReturnsFalseWhenNoModalitiesDefined(): void
    {
        $model = $this->makeModel([]);

        $this->assertFalse($model->supportsOutput(ModalityEnum::text()));
    }

    /**
     * Tests that supportsOutput() does NOT check input modalities.
     *
     * @return void
     */
    public function testSupportsOutputDoesNotCheckInputModalities(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::inputModalities(),
                [ModalityEnum::image()]
            ),
        ]);

        // image is only in input, supportsOutput should return false
        $this->assertFalse($model->supportsOutput(ModalityEnum::image()));
    }

    // -------------------------------------------------------------------------
    // ModelInterface contract
    // -------------------------------------------------------------------------

    /**
     * Tests that the model instance correctly implements ModelInterface.
     *
     * @return void
     */
    public function testImplementsModelInterface(): void
    {
        $model = $this->makeModel([]);

        $this->assertIsArray($model->getCapabilities());
        $this->assertArrayHasKey('input', $model->getCapabilities());
        $this->assertArrayHasKey('output', $model->getCapabilities());
    }

    /**
     * Tests a realistic text-generation model scenario (text+image → text).
     *
     * @return void
     */
    public function testRealisticTextGenerationModel(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::inputModalities(),
                [ModalityEnum::text(), ModalityEnum::image()]
            ),
            new SupportedOption(
                OptionEnum::outputModalities(),
                [ModalityEnum::text()]
            ),
        ]);

        $capabilities = $model->getCapabilities();

        // Verify full capabilities shape
        $this->assertCount(2, $capabilities['input']);
        $this->assertCount(1, $capabilities['output']);

        // Input: text, image
        $this->assertTrue($model->supportsInput(ModalityEnum::text()));
        $this->assertTrue($model->supportsInput(ModalityEnum::image()));
        $this->assertFalse($model->supportsInput(ModalityEnum::audio()));
        $this->assertFalse($model->supportsInput(ModalityEnum::video()));

        // Output: text only
        $this->assertTrue($model->supportsOutput(ModalityEnum::text()));
        $this->assertFalse($model->supportsOutput(ModalityEnum::image()));
        $this->assertFalse($model->supportsOutput(ModalityEnum::audio()));
    }

    /**
     * Tests a realistic text-to-speech model (text → audio).
     *
     * @return void
     */
    public function testRealisticTextToSpeechModel(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::inputModalities(),
                [ModalityEnum::text()]
            ),
            new SupportedOption(
                OptionEnum::outputModalities(),
                [ModalityEnum::audio()]
            ),
        ]);

        $this->assertTrue($model->supportsInput(ModalityEnum::text()));
        $this->assertFalse($model->supportsInput(ModalityEnum::audio()));

        $this->assertTrue($model->supportsOutput(ModalityEnum::audio()));
        $this->assertFalse($model->supportsOutput(ModalityEnum::text()));
    }

    /**
     * Tests a realistic audio transcription model (audio → text).
     *
     * @return void
     */
    public function testRealisticAudioTranscriptionModel(): void
    {
        $model = $this->makeModel([
            new SupportedOption(
                OptionEnum::inputModalities(),
                [ModalityEnum::audio()]
            ),
            new SupportedOption(
                OptionEnum::outputModalities(),
                [ModalityEnum::text()]
            ),
        ]);

        $this->assertTrue($model->supportsInput(ModalityEnum::audio()));
        $this->assertFalse($model->supportsInput(ModalityEnum::text()));

        $this->assertTrue($model->supportsOutput(ModalityEnum::text()));
        $this->assertFalse($model->supportsOutput(ModalityEnum::audio()));
    }
}

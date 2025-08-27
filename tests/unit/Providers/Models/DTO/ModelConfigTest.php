<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\WebSearch;

/**
 * @covers \WordPress\AiClient\Providers\Models\DTO\ModelConfig
 */
class ModelConfigTest extends TestCase
{
    /**
     * Creates a sample function declaration for testing.
     *
     * @return FunctionDeclaration
     */
    private function createSampleFunctionDeclaration(): FunctionDeclaration
    {
        return new FunctionDeclaration(
            'test_function',
            'A test function',
            ['type' => 'object', 'properties' => []]
        );
    }

    /**
     * Creates a sample web search for testing.
     *
     * @return WebSearch
     */
    private function createSampleWebSearch(): WebSearch
    {
        return new WebSearch(['example.com'], ['disallowed.com']);
    }

    /**
     * Tests default constructor creates empty config.
     *
     * @return void
     */
    public function testDefaultConstructor(): void
    {
        $config = new ModelConfig();

        $this->assertNull($config->getOutputModalities());
        $this->assertNull($config->getSystemInstruction());
        $this->assertNull($config->getCandidateCount());
        $this->assertNull($config->getMaxTokens());
        $this->assertNull($config->getTemperature());
        $this->assertNull($config->getTopP());
        $this->assertNull($config->getTopK());
        $this->assertNull($config->getStopSequences());
        $this->assertNull($config->getPresencePenalty());
        $this->assertNull($config->getFrequencyPenalty());
        $this->assertNull($config->getLogprobs());
        $this->assertNull($config->getTopLogprobs());
        $this->assertNull($config->getFunctionDeclarations());
        $this->assertNull($config->getWebSearch());
        $this->assertNull($config->getOutputFileType());
        $this->assertNull($config->getOutputMimeType());
        $this->assertNull($config->getOutputSchema());
        $this->assertNull($config->getOutputMediaOrientation());
        $this->assertNull($config->getOutputMediaAspectRatio());
        $this->assertEquals([], $config->getCustomOptions());
    }

    /**
     * Tests setter and getter methods.
     *
     * @return void
     */
    public function testSettersAndGetters(): void
    {
        $config = new ModelConfig();

        // Test output modalities
        $modalities = [ModalityEnum::text(), ModalityEnum::image()];
        $config->setOutputModalities($modalities);
        $this->assertEquals($modalities, $config->getOutputModalities());

        // Test system instruction
        $instruction = 'You are a helpful assistant.';
        $config->setSystemInstruction($instruction);
        $this->assertEquals($instruction, $config->getSystemInstruction());

        // Test candidate count
        $config->setCandidateCount(3);
        $this->assertEquals(3, $config->getCandidateCount());

        // Test max tokens
        $config->setMaxTokens(1000);
        $this->assertEquals(1000, $config->getMaxTokens());

        // Test temperature
        $config->setTemperature(0.7);
        $this->assertEquals(0.7, $config->getTemperature());

        // Test top-p
        $config->setTopP(0.9);
        $this->assertEquals(0.9, $config->getTopP());

        // Test top-k
        $config->setTopK(40);
        $this->assertEquals(40, $config->getTopK());

        // Test stop sequences
        $stopSequences = ['\n\n', 'END'];
        $config->setStopSequences($stopSequences);
        $this->assertEquals($stopSequences, $config->getStopSequences());

        // Test presence penalty
        $config->setPresencePenalty(0.5);
        $this->assertEquals(0.5, $config->getPresencePenalty());

        // Test frequency penalty
        $config->setFrequencyPenalty(0.3);
        $this->assertEquals(0.3, $config->getFrequencyPenalty());

        // Test logprobs
        $config->setLogprobs(true);
        $this->assertTrue($config->getLogprobs());

        // Test top logprobs
        $config->setTopLogprobs(5);
        $this->assertEquals(5, $config->getTopLogprobs());

        // Test function declarations
        $functionDeclarations = [$this->createSampleFunctionDeclaration()];
        $config->setFunctionDeclarations($functionDeclarations);
        $this->assertEquals($functionDeclarations, $config->getFunctionDeclarations());

        // Test web search
        $webSearch = $this->createSampleWebSearch();
        $config->setWebSearch($webSearch);
        $this->assertEquals($webSearch, $config->getWebSearch());

        // Test output MIME type
        $config->setOutputMimeType('application/json');
        $this->assertEquals('application/json', $config->getOutputMimeType());

        // Test output schema
        $outputSchema = [
            'type' => 'object',
            'properties' => [
                'result' => ['type' => 'string']
            ]
        ];
        $config->setOutputSchema($outputSchema);
        $this->assertEquals($outputSchema, $config->getOutputSchema());

        // Test output file type
        $config->setOutputFileType(FileTypeEnum::inline());
        $this->assertEquals(FileTypeEnum::inline(), $config->getOutputFileType());

        // Test output media orientation
        $config->setOutputMediaOrientation(MediaOrientationEnum::landscape());
        $this->assertEquals(MediaOrientationEnum::landscape(), $config->getOutputMediaOrientation());

        // Test output media aspect ratio
        $config->setOutputMediaAspectRatio('4:3');
        $this->assertEquals('4:3', $config->getOutputMediaAspectRatio());

        // Test custom options
        $customOptions = ['custom_param' => 'value', 'another_param' => 123];
        $config->setCustomOptions($customOptions);
        $this->assertEquals($customOptions, $config->getCustomOptions());
    }

    /**
     * Tests JSON schema generation.
     *
     * @return void
     */
    public function testGetJsonSchema(): void
    {
        $schema = ModelConfig::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertFalse($schema['additionalProperties']);

        // Check all properties exist
        $expectedProperties = [
            ModelConfig::KEY_OUTPUT_MODALITIES,
            ModelConfig::KEY_SYSTEM_INSTRUCTION,
            ModelConfig::KEY_CANDIDATE_COUNT,
            ModelConfig::KEY_MAX_TOKENS,
            ModelConfig::KEY_TEMPERATURE,
            ModelConfig::KEY_TOP_P,
            ModelConfig::KEY_TOP_K,
            ModelConfig::KEY_STOP_SEQUENCES,
            ModelConfig::KEY_PRESENCE_PENALTY,
            ModelConfig::KEY_FREQUENCY_PENALTY,
            ModelConfig::KEY_LOGPROBS,
            ModelConfig::KEY_TOP_LOGPROBS,
            ModelConfig::KEY_FUNCTION_DECLARATIONS,
            ModelConfig::KEY_WEB_SEARCH,
            ModelConfig::KEY_OUTPUT_FILE_TYPE,
            ModelConfig::KEY_OUTPUT_MIME_TYPE,
            ModelConfig::KEY_OUTPUT_SCHEMA,
            ModelConfig::KEY_OUTPUT_MEDIA_ORIENTATION,
            ModelConfig::KEY_OUTPUT_MEDIA_ASPECT_RATIO,
            ModelConfig::KEY_CUSTOM_OPTIONS
        ];

        foreach ($expectedProperties as $property) {
            $this->assertArrayHasKey($property, $schema['properties']);
        }

        // Check specific property schemas
        $this->assertEquals('array', $schema['properties'][ModelConfig::KEY_OUTPUT_MODALITIES]['type']);
        $this->assertEquals('string', $schema['properties'][ModelConfig::KEY_SYSTEM_INSTRUCTION]['type']);
        $this->assertEquals('integer', $schema['properties'][ModelConfig::KEY_CANDIDATE_COUNT]['type']);
        $this->assertEquals('number', $schema['properties'][ModelConfig::KEY_TEMPERATURE]['type']);
        $this->assertEquals('boolean', $schema['properties'][ModelConfig::KEY_LOGPROBS]['type']);
        $this->assertEquals('string', $schema['properties'][ModelConfig::KEY_OUTPUT_MIME_TYPE]['type']);
        $this->assertEquals('object', $schema['properties'][ModelConfig::KEY_OUTPUT_SCHEMA]['type']);
        $this->assertEquals('string', $schema['properties'][ModelConfig::KEY_OUTPUT_FILE_TYPE]['type']);
        $this->assertEquals('string', $schema['properties'][ModelConfig::KEY_OUTPUT_MEDIA_ORIENTATION]['type']);
        $this->assertEquals('string', $schema['properties'][ModelConfig::KEY_OUTPUT_MEDIA_ASPECT_RATIO]['type']);
        $this->assertEquals('object', $schema['properties'][ModelConfig::KEY_CUSTOM_OPTIONS]['type']);

        // Check constraints
        $this->assertEquals(1, $schema['properties'][ModelConfig::KEY_CANDIDATE_COUNT]['minimum']);
        $this->assertEquals(0.0, $schema['properties'][ModelConfig::KEY_TEMPERATURE]['minimum']);
        $this->assertEquals(2.0, $schema['properties'][ModelConfig::KEY_TEMPERATURE]['maximum']);
        $this->assertEquals(0.0, $schema['properties'][ModelConfig::KEY_TOP_P]['minimum']);
        $this->assertEquals(1.0, $schema['properties'][ModelConfig::KEY_TOP_P]['maximum']);
    }

    /**
     * Tests array conversion with all properties set.
     *
     * @return void
     */
    public function testToArrayAllProperties(): void
    {
        $config = new ModelConfig();

        $config->setOutputModalities([ModalityEnum::text(), ModalityEnum::audio()]);
        $config->setSystemInstruction('Test instruction');
        $config->setCandidateCount(2);
        $config->setMaxTokens(500);
        $config->setTemperature(1.2);
        $config->setTopP(0.8);
        $config->setTopK(30);
        $config->setStopSequences(['STOP', 'END']);
        $config->setPresencePenalty(0.6);
        $config->setFrequencyPenalty(0.4);
        $config->setLogprobs(true);
        $config->setTopLogprobs(10);
        $config->setFunctionDeclarations([$this->createSampleFunctionDeclaration()]);
        $config->setWebSearch($this->createSampleWebSearch());
        $config->setOutputFileType(FileTypeEnum::remote());
        $config->setOutputMimeType('application/json');
        $config->setOutputSchema(['type' => 'object']);
        $config->setOutputMediaOrientation(MediaOrientationEnum::portrait());
        $config->setOutputMediaAspectRatio('9:16');
        $config->setCustomOptions(['key' => 'value']);

        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(['text', 'audio'], $array[ModelConfig::KEY_OUTPUT_MODALITIES]);
        $this->assertEquals('Test instruction', $array[ModelConfig::KEY_SYSTEM_INSTRUCTION]);
        $this->assertEquals(2, $array[ModelConfig::KEY_CANDIDATE_COUNT]);
        $this->assertEquals(500, $array[ModelConfig::KEY_MAX_TOKENS]);
        $this->assertEquals(1.2, $array[ModelConfig::KEY_TEMPERATURE]);
        $this->assertEquals(0.8, $array[ModelConfig::KEY_TOP_P]);
        $this->assertEquals(30, $array[ModelConfig::KEY_TOP_K]);
        $this->assertEquals(['STOP', 'END'], $array[ModelConfig::KEY_STOP_SEQUENCES]);
        $this->assertEquals(0.6, $array[ModelConfig::KEY_PRESENCE_PENALTY]);
        $this->assertEquals(0.4, $array[ModelConfig::KEY_FREQUENCY_PENALTY]);
        $this->assertTrue($array[ModelConfig::KEY_LOGPROBS]);
        $this->assertEquals(10, $array[ModelConfig::KEY_TOP_LOGPROBS]);
        $this->assertCount(1, $array[ModelConfig::KEY_FUNCTION_DECLARATIONS]);
        $this->assertEquals($this->createSampleWebSearch()->toArray(), $array[ModelConfig::KEY_WEB_SEARCH]);
        $this->assertEquals('remote', $array[ModelConfig::KEY_OUTPUT_FILE_TYPE]);
        $this->assertEquals('application/json', $array[ModelConfig::KEY_OUTPUT_MIME_TYPE]);
        $this->assertEquals(['type' => 'object'], $array[ModelConfig::KEY_OUTPUT_SCHEMA]);
        $this->assertEquals('portrait', $array[ModelConfig::KEY_OUTPUT_MEDIA_ORIENTATION]);
        $this->assertEquals('9:16', $array[ModelConfig::KEY_OUTPUT_MEDIA_ASPECT_RATIO]);
        $this->assertEquals(['key' => 'value'], $array[ModelConfig::KEY_CUSTOM_OPTIONS]);
    }

    /**
     * Tests array conversion with no properties set.
     *
     * @return void
     */
    public function testToArrayNoProperties(): void
    {
        $config = new ModelConfig();
        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertCount(0, $array);
        $this->assertArrayNotHasKey(ModelConfig::KEY_CUSTOM_OPTIONS, $array);
    }

    /**
     * Tests array conversion with partial properties set.
     *
     * @return void
     */
    public function testToArrayPartialProperties(): void
    {
        $config = new ModelConfig();
        $config->setTemperature(0.5);
        $config->setMaxTokens(100);

        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertEquals(0.5, $array[ModelConfig::KEY_TEMPERATURE]);
        $this->assertEquals(100, $array[ModelConfig::KEY_MAX_TOKENS]);
        $this->assertArrayNotHasKey(ModelConfig::KEY_CUSTOM_OPTIONS, $array);
        $this->assertArrayNotHasKey(ModelConfig::KEY_SYSTEM_INSTRUCTION, $array);
        $this->assertArrayNotHasKey(ModelConfig::KEY_TOP_P, $array);
    }

    /**
     * Tests custom options are only included when not empty.
     *
     * @return void
     */
    public function testToArrayCustomOptionsOnlyIncludedWhenNotEmpty(): void
    {
        // Test with empty custom options (default)
        $config = new ModelConfig();
        $config->setTemperature(0.7);
        
        $array = $config->toArray();
        $this->assertArrayNotHasKey(ModelConfig::KEY_CUSTOM_OPTIONS, $array);
        
        // Test with non-empty custom options
        $config->setCustomOption('key1', 'value1');
        $array = $config->toArray();
        $this->assertArrayHasKey(ModelConfig::KEY_CUSTOM_OPTIONS, $array);
        $this->assertEquals(['key1' => 'value1'], $array[ModelConfig::KEY_CUSTOM_OPTIONS]);
        
        // Test with multiple custom options
        $config->setCustomOption('key2', ['nested' => 'value']);
        $array = $config->toArray();
        $this->assertArrayHasKey(ModelConfig::KEY_CUSTOM_OPTIONS, $array);
        $this->assertEquals([
            'key1' => 'value1',
            'key2' => ['nested' => 'value']
        ], $array[ModelConfig::KEY_CUSTOM_OPTIONS]);
        
        // Test resetting custom options to empty
        $config->setCustomOptions([]);
        $array = $config->toArray();
        $this->assertArrayNotHasKey(ModelConfig::KEY_CUSTOM_OPTIONS, $array);
    }

    /**
     * Tests creating from array with all properties.
     *
     * @return void
     */
    public function testFromArrayAllProperties(): void
    {
        $data = [
            ModelConfig::KEY_OUTPUT_MODALITIES => ['text', 'image'],
            ModelConfig::KEY_SYSTEM_INSTRUCTION => 'Be helpful',
            ModelConfig::KEY_CANDIDATE_COUNT => 3,
            ModelConfig::KEY_MAX_TOKENS => 2000,
            ModelConfig::KEY_TEMPERATURE => 0.9,
            ModelConfig::KEY_TOP_P => 0.95,
            ModelConfig::KEY_TOP_K => 50,
            ModelConfig::KEY_STOP_SEQUENCES => ['###', 'DONE'],
            ModelConfig::KEY_PRESENCE_PENALTY => 0.2,
            ModelConfig::KEY_FREQUENCY_PENALTY => 0.1,
            ModelConfig::KEY_LOGPROBS => false,
            ModelConfig::KEY_TOP_LOGPROBS => 3,
            ModelConfig::KEY_FUNCTION_DECLARATIONS => [
                [
                    'name' => 'test_func',
                    'description' => 'Test function',
                    'parameters' => ['type' => 'object']
                ]
            ],
            ModelConfig::KEY_WEB_SEARCH => [
                'allowedDomains' => ['example.com'],
                'disallowedDomains' => ['disallowed.com'],
            ],
            ModelConfig::KEY_OUTPUT_MIME_TYPE => 'application/json',
            ModelConfig::KEY_OUTPUT_SCHEMA => ['type' => 'array', 'items' => ['type' => 'string']],
            ModelConfig::KEY_OUTPUT_FILE_TYPE => 'inline',
            ModelConfig::KEY_OUTPUT_MEDIA_ORIENTATION => 'landscape',
            ModelConfig::KEY_OUTPUT_MEDIA_ASPECT_RATIO => '16:9',
            ModelConfig::KEY_CUSTOM_OPTIONS => ['custom' => true]
        ];

        $config = ModelConfig::fromArray($data);

        $this->assertInstanceOf(ModelConfig::class, $config);
        $this->assertCount(2, $config->getOutputModalities());
        $this->assertTrue($config->getOutputModalities()[0]->isText());
        $this->assertTrue($config->getOutputModalities()[1]->isImage());
        $this->assertEquals('Be helpful', $config->getSystemInstruction());
        $this->assertEquals(3, $config->getCandidateCount());
        $this->assertEquals(2000, $config->getMaxTokens());
        $this->assertEquals(0.9, $config->getTemperature());
        $this->assertEquals(0.95, $config->getTopP());
        $this->assertEquals(50, $config->getTopK());
        $this->assertEquals(['###', 'DONE'], $config->getStopSequences());
        $this->assertEquals(0.2, $config->getPresencePenalty());
        $this->assertEquals(0.1, $config->getFrequencyPenalty());
        $this->assertFalse($config->getLogprobs());
        $this->assertEquals(3, $config->getTopLogprobs());
        $this->assertCount(1, $config->getFunctionDeclarations());
        $this->assertInstanceOf(WebSearch::class, $config->getWebSearch());
        $this->assertEquals(['example.com'], $config->getWebSearch()->getAllowedDomains());
        $this->assertEquals(['disallowed.com'], $config->getWebSearch()->getDisallowedDomains());
        $this->assertEquals('application/json', $config->getOutputMimeType());
        $this->assertEquals(['type' => 'array', 'items' => ['type' => 'string']], $config->getOutputSchema());
        $this->assertEquals(FileTypeEnum::inline(), $config->getOutputFileType());
        $this->assertEquals(MediaOrientationEnum::landscape(), $config->getOutputMediaOrientation());
        $this->assertEquals('16:9', $config->getOutputMediaAspectRatio());
        $this->assertEquals(['custom' => true], $config->getCustomOptions());
    }

    /**
     * Tests creating from empty array.
     *
     * @return void
     */
    public function testFromArrayEmpty(): void
    {
        $config = ModelConfig::fromArray([]);

        $this->assertInstanceOf(ModelConfig::class, $config);
        $this->assertNull($config->getOutputModalities());
        $this->assertNull($config->getSystemInstruction());
        $this->assertNull($config->getMaxTokens());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $original = new ModelConfig();
        $original->setSystemInstruction('Round trip test');
        $original->setTemperature(0.75);
        $original->setMaxTokens(1500);
        $original->setStopSequences(['END', 'STOP']);
        $original->setLogprobs(true);
        $original->setOutputFileType(FileTypeEnum::inline());
        $original->setOutputMediaOrientation(MediaOrientationEnum::square());
        $original->setOutputMediaAspectRatio('1:1');
        $original->setCustomOptions(['test' => 'value']);

        $array = $original->toArray();
        $restored = ModelConfig::fromArray($array);

        $this->assertEquals($original->getSystemInstruction(), $restored->getSystemInstruction());
        $this->assertEquals($original->getTemperature(), $restored->getTemperature());
        $this->assertEquals($original->getMaxTokens(), $restored->getMaxTokens());
        $this->assertEquals($original->getStopSequences(), $restored->getStopSequences());
        $this->assertEquals($original->getLogprobs(), $restored->getLogprobs());
        $this->assertEquals($original->getOutputFileType(), $restored->getOutputFileType());
        $this->assertEquals($original->getOutputMediaOrientation(), $restored->getOutputMediaOrientation());
        $this->assertEquals($original->getOutputMediaAspectRatio(), $restored->getOutputMediaAspectRatio());
        $this->assertEquals($original->getCustomOptions(), $restored->getCustomOptions());
    }

    /**
     * Tests JSON serialization.
     *
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $config = new ModelConfig();
        $config->setTemperature(0.8);
        $config->setMaxTokens(1000);
        $config->setSystemInstruction('JSON test');

        $json = json_encode($config);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals(0.8, $decoded['temperature']);
        $this->assertEquals(1000, $decoded['maxTokens']);
        $this->assertEquals('JSON test', $decoded['systemInstruction']);
    }

    /**
     * Tests with extreme values.
     *
     * @return void
     */
    public function testExtremeValues(): void
    {
        $config = new ModelConfig();

        // Test minimum values
        $config->setTemperature(0.0);
        $config->setTopP(0.0);
        $config->setTopK(1);
        $config->setCandidateCount(1);
        $config->setMaxTokens(1);
        $config->setPresencePenalty(-2.0);
        $config->setFrequencyPenalty(-2.0);

        $array = $config->toArray();
        $this->assertEquals(0.0, $array['temperature']);
        $this->assertEquals(0.0, $array['topP']);
        $this->assertEquals(1, $array['topK']);

        // Test maximum values
        $config->setTemperature(2.0);
        $config->setTopP(1.0);
        $config->setTopK(999999);
        $config->setCandidateCount(100);
        $config->setMaxTokens(999999);
        $config->setPresencePenalty(2.0);
        $config->setFrequencyPenalty(2.0);

        $array = $config->toArray();
        $this->assertEquals(2.0, $array['temperature']);
        $this->assertEquals(1.0, $array['topP']);
        $this->assertEquals(999999, $array['topK']);
    }

    /**
     * Tests that setters properly set values.
     *
     * @return void
     */
    public function testSettersProperlySetValues(): void
    {
        $config = new ModelConfig();

        $config->setSystemInstruction('test');
        $config->setTemperature(0.5);
        $config->setMaxTokens(100);
        $config->setTopP(0.9);
        $config->setTopK(40);
        $config->setLogprobs(true);

        $this->assertEquals('test', $config->getSystemInstruction());
        $this->assertEquals(0.5, $config->getTemperature());
        $this->assertEquals(100, $config->getMaxTokens());
        $this->assertEquals(0.9, $config->getTopP());
        $this->assertEquals(40, $config->getTopK());
        $this->assertTrue($config->getLogprobs());
    }

    /**
     * Tests array values are properly indexed.
     *
     * @return void
     */
    public function testArrayValuesProperlyIndexed(): void
    {
        $config = new ModelConfig();
        $config->setOutputModalities([
            ModalityEnum::text(),
            ModalityEnum::image(),
            ModalityEnum::audio()
        ]);
        $config->setStopSequences(['stop1', 'stop2', 'stop3']);
        $config->setFunctionDeclarations([
            $this->createSampleFunctionDeclaration(),
            $this->createSampleFunctionDeclaration()
        ]);

        $array = $config->toArray();

        // Check that arrays are properly indexed from 0
        $this->assertEquals([0, 1, 2], array_keys($array['outputModalities']));
        $this->assertEquals([0, 1, 2], array_keys($array['stopSequences']));
        $this->assertEquals([0, 1], array_keys($array['functionDeclarations']));
    }

    /**
     * Tests implements correct interfaces.
     *
     * @return void
     */
    public function testImplementsCorrectInterfaces(): void
    {
        $config = new ModelConfig();

        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface::class,
            $config
        );
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface::class,
            $config
        );
        $this->assertInstanceOf(
            JsonSerializable::class,
            $config
        );
    }

    /**
     * Tests automatic output MIME type setting when schema is provided.
     *
     * @return void
     */
    public function testOutputSchemaAutomaticallySetsJsonMimeType(): void
    {
        $config = new ModelConfig();

        // Test that setting output schema automatically sets MIME type to application/json
        $this->assertNull($config->getOutputMimeType());

        $schema = ['type' => 'object', 'properties' => ['test' => ['type' => 'string']]];
        $config->setOutputSchema($schema);

        $this->assertEquals('application/json', $config->getOutputMimeType());
        $this->assertEquals($schema, $config->getOutputSchema());

        // Test that explicitly set MIME type is not overridden
        $config2 = new ModelConfig();
        $config2->setOutputMimeType('text/plain');
        $config2->setOutputSchema($schema);

        $this->assertEquals('text/plain', $config2->getOutputMimeType());
        $this->assertEquals($schema, $config2->getOutputSchema());
    }

    /**
     * Tests setCustomOption method.
     *
     * @return void
     */
    public function testSetCustomOption(): void
    {
        $config = new ModelConfig();

        // Test setting a single custom option
        $config->setCustomOption('key1', 'value1');
        $customOptions = $config->getCustomOptions();
        $this->assertArrayHasKey('key1', $customOptions);
        $this->assertEquals('value1', $customOptions['key1']);

        // Test setting multiple custom options
        $config->setCustomOption('key2', 42);
        $config->setCustomOption('key3', true);
        $config->setCustomOption('key4', ['nested' => 'array']);

        $customOptions = $config->getCustomOptions();
        $this->assertCount(4, $customOptions);
        $this->assertEquals('value1', $customOptions['key1']);
        $this->assertEquals(42, $customOptions['key2']);
        $this->assertTrue($customOptions['key3']);
        $this->assertEquals(['nested' => 'array'], $customOptions['key4']);

        // Test overwriting an existing custom option
        $config->setCustomOption('key1', 'new value');
        $customOptions = $config->getCustomOptions();
        $this->assertEquals('new value', $customOptions['key1']);

        // Test that setCustomOption works with null values
        $config->setCustomOption('nullKey', null);
        $customOptions = $config->getCustomOptions();
        $this->assertArrayHasKey('nullKey', $customOptions);
        $this->assertNull($customOptions['nullKey']);

        // Test that custom options are preserved in array conversion
        $array = $config->toArray();
        $this->assertArrayHasKey(ModelConfig::KEY_CUSTOM_OPTIONS, $array);
        $this->assertEquals($customOptions, $array[ModelConfig::KEY_CUSTOM_OPTIONS]);

        // Test round-trip with custom options set via setCustomOption
        $restored = ModelConfig::fromArray($array);
        $this->assertEquals($customOptions, $restored->getCustomOptions());
    }
}

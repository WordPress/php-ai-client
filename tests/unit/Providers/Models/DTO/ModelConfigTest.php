<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Tools\DTO\Tool;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;

/**
 * @covers \WordPress\AiClient\Providers\Models\DTO\ModelConfig
 */
class ModelConfigTest extends TestCase
{
    /**
     * Creates a sample tool for testing.
     *
     * @return Tool
     */
    private function createSampleTool(): Tool
    {
        $function = new FunctionDeclaration(
            'test_function',
            'A test function',
            ['type' => 'object', 'properties' => []]
        );
        return new Tool([$function]);
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
        $this->assertNull($config->getTools());
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
        $tool = $this->createSampleTool();

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

        // Test tools
        $tools = [$tool];
        $config->setTools($tools);
        $this->assertEquals($tools, $config->getTools());

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
            ModelConfig::KEY_OUTPUT_MODALITIES, ModelConfig::KEY_SYSTEM_INSTRUCTION, ModelConfig::KEY_CANDIDATE_COUNT, ModelConfig::KEY_MAX_TOKENS,
            ModelConfig::KEY_TEMPERATURE, ModelConfig::KEY_TOP_P, ModelConfig::KEY_TOP_K, ModelConfig::KEY_STOP_SEQUENCES, ModelConfig::KEY_PRESENCE_PENALTY,
            ModelConfig::KEY_FREQUENCY_PENALTY, ModelConfig::KEY_LOGPROBS, ModelConfig::KEY_TOP_LOGPROBS, ModelConfig::KEY_TOOLS, ModelConfig::KEY_CUSTOM_OPTIONS
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
        $tool = $this->createSampleTool();

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
        $config->setTools([$tool]);
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
        $this->assertCount(1, $array[ModelConfig::KEY_TOOLS]);
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
        $this->assertCount(1, $array);
        $this->assertArrayHasKey(ModelConfig::KEY_CUSTOM_OPTIONS, $array);
        $this->assertEquals([], $array[ModelConfig::KEY_CUSTOM_OPTIONS]);
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
        $this->assertCount(3, $array);
        $this->assertEquals(0.5, $array[ModelConfig::KEY_TEMPERATURE]);
        $this->assertEquals(100, $array[ModelConfig::KEY_MAX_TOKENS]);
        $this->assertEquals([], $array[ModelConfig::KEY_CUSTOM_OPTIONS]);
        $this->assertArrayNotHasKey(ModelConfig::KEY_SYSTEM_INSTRUCTION, $array);
        $this->assertArrayNotHasKey(ModelConfig::KEY_TOP_P, $array);
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
            ModelConfig::KEY_TOOLS => [
                [
                    'type' => 'function_declarations',
                    'functionDeclarations' => [
                        [
                            'name' => 'test_func',
                            'description' => 'Test function',
                            'parameters' => ['type' => 'object']
                        ]
                    ]
                ]
            ],
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
        $this->assertCount(1, $config->getTools());
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
        $original->setCustomOptions(['test' => 'value']);

        $array = $original->toArray();
        $restored = ModelConfig::fromArray($array);

        $this->assertEquals($original->getSystemInstruction(), $restored->getSystemInstruction());
        $this->assertEquals($original->getTemperature(), $restored->getTemperature());
        $this->assertEquals($original->getMaxTokens(), $restored->getMaxTokens());
        $this->assertEquals($original->getStopSequences(), $restored->getStopSequences());
        $this->assertEquals($original->getLogprobs(), $restored->getLogprobs());
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
        $config->setTools([
            $this->createSampleTool(),
            $this->createSampleTool()
        ]);

        $array = $config->toArray();

        // Check that arrays are properly indexed from 0
        $this->assertEquals([0, 1, 2], array_keys($array['outputModalities']));
        $this->assertEquals([0, 1, 2], array_keys($array['stopSequences']));
        $this->assertEquals([0, 1], array_keys($array['tools']));
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
}

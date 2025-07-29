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
        $this->assertNull($config->getCustomOptions());
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
        $this->assertSame($config, $config->setOutputModalities($modalities));
        $this->assertEquals($modalities, $config->getOutputModalities());

        // Test system instruction
        $instruction = 'You are a helpful assistant.';
        $this->assertSame($config, $config->setSystemInstruction($instruction));
        $this->assertEquals($instruction, $config->getSystemInstruction());

        // Test candidate count
        $this->assertSame($config, $config->setCandidateCount(3));
        $this->assertEquals(3, $config->getCandidateCount());

        // Test max tokens
        $this->assertSame($config, $config->setMaxTokens(1000));
        $this->assertEquals(1000, $config->getMaxTokens());

        // Test temperature
        $this->assertSame($config, $config->setTemperature(0.7));
        $this->assertEquals(0.7, $config->getTemperature());

        // Test top-p
        $this->assertSame($config, $config->setTopP(0.9));
        $this->assertEquals(0.9, $config->getTopP());

        // Test top-k
        $this->assertSame($config, $config->setTopK(40));
        $this->assertEquals(40, $config->getTopK());

        // Test stop sequences
        $stopSequences = ['\n\n', 'END'];
        $this->assertSame($config, $config->setStopSequences($stopSequences));
        $this->assertEquals($stopSequences, $config->getStopSequences());

        // Test presence penalty
        $this->assertSame($config, $config->setPresencePenalty(0.5));
        $this->assertEquals(0.5, $config->getPresencePenalty());

        // Test frequency penalty
        $this->assertSame($config, $config->setFrequencyPenalty(0.3));
        $this->assertEquals(0.3, $config->getFrequencyPenalty());

        // Test logprobs
        $this->assertSame($config, $config->setLogprobs(true));
        $this->assertTrue($config->getLogprobs());

        // Test top logprobs
        $this->assertSame($config, $config->setTopLogprobs(5));
        $this->assertEquals(5, $config->getTopLogprobs());

        // Test tools
        $tools = [$tool];
        $this->assertSame($config, $config->setTools($tools));
        $this->assertEquals($tools, $config->getTools());

        // Test custom options
        $customOptions = ['custom_param' => 'value', 'another_param' => 123];
        $this->assertSame($config, $config->setCustomOptions($customOptions));
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
            'outputModalities', 'systemInstruction', 'candidateCount', 'maxTokens',
            'temperature', 'topP', 'topK', 'stopSequences', 'presencePenalty',
            'frequencyPenalty', 'logprobs', 'topLogprobs', 'tools', 'customOptions'
        ];

        foreach ($expectedProperties as $property) {
            $this->assertArrayHasKey($property, $schema['properties']);
        }

        // Check specific property schemas
        $this->assertEquals('array', $schema['properties']['outputModalities']['type']);
        $this->assertEquals('string', $schema['properties']['systemInstruction']['type']);
        $this->assertEquals('integer', $schema['properties']['candidateCount']['type']);
        $this->assertEquals('number', $schema['properties']['temperature']['type']);
        $this->assertEquals('boolean', $schema['properties']['logprobs']['type']);
        $this->assertEquals('object', $schema['properties']['customOptions']['type']);

        // Check constraints
        $this->assertEquals(1, $schema['properties']['candidateCount']['minimum']);
        $this->assertEquals(0.0, $schema['properties']['temperature']['minimum']);
        $this->assertEquals(2.0, $schema['properties']['temperature']['maximum']);
        $this->assertEquals(0.0, $schema['properties']['topP']['minimum']);
        $this->assertEquals(1.0, $schema['properties']['topP']['maximum']);
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

        $config->setOutputModalities([ModalityEnum::text(), ModalityEnum::audio()])
            ->setSystemInstruction('Test instruction')
            ->setCandidateCount(2)
            ->setMaxTokens(500)
            ->setTemperature(1.2)
            ->setTopP(0.8)
            ->setTopK(30)
            ->setStopSequences(['STOP', 'END'])
            ->setPresencePenalty(0.6)
            ->setFrequencyPenalty(0.4)
            ->setLogprobs(true)
            ->setTopLogprobs(10)
            ->setTools([$tool])
            ->setCustomOptions(['key' => 'value']);

        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(['text', 'audio'], $array['outputModalities']);
        $this->assertEquals('Test instruction', $array['systemInstruction']);
        $this->assertEquals(2, $array['candidateCount']);
        $this->assertEquals(500, $array['maxTokens']);
        $this->assertEquals(1.2, $array['temperature']);
        $this->assertEquals(0.8, $array['topP']);
        $this->assertEquals(30, $array['topK']);
        $this->assertEquals(['STOP', 'END'], $array['stopSequences']);
        $this->assertEquals(0.6, $array['presencePenalty']);
        $this->assertEquals(0.4, $array['frequencyPenalty']);
        $this->assertTrue($array['logprobs']);
        $this->assertEquals(10, $array['topLogprobs']);
        $this->assertCount(1, $array['tools']);
        $this->assertEquals(['key' => 'value'], $array['customOptions']);
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
        $this->assertEmpty($array);
    }

    /**
     * Tests array conversion with partial properties set.
     *
     * @return void
     */
    public function testToArrayPartialProperties(): void
    {
        $config = new ModelConfig();
        $config->setTemperature(0.5)
            ->setMaxTokens(100);

        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertEquals(0.5, $array['temperature']);
        $this->assertEquals(100, $array['maxTokens']);
        $this->assertArrayNotHasKey('systemInstruction', $array);
        $this->assertArrayNotHasKey('topP', $array);
    }

    /**
     * Tests creating from array with all properties.
     *
     * @return void
     */
    public function testFromArrayAllProperties(): void
    {
        $data = [
            'outputModalities' => ['text', 'image'],
            'systemInstruction' => 'Be helpful',
            'candidateCount' => 3,
            'maxTokens' => 2000,
            'temperature' => 0.9,
            'topP' => 0.95,
            'topK' => 50,
            'stopSequences' => ['###', 'DONE'],
            'presencePenalty' => 0.2,
            'frequencyPenalty' => 0.1,
            'logprobs' => false,
            'topLogprobs' => 3,
            'tools' => [
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
            'customOptions' => ['custom' => true]
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
        $original->setSystemInstruction('Round trip test')
            ->setTemperature(0.75)
            ->setMaxTokens(1500)
            ->setStopSequences(['END', 'STOP'])
            ->setLogprobs(true)
            ->setCustomOptions(['test' => 'value']);

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
        $config->setTemperature(0.8)
            ->setMaxTokens(1000)
            ->setSystemInstruction('JSON test');

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
        $config->setTemperature(0.0)
            ->setTopP(0.0)
            ->setTopK(1)
            ->setCandidateCount(1)
            ->setMaxTokens(1)
            ->setPresencePenalty(-2.0)
            ->setFrequencyPenalty(-2.0);

        $array = $config->toArray();
        $this->assertEquals(0.0, $array['temperature']);
        $this->assertEquals(0.0, $array['topP']);
        $this->assertEquals(1, $array['topK']);

        // Test maximum values
        $config->setTemperature(2.0)
            ->setTopP(1.0)
            ->setTopK(999999)
            ->setCandidateCount(100)
            ->setMaxTokens(999999)
            ->setPresencePenalty(2.0)
            ->setFrequencyPenalty(2.0);

        $array = $config->toArray();
        $this->assertEquals(2.0, $array['temperature']);
        $this->assertEquals(1.0, $array['topP']);
        $this->assertEquals(999999, $array['topK']);
    }

    /**
     * Tests fluent interface method chaining.
     *
     * @return void
     */
    public function testFluentInterface(): void
    {
        $config = new ModelConfig();

        $result = $config
            ->setSystemInstruction('test')
            ->setTemperature(0.5)
            ->setMaxTokens(100)
            ->setTopP(0.9)
            ->setTopK(40)
            ->setLogprobs(true);

        $this->assertSame($config, $result);
        $this->assertEquals('test', $config->getSystemInstruction());
        $this->assertEquals(0.5, $config->getTemperature());
        $this->assertEquals(100, $config->getMaxTokens());
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


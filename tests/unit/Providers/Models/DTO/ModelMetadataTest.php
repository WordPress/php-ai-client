<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * @covers \WordPress\AiClient\Providers\Models\DTO\ModelMetadata
 */
class ModelMetadataTest extends TestCase
{
    /**
     * Creates a sample supported option for testing.
     *
     * @return SupportedOption
     */
    private function createSampleOption(): SupportedOption
    {
        return new SupportedOption('temperature', [0.0, 0.5, 1.0, 1.5, 2.0]);
    }

    /**
     * Tests constructor and getter methods.
     *
     * @return void
     */
    public function testConstructorAndGetters(): void
    {
        $id = 'gpt-4';
        $name = 'GPT-4';
        $capabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
            CapabilityEnum::textGeneration()
        ];
        $options = [
            new SupportedOption('temperature', [0.0, 0.7, 1.0, 2.0]),
            new SupportedOption('max_tokens', [100, 1000, 4000])
        ];

        $metadata = new ModelMetadata($id, $name, $capabilities, $options);

        $this->assertEquals($id, $metadata->getId());
        $this->assertEquals($name, $metadata->getName());
        $this->assertEquals($capabilities, $metadata->getSupportedCapabilities());
        $this->assertEquals($options, $metadata->getSupportedOptions());
        $this->assertCount(3, $metadata->getSupportedCapabilities());
        $this->assertCount(2, $metadata->getSupportedOptions());
    }

    /**
     * Tests with empty capabilities and options.
     *
     * @return void
     */
    public function testWithEmptyCapabilitiesAndOptions(): void
    {
        $metadata = new ModelMetadata('simple-model', 'Simple Model', [], []);

        $this->assertEquals('simple-model', $metadata->getId());
        $this->assertEquals('Simple Model', $metadata->getName());
        $this->assertEquals([], $metadata->getSupportedCapabilities());
        $this->assertEquals([], $metadata->getSupportedOptions());
    }

    /**
     * Tests JSON schema generation.
     *
     * @return void
     */
    public function testGetJsonSchema(): void
    {
        $schema = ModelMetadata::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);

        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('name', $schema['properties']);
        $this->assertArrayHasKey('supportedCapabilities', $schema['properties']);
        $this->assertArrayHasKey('supportedOptions', $schema['properties']);

        // Check property types
        $this->assertEquals('string', $schema['properties']['id']['type']);
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertEquals('array', $schema['properties']['supportedCapabilities']['type']);
        $this->assertEquals('array', $schema['properties']['supportedOptions']['type']);

        // Check array items
        $this->assertArrayHasKey('items', $schema['properties']['supportedCapabilities']);
        $this->assertEquals('string', $schema['properties']['supportedCapabilities']['items']['type']);
        $this->assertArrayHasKey('enum', $schema['properties']['supportedCapabilities']['items']);
        $this->assertEquals(CapabilityEnum::getValues(), $schema['properties']['supportedCapabilities']['items']['enum']);

        $this->assertArrayHasKey('items', $schema['properties']['supportedOptions']);
        $this->assertEquals(SupportedOption::getJsonSchema(), $schema['properties']['supportedOptions']['items']);

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals(['id', 'name', 'supportedCapabilities', 'supportedOptions'], $schema['required']);
    }

    /**
     * Tests array conversion.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $metadata = new ModelMetadata(
            'claude-2',
            'Claude 2',
            [CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory()],
            [
                new SupportedOption('max_tokens', [100, 1000, 10000]),
                new SupportedOption('temperature', [0.0, 1.0])
            ]
        );

        $array = $metadata->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('claude-2', $array['id']);
        $this->assertEquals('Claude 2', $array['name']);
        $this->assertEquals(['text_generation', 'chat_history'], $array['supportedCapabilities']);
        $this->assertCount(2, $array['supportedOptions']);
        $this->assertEquals('max_tokens', $array['supportedOptions'][0]['name']);
        $this->assertEquals([100, 1000, 10000], $array['supportedOptions'][0]['supportedValues']);
        $this->assertEquals('temperature', $array['supportedOptions'][1]['name']);
        $this->assertEquals([0.0, 1.0], $array['supportedOptions'][1]['supportedValues']);
    }

    /**
     * Tests creating from array.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $data = [
            'id' => 'llama-2',
            'name' => 'LLaMA 2',
            'supportedCapabilities' => ['text_generation', 'chat_history', 'embedding_generation'],
            'supportedOptions' => [
                [
                    'name' => 'temperature',
                    'supportedValues' => [0.1, 0.5, 0.9]
                ],
                [
                    'name' => 'top_p',
                    'supportedValues' => [0.5, 0.9, 0.95]
                ]
            ]
        ];

        $metadata = ModelMetadata::fromArray($data);

        $this->assertInstanceOf(ModelMetadata::class, $metadata);
        $this->assertEquals('llama-2', $metadata->getId());
        $this->assertEquals('LLaMA 2', $metadata->getName());

        $capabilities = $metadata->getSupportedCapabilities();
        $this->assertCount(3, $capabilities);
        $this->assertTrue($capabilities[0]->isTextGeneration());
        $this->assertTrue($capabilities[1]->isChatHistory());
        $this->assertTrue($capabilities[2]->isEmbeddingGeneration());

        $options = $metadata->getSupportedOptions();
        $this->assertCount(2, $options);
        $this->assertEquals('temperature', $options[0]->getName());
        $this->assertEquals([0.1, 0.5, 0.9], $options[0]->getSupportedValues());
        $this->assertEquals('top_p', $options[1]->getName());
        $this->assertEquals([0.5, 0.9, 0.95], $options[1]->getSupportedValues());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $original = new ModelMetadata(
            'test-model',
            'Test Model',
            [
                CapabilityEnum::imageGeneration(),
                CapabilityEnum::textGeneration(),
                CapabilityEnum::textToSpeechConversion()
            ],
            [
                new SupportedOption('resolution', ['256x256', '512x512', '1024x1024']),
                new SupportedOption('voice', ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'])
            ]
        );

        $array = $original->toArray();
        $restored = ModelMetadata::fromArray($array);

        $this->assertEquals($original->getId(), $restored->getId());
        $this->assertEquals($original->getName(), $restored->getName());

        $originalCaps = $original->getSupportedCapabilities();
        $restoredCaps = $restored->getSupportedCapabilities();
        $this->assertCount(count($originalCaps), $restoredCaps);
        for ($i = 0; $i < count($originalCaps); $i++) {
            $this->assertEquals($originalCaps[$i]->value, $restoredCaps[$i]->value);
        }

        $originalOpts = $original->getSupportedOptions();
        $restoredOpts = $restored->getSupportedOptions();
        $this->assertCount(count($originalOpts), $restoredOpts);
        for ($i = 0; $i < count($originalOpts); $i++) {
            $this->assertEquals($originalOpts[$i]->getName(), $restoredOpts[$i]->getName());
            $this->assertEquals($originalOpts[$i]->getSupportedValues(), $restoredOpts[$i]->getSupportedValues());
        }
    }

    /**
     * Tests JSON serialization.
     *
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $metadata = new ModelMetadata(
            'json-model',
            'JSON Test Model',
            [CapabilityEnum::embeddingGeneration()],
            [new SupportedOption('dimensions', [256, 512, 1024])]
        );

        $json = json_encode($metadata);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals('json-model', $decoded['id']);
        $this->assertEquals('JSON Test Model', $decoded['name']);
        $this->assertEquals(['embedding_generation'], $decoded['supportedCapabilities']);
        $this->assertCount(1, $decoded['supportedOptions']);
        $this->assertEquals('dimensions', $decoded['supportedOptions'][0]['name']);
        $this->assertEquals([256, 512, 1024], $decoded['supportedOptions'][0]['supportedValues']);
    }

    /**
     * Tests with all available capabilities.
     *
     * @return void
     */
    public function testWithAllCapabilities(): void
    {
        $allCapabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
            CapabilityEnum::textGeneration(),
            CapabilityEnum::imageGeneration(),
            CapabilityEnum::textGeneration(),
            CapabilityEnum::musicGeneration(),
            CapabilityEnum::speechGeneration(),
            CapabilityEnum::videoGeneration(),
            CapabilityEnum::textToSpeechConversion(),
            CapabilityEnum::embeddingGeneration()
        ];

        $metadata = new ModelMetadata(
            'super-model',
            'Super Model',
            $allCapabilities,
            []
        );

        $array = $metadata->toArray();
        $this->assertCount(count($allCapabilities), $array['supportedCapabilities']);

        // Verify all capabilities are preserved
        $expectedValues = array_map(function ($cap) {
            return $cap->value;
        }, $allCapabilities);
        $this->assertEquals($expectedValues, $array['supportedCapabilities']);
    }

    /**
     * Tests with complex supported options.
     *
     * @return void
     */
    public function testWithComplexSupportedOptions(): void
    {
        $options = [
            new SupportedOption('string_values', ['option1', 'option2', 'option3']),
            new SupportedOption('numeric_values', [1, 2, 3, 4, 5]),
            new SupportedOption('float_values', [0.1, 0.5, 0.9]),
            new SupportedOption('boolean_values', [true, false]),
            new SupportedOption('mixed_values', ['text', 123, true, null]),
            new SupportedOption('nested_arrays', [['a', 'b'], ['c', 'd']]),
            new SupportedOption('objects', [['key' => 'value'], ['another' => 'object']])
        ];

        $metadata = new ModelMetadata('complex-model', 'Complex Model', [], $options);
        $array = $metadata->toArray();

        $this->assertCount(7, $array['supportedOptions']);

        // Verify different value types are preserved
        $this->assertEquals(['option1', 'option2', 'option3'], $array['supportedOptions'][0]['supportedValues']);
        $this->assertEquals([1, 2, 3, 4, 5], $array['supportedOptions'][1]['supportedValues']);
        $this->assertEquals([0.1, 0.5, 0.9], $array['supportedOptions'][2]['supportedValues']);
        $this->assertEquals([true, false], $array['supportedOptions'][3]['supportedValues']);
        $this->assertEquals(['text', 123, true, null], $array['supportedOptions'][4]['supportedValues']);
        $this->assertEquals([['a', 'b'], ['c', 'd']], $array['supportedOptions'][5]['supportedValues']);
        $this->assertEquals([['key' => 'value'], ['another' => 'object']], $array['supportedOptions'][6]['supportedValues']);
    }

    /**
     * Tests with special characters in names.
     *
     * @return void
     */
    public function testSpecialCharactersInNames(): void
    {
        $metadata = new ModelMetadata(
            'special-model-123',
            'Model with "quotes" & special <chars>',
            [CapabilityEnum::textGeneration()],
            [new SupportedOption('option_with_underscore', ['value'])]
        );

        $array = $metadata->toArray();
        $this->assertEquals('Model with "quotes" & special <chars>', $array['name']);

        $restored = ModelMetadata::fromArray($array);
        $this->assertEquals($metadata->getName(), $restored->getName());
    }

    /**
     * Tests array values are properly indexed.
     *
     * @return void
     */
    public function testArrayValuesProperlyIndexed(): void
    {
        $metadata = new ModelMetadata(
            'indexed-model',
            'Indexed Model',
            [
                CapabilityEnum::textGeneration(),
                CapabilityEnum::chatHistory(),
                CapabilityEnum::embeddingGeneration()
            ],
            [
                new SupportedOption('opt1', [1, 2, 3]),
                new SupportedOption('opt2', ['a', 'b', 'c'])
            ]
        );

        $array = $metadata->toArray();

        // Check that arrays are properly indexed from 0
        $this->assertEquals([0, 1, 2], array_keys($array['supportedCapabilities']));
        $this->assertEquals([0, 1], array_keys($array['supportedOptions']));
    }

    /**
     * Tests implements correct interfaces.
     *
     * @return void
     */
    public function testImplementsCorrectInterfaces(): void
    {
        $metadata = new ModelMetadata('test', 'Test', [], []);

        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface::class,
            $metadata
        );
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface::class,
            $metadata
        );
        $this->assertInstanceOf(
            JsonSerializable::class,
            $metadata
        );
    }
}


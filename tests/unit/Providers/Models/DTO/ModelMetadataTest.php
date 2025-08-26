<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

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
        return new SupportedOption(OptionEnum::temperature(), [0.0, 0.5, 1.0, 1.5, 2.0]);
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
            new SupportedOption(OptionEnum::temperature(), [0.0, 0.7, 1.0, 2.0]),
            new SupportedOption(OptionEnum::maxTokens(), [100, 1000, 4000])
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
        $this->assertArrayHasKey(ModelMetadata::KEY_ID, $schema['properties']);
        $this->assertArrayHasKey(ModelMetadata::KEY_NAME, $schema['properties']);
        $this->assertArrayHasKey(ModelMetadata::KEY_SUPPORTED_CAPABILITIES, $schema['properties']);
        $this->assertArrayHasKey(ModelMetadata::KEY_SUPPORTED_OPTIONS, $schema['properties']);

        // Check property types
        $this->assertEquals('string', $schema['properties'][ModelMetadata::KEY_ID]['type']);
        $this->assertEquals('string', $schema['properties'][ModelMetadata::KEY_NAME]['type']);
        $this->assertEquals('array', $schema['properties'][ModelMetadata::KEY_SUPPORTED_CAPABILITIES]['type']);
        $this->assertEquals('array', $schema['properties'][ModelMetadata::KEY_SUPPORTED_OPTIONS]['type']);

        // Check array items
        $this->assertArrayHasKey('items', $schema['properties'][ModelMetadata::KEY_SUPPORTED_CAPABILITIES]);
        $this->assertEquals(
            'string',
            $schema['properties'][ModelMetadata::KEY_SUPPORTED_CAPABILITIES]['items']['type']
        );
        $this->assertArrayHasKey('enum', $schema['properties'][ModelMetadata::KEY_SUPPORTED_CAPABILITIES]['items']);
        $this->assertEquals(
            CapabilityEnum::getValues(),
            $schema['properties'][ModelMetadata::KEY_SUPPORTED_CAPABILITIES]['items']['enum']
        );

        $this->assertArrayHasKey('items', $schema['properties'][ModelMetadata::KEY_SUPPORTED_OPTIONS]);
        $this->assertEquals(
            SupportedOption::getJsonSchema(),
            $schema['properties'][ModelMetadata::KEY_SUPPORTED_OPTIONS]['items']
        );

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals(
            [
                ModelMetadata::KEY_ID,
                ModelMetadata::KEY_NAME,
                ModelMetadata::KEY_SUPPORTED_CAPABILITIES,
                ModelMetadata::KEY_SUPPORTED_OPTIONS
            ],
            $schema['required']
        );
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
                new SupportedOption(OptionEnum::maxTokens(), [100, 1000, 10000]),
                new SupportedOption(OptionEnum::temperature(), [0.0, 1.0])
            ]
        );

        $array = $metadata->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('claude-2', $array[ModelMetadata::KEY_ID]);
        $this->assertEquals('Claude 2', $array[ModelMetadata::KEY_NAME]);
        $this->assertEquals(['text_generation', 'chat_history'], $array[ModelMetadata::KEY_SUPPORTED_CAPABILITIES]);
        $this->assertCount(2, $array[ModelMetadata::KEY_SUPPORTED_OPTIONS]);
        $this->assertEquals(OptionEnum::maxTokens()->value, $array[ModelMetadata::KEY_SUPPORTED_OPTIONS][0][SupportedOption::KEY_NAME]);
        $this->assertEquals(
            [100, 1000, 10000],
            $array[ModelMetadata::KEY_SUPPORTED_OPTIONS][0][SupportedOption::KEY_SUPPORTED_VALUES]
        );
        $this->assertEquals(OptionEnum::temperature()->value, $array[ModelMetadata::KEY_SUPPORTED_OPTIONS][1][SupportedOption::KEY_NAME]);
        $this->assertEquals(
            [0.0, 1.0],
            $array[ModelMetadata::KEY_SUPPORTED_OPTIONS][1][SupportedOption::KEY_SUPPORTED_VALUES]
        );
    }

    /**
     * Tests creating from array.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $data = [
            ModelMetadata::KEY_ID => 'llama-2',
            ModelMetadata::KEY_NAME => 'LLaMA 2',
            ModelMetadata::KEY_SUPPORTED_CAPABILITIES => ['text_generation', 'chat_history', 'embedding_generation'],
            ModelMetadata::KEY_SUPPORTED_OPTIONS => [
                [
                    SupportedOption::KEY_NAME => OptionEnum::temperature()->value,
                    SupportedOption::KEY_SUPPORTED_VALUES => [0.1, 0.5, 0.9]
                ],
                [
                    SupportedOption::KEY_NAME => OptionEnum::topP()->value,
                    SupportedOption::KEY_SUPPORTED_VALUES => [0.5, 0.9, 0.95]
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
        $this->assertEquals(OptionEnum::temperature()->value, $options[0]->getName());
        $this->assertEquals([0.1, 0.5, 0.9], $options[0]->getSupportedValues());
        $this->assertEquals(OptionEnum::topP()->value, $options[1]->getName());
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
                new SupportedOption(OptionEnum::outputSchema(), ['256x256', '512x512', '1024x1024']),
                new SupportedOption(OptionEnum::outputSchema(), ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'])
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
            [new SupportedOption(OptionEnum::outputSchema(), [256, 512, 1024])]
        );

        $json = json_encode($metadata);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals('json-model', $decoded[ModelMetadata::KEY_ID]);
        $this->assertEquals('JSON Test Model', $decoded[ModelMetadata::KEY_NAME]);
        $this->assertEquals(['embedding_generation'], $decoded[ModelMetadata::KEY_SUPPORTED_CAPABILITIES]);
        $this->assertCount(1, $decoded[ModelMetadata::KEY_SUPPORTED_OPTIONS]);
        $this->assertEquals(OptionEnum::outputSchema()->value, $decoded[ModelMetadata::KEY_SUPPORTED_OPTIONS][0][SupportedOption::KEY_NAME]);
        $this->assertEquals(
            [256, 512, 1024],
            $decoded[ModelMetadata::KEY_SUPPORTED_OPTIONS][0][SupportedOption::KEY_SUPPORTED_VALUES]
        );
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
        $this->assertCount(count($allCapabilities), $array[ModelMetadata::KEY_SUPPORTED_CAPABILITIES]);

        // Verify all capabilities are preserved
        $expectedValues = array_map(function ($cap) {
            return $cap->value;
        }, $allCapabilities);
        $this->assertEquals($expectedValues, $array[ModelMetadata::KEY_SUPPORTED_CAPABILITIES]);
    }

    /**
     * Tests with complex supported options.
     *
     * @return void
     */
    public function testWithComplexSupportedOptions(): void
    {
        $options = [
            new SupportedOption(OptionEnum::outputSchema(), ['option1', 'option2', 'option3']),
            new SupportedOption(OptionEnum::outputSchema(), [1, 2, 3, 4, 5]),
            new SupportedOption(OptionEnum::temperature(), [0.1, 0.5, 0.9]),
            new SupportedOption(OptionEnum::outputSchema(), [true, false]),
            new SupportedOption(OptionEnum::outputSchema(), ['text', 123, true, null]),
            new SupportedOption(OptionEnum::outputSchema(), [['a', 'b'], ['c', 'd']]),
            new SupportedOption(OptionEnum::customOptions(), [['key' => 'value'], ['another' => 'object']])
        ];

        $metadata = new ModelMetadata('complex-model', 'Complex Model', [], $options);
        $array = $metadata->toArray();

        $this->assertCount(7, $array[ModelMetadata::KEY_SUPPORTED_OPTIONS]);

        // Verify different value types are preserved
        $this->assertEquals(
            ['option1', 'option2', 'option3'],
            $array[ModelMetadata::KEY_SUPPORTED_OPTIONS][0][SupportedOption::KEY_SUPPORTED_VALUES]
        );
        $this->assertEquals(
            [1, 2, 3, 4, 5],
            $array[ModelMetadata::KEY_SUPPORTED_OPTIONS][1][SupportedOption::KEY_SUPPORTED_VALUES]
        );
        $this->assertEquals(
            [0.1, 0.5, 0.9],
            $array[ModelMetadata::KEY_SUPPORTED_OPTIONS][2][SupportedOption::KEY_SUPPORTED_VALUES]
        );
        $this->assertEquals(
            [true, false],
            $array[ModelMetadata::KEY_SUPPORTED_OPTIONS][3][SupportedOption::KEY_SUPPORTED_VALUES]
        );
        $this->assertEquals(
            ['text', 123, true, null],
            $array[ModelMetadata::KEY_SUPPORTED_OPTIONS][4][SupportedOption::KEY_SUPPORTED_VALUES]
        );
        $this->assertEquals(
            [['a', 'b'], ['c', 'd']],
            $array[ModelMetadata::KEY_SUPPORTED_OPTIONS][5][SupportedOption::KEY_SUPPORTED_VALUES]
        );
        $this->assertEquals(
            [['key' => 'value'], ['another' => 'object']],
            $array[ModelMetadata::KEY_SUPPORTED_OPTIONS][6][SupportedOption::KEY_SUPPORTED_VALUES]
        );
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
            [new SupportedOption(OptionEnum::outputSchema(), ['value'])]
        );

        $array = $metadata->toArray();
        $this->assertEquals('Model with "quotes" & special <chars>', $array[ModelMetadata::KEY_NAME]);

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
                new SupportedOption(OptionEnum::maxTokens(), [1, 2, 3]),
                new SupportedOption(OptionEnum::outputSchema(), ['a', 'b', 'c'])
            ]
        );

        $array = $metadata->toArray();

        // Check that arrays are properly indexed from 0
        $this->assertEquals([0, 1, 2], array_keys($array[ModelMetadata::KEY_SUPPORTED_CAPABILITIES]));
        $this->assertEquals([0, 1], array_keys($array[ModelMetadata::KEY_SUPPORTED_OPTIONS]));
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

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface;
use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\DTO\RequiredOption;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

/**
 * @covers \WordPress\AiClient\Providers\Models\DTO\ModelRequirements
 */
class ModelRequirementsTest extends TestCase
{
    /**
     * Tests constructor and getter methods.
     *
     * @return void
     */
    public function testConstructorAndGetters(): void
    {
        $capabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory()
        ];
        $options = [
            new RequiredOption(OptionEnum::temperature(), 0.7),
            new RequiredOption(OptionEnum::maxTokens(), 1000)
        ];

        $requirements = new ModelRequirements($capabilities, $options);

        $this->assertEquals($capabilities, $requirements->getRequiredCapabilities());
        $this->assertEquals($options, $requirements->getRequiredOptions());
        $this->assertCount(2, $requirements->getRequiredCapabilities());
        $this->assertCount(2, $requirements->getRequiredOptions());
    }

    /**
     * Tests with empty capabilities and options.
     *
     * @return void
     */
    public function testWithEmptyCapabilitiesAndOptions(): void
    {
        $requirements = new ModelRequirements([], []);

        $this->assertEquals([], $requirements->getRequiredCapabilities());
        $this->assertEquals([], $requirements->getRequiredOptions());
    }

    /**
     * Tests JSON schema generation.
     *
     * @return void
     */
    public function testGetJsonSchema(): void
    {
        $schema = ModelRequirements::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);

        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey(ModelRequirements::KEY_REQUIRED_CAPABILITIES, $schema['properties']);
        $this->assertArrayHasKey(ModelRequirements::KEY_REQUIRED_OPTIONS, $schema['properties']);

        // Check property types
        $this->assertEquals('array', $schema['properties'][ModelRequirements::KEY_REQUIRED_CAPABILITIES]['type']);
        $this->assertEquals('array', $schema['properties'][ModelRequirements::KEY_REQUIRED_OPTIONS]['type']);

        // Check array items
        $this->assertArrayHasKey('items', $schema['properties'][ModelRequirements::KEY_REQUIRED_CAPABILITIES]);
        $this->assertEquals(
            'string',
            $schema['properties'][ModelRequirements::KEY_REQUIRED_CAPABILITIES]['items']['type']
        );
        $this->assertArrayHasKey('enum', $schema['properties'][ModelRequirements::KEY_REQUIRED_CAPABILITIES]['items']);
        $this->assertEquals(
            CapabilityEnum::getValues(),
            $schema['properties'][ModelRequirements::KEY_REQUIRED_CAPABILITIES]['items']['enum']
        );

        $this->assertArrayHasKey('items', $schema['properties'][ModelRequirements::KEY_REQUIRED_OPTIONS]);
        $this->assertEquals(
            RequiredOption::getJsonSchema(),
            $schema['properties'][ModelRequirements::KEY_REQUIRED_OPTIONS]['items']
        );

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals(
            [ModelRequirements::KEY_REQUIRED_CAPABILITIES, ModelRequirements::KEY_REQUIRED_OPTIONS],
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
        $requirements = new ModelRequirements(
            [CapabilityEnum::imageGeneration(), CapabilityEnum::textGeneration()],
            [
                new RequiredOption(OptionEnum::outputSchema(), '1024x1024'),
                new RequiredOption(OptionEnum::outputSchema(), 'realistic')
            ]
        );

        $array = $requirements->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(
            ['image_generation', 'text_generation'],
            $array[ModelRequirements::KEY_REQUIRED_CAPABILITIES]
        );
        $this->assertCount(2, $array[ModelRequirements::KEY_REQUIRED_OPTIONS]);
        $this->assertEquals(
            OptionEnum::outputSchema()->value,
            $array[ModelRequirements::KEY_REQUIRED_OPTIONS][0][RequiredOption::KEY_NAME]
        );
        $this->assertEquals('1024x1024', $array[ModelRequirements::KEY_REQUIRED_OPTIONS][0][RequiredOption::KEY_VALUE]);
        $this->assertEquals(
            OptionEnum::outputSchema()->value,
            $array[ModelRequirements::KEY_REQUIRED_OPTIONS][1][RequiredOption::KEY_NAME]
        );
        $this->assertEquals('realistic', $array[ModelRequirements::KEY_REQUIRED_OPTIONS][1][RequiredOption::KEY_VALUE]);
    }

    /**
     * Tests creating from array.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $data = [
            ModelRequirements::KEY_REQUIRED_CAPABILITIES => ['text_generation', 'chat_history', 'embedding_generation'],
            ModelRequirements::KEY_REQUIRED_OPTIONS => [
                [
                    RequiredOption::KEY_NAME => OptionEnum::outputSchema()->value,
                    RequiredOption::KEY_VALUE => ['type' => 'json_object']
                ],
                [
                    RequiredOption::KEY_NAME => OptionEnum::temperature()->value,
                    RequiredOption::KEY_VALUE => 0.5
                ]
            ]
        ];

        $requirements = ModelRequirements::fromArray($data);

        $this->assertInstanceOf(ModelRequirements::class, $requirements);

        $capabilities = $requirements->getRequiredCapabilities();
        $this->assertCount(3, $capabilities);
        $this->assertTrue($capabilities[0]->isTextGeneration());
        $this->assertTrue($capabilities[1]->isChatHistory());
        $this->assertTrue($capabilities[2]->isEmbeddingGeneration());

        $options = $requirements->getRequiredOptions();
        $this->assertCount(2, $options);
        $this->assertEquals(OptionEnum::outputSchema()->value, $options[0]->getName());
        $this->assertEquals(['type' => 'json_object'], $options[0]->getValue());
        $this->assertEquals(OptionEnum::temperature()->value, $options[1]->getName());
        $this->assertEquals(0.5, $options[1]->getValue());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $original = new ModelRequirements(
            [
                CapabilityEnum::textToSpeechConversion(),
                CapabilityEnum::speechGeneration(),
                CapabilityEnum::musicGeneration()
            ],
            [
                new RequiredOption(OptionEnum::outputSchema(), 'alloy'),
                new RequiredOption(OptionEnum::outputSchema(), 'en-US'),
                new RequiredOption(OptionEnum::outputSchema(), 44100)
            ]
        );

        $array = $original->toArray();
        $restored = ModelRequirements::fromArray($array);

        $originalCaps = $original->getRequiredCapabilities();
        $restoredCaps = $restored->getRequiredCapabilities();
        $this->assertCount(count($originalCaps), $restoredCaps);
        for ($i = 0; $i < count($originalCaps); $i++) {
            $this->assertEquals($originalCaps[$i]->value, $restoredCaps[$i]->value);
        }

        $originalOpts = $original->getRequiredOptions();
        $restoredOpts = $restored->getRequiredOptions();
        $this->assertCount(count($originalOpts), $restoredOpts);
        for ($i = 0; $i < count($originalOpts); $i++) {
            $this->assertEquals($originalOpts[$i]->getName(), $restoredOpts[$i]->getName());
            $this->assertEquals($originalOpts[$i]->getValue(), $restoredOpts[$i]->getValue());
        }
    }

    /**
     * Tests JSON serialization.
     *
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $requirements = new ModelRequirements(
            [CapabilityEnum::embeddingGeneration()],
            [new RequiredOption(OptionEnum::outputSchema(), 1536)]
        );

        $json = json_encode($requirements);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals(['embedding_generation'], $decoded[ModelRequirements::KEY_REQUIRED_CAPABILITIES]);
        $this->assertCount(1, $decoded[ModelRequirements::KEY_REQUIRED_OPTIONS]);
        $this->assertEquals(
            OptionEnum::outputSchema()->value,
            $decoded[ModelRequirements::KEY_REQUIRED_OPTIONS][0][RequiredOption::KEY_NAME]
        );
        $this->assertEquals(1536, $decoded[ModelRequirements::KEY_REQUIRED_OPTIONS][0][RequiredOption::KEY_VALUE]);
    }

    /**
     * Tests with all capability types.
     *
     * @return void
     */
    public function testWithAllCapabilityTypes(): void
    {
        $allCapabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
            CapabilityEnum::embeddingGeneration(),
            CapabilityEnum::imageGeneration(),
            CapabilityEnum::musicGeneration(),
            CapabilityEnum::speechGeneration(),
            CapabilityEnum::videoGeneration(),
            CapabilityEnum::textToSpeechConversion(),
            CapabilityEnum::chatHistory()
        ];

        $requirements = new ModelRequirements($allCapabilities, []);
        $array = $requirements->toArray();

        $this->assertCount(count($allCapabilities), $array[ModelRequirements::KEY_REQUIRED_CAPABILITIES]);

        // Verify all capabilities are preserved with correct values
        $expectedValues = array_map(function ($cap) {
            return $cap->value;
        }, $allCapabilities);
        $this->assertEquals($expectedValues, $array[ModelRequirements::KEY_REQUIRED_CAPABILITIES]);
    }

    /**
     * Tests with various option value types.
     *
     * @return void
     */
    public function testWithVariousOptionValueTypes(): void
    {
        $options = [
            new RequiredOption(OptionEnum::outputSchema(), 'text value'),
            new RequiredOption(OptionEnum::outputSchema(), 42),
            new RequiredOption(OptionEnum::temperature(), 3.14),
            new RequiredOption(OptionEnum::outputSchema(), true),
            new RequiredOption(OptionEnum::outputSchema(), null),
            new RequiredOption(OptionEnum::outputSchema(), ['a', 'b', 'c']),
            new RequiredOption(OptionEnum::customOptions(), ['key' => 'value', 'nested' => ['inner' => true]])
        ];

        $requirements = new ModelRequirements([], $options);
        $array = $requirements->toArray();

        $this->assertCount(7, $array['requiredOptions']);
        $this->assertEquals('text value', $array['requiredOptions'][0]['value']);
        $this->assertEquals(42, $array['requiredOptions'][1]['value']);
        $this->assertEquals(3.14, $array['requiredOptions'][2]['value']);
        $this->assertTrue($array['requiredOptions'][3]['value']);
        $this->assertNull($array['requiredOptions'][4]['value']);
        $this->assertEquals(['a', 'b', 'c'], $array['requiredOptions'][5]['value']);
        $this->assertEquals(['key' => 'value', 'nested' => ['inner' => true]], $array['requiredOptions'][6]['value']);
    }

    /**
     * Tests only capabilities without options.
     *
     * @return void
     */
    public function testOnlyCapabilitiesNoOptions(): void
    {
        $requirements = new ModelRequirements(
            [
                CapabilityEnum::textGeneration(),
                CapabilityEnum::chatHistory()
            ],
            []
        );

        $array = $requirements->toArray();
        $this->assertEquals(['text_generation', 'chat_history'], $array['requiredCapabilities']);
        $this->assertEquals([], $array['requiredOptions']);
    }

    /**
     * Tests only options without capabilities.
     *
     * @return void
     */
    public function testOnlyOptionsNoCapabilities(): void
    {
        $requirements = new ModelRequirements(
            [],
            [
                new RequiredOption(OptionEnum::outputSchema(), 'secret-key'),
                new RequiredOption(OptionEnum::outputSchema(), 'https://api.example.com')
            ]
        );

        $array = $requirements->toArray();
        $this->assertEquals([], $array['requiredCapabilities']);
        $this->assertCount(2, $array['requiredOptions']);
        $this->assertEquals(OptionEnum::outputSchema()->value, $array['requiredOptions'][0]['name']);
        $this->assertEquals('secret-key', $array['requiredOptions'][0]['value']);
    }

    /**
     * Tests array values are properly indexed.
     *
     * @return void
     */
    public function testArrayValuesProperlyIndexed(): void
    {
        $requirements = new ModelRequirements(
            [
                CapabilityEnum::textGeneration(),
                CapabilityEnum::chatHistory(),
                CapabilityEnum::embeddingGeneration()
            ],
            [
                new RequiredOption(OptionEnum::outputSchema(), 'val1'),
                new RequiredOption(OptionEnum::outputSchema(), 'val2')
            ]
        );

        $array = $requirements->toArray();

        // Check that arrays are properly indexed from 0
        $this->assertEquals([0, 1, 2], array_keys($array['requiredCapabilities']));
        $this->assertEquals([0, 1], array_keys($array['requiredOptions']));
    }

    /**
     * Tests implements correct interfaces.
     *
     * @return void
     */
    public function testImplementsCorrectInterfaces(): void
    {
        $requirements = new ModelRequirements([], []);

        $this->assertInstanceOf(
            WithArrayTransformationInterface::class,
            $requirements
        );
        $this->assertInstanceOf(
            WithJsonSchemaInterface::class,
            $requirements
        );
        $this->assertInstanceOf(
            JsonSerializable::class,
            $requirements
        );
    }

    /**
     * Tests areMetBy method with matching capabilities and options.
     *
     * @return void
     */
    public function testAreMetByWithMatchingRequirements(): void
    {
        $requirements = new ModelRequirements(
            [CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory()],
            [
                new RequiredOption(OptionEnum::temperature(), 0.7),
                new RequiredOption(OptionEnum::maxTokens(), 1000)
            ]
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getSupportedCapabilities')->willReturn([
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
            CapabilityEnum::imageGeneration()
        ]);
        $metadata->method('getSupportedOptions')->willReturn([
            new SupportedOption(OptionEnum::temperature(), [0.1, 0.7, 1.0]),
            new SupportedOption(OptionEnum::maxTokens(), [500, 1000, 2000])
        ]);

        $this->assertTrue($requirements->areMetBy($metadata));
    }

    /**
     * Tests areMetBy method with missing required capability.
     *
     * @return void
     */
    public function testAreMetByWithMissingCapability(): void
    {
        $requirements = new ModelRequirements(
            [CapabilityEnum::textGeneration(), CapabilityEnum::imageGeneration()],
            []
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getSupportedCapabilities')->willReturn([
            CapabilityEnum::textGeneration()
        ]);
        $metadata->method('getSupportedOptions')->willReturn([]);

        $this->assertFalse($requirements->areMetBy($metadata));
    }

    /**
     * Tests areMetBy method with unsupported option value.
     *
     * @return void
     */
    public function testAreMetByWithUnsupportedOptionValue(): void
    {
        $requirements = new ModelRequirements(
            [CapabilityEnum::textGeneration()],
            [new RequiredOption(OptionEnum::temperature(), 0.5)]
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getSupportedCapabilities')->willReturn([
            CapabilityEnum::textGeneration()
        ]);
        $metadata->method('getSupportedOptions')->willReturn([
            new SupportedOption(OptionEnum::temperature(), [0.1, 0.7, 1.0])
        ]);

        $this->assertFalse($requirements->areMetBy($metadata));
    }

    /**
     * Tests areMetBy method with no requirements.
     *
     * @return void
     */
    public function testAreMetByWithNoRequirements(): void
    {
        $requirements = new ModelRequirements([], []);

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getSupportedCapabilities')->willReturn([
            CapabilityEnum::textGeneration()
        ]);
        $metadata->method('getSupportedOptions')->willReturn([
            new SupportedOption(OptionEnum::temperature(), [0.7])
        ]);

        $this->assertTrue($requirements->areMetBy($metadata));
    }

    /**
     * Tests fromPromptData method with simple text generation.
     *
     * @return void
     */
    public function testFromPromptDataWithSimpleTextGeneration(): void
    {
        $messages = [new UserMessage([new MessagePart('Hello, world!')])];
        $modelConfig = new ModelConfig();
        $modelConfig->setTemperature(0.7);
        $modelConfig->setMaxTokens(1000);

        $requirements = ModelRequirements::fromPromptData(
            CapabilityEnum::textGeneration(),
            $messages,
            $modelConfig
        );

        $capabilities = $requirements->getRequiredCapabilities();
        $this->assertContains(CapabilityEnum::textGeneration(), $capabilities);

        // Check for input modalities option containing text
        $inputModalityOptions = array_filter(
            $requirements->getRequiredOptions(),
            fn($opt) => $opt->getName()->isInputModalities()
        );
        $this->assertNotEmpty($inputModalityOptions);

        $modalityValues = array_values($inputModalityOptions)[0]->getValue();

        // The array contains ModalityEnum objects, not strings
        $this->assertContains(ModalityEnum::text(), $modalityValues);
    }

    /**
     * Tests fromPromptData method with chat history.
     *
     * @return void
     */
    public function testFromPromptDataWithChatHistory(): void
    {
        $messages = [
            new UserMessage([new MessagePart('First message')]),
            new UserMessage([new MessagePart('Second message')])
        ];
        $modelConfig = new ModelConfig();

        $requirements = ModelRequirements::fromPromptData(
            CapabilityEnum::textGeneration(),
            $messages,
            $modelConfig
        );

        $capabilities = $requirements->getRequiredCapabilities();
        $this->assertContains(CapabilityEnum::textGeneration(), $capabilities);
        $this->assertContains(CapabilityEnum::chatHistory(), $capabilities);
    }

    /**
     * Tests fromPromptData method with image input.
     *
     * @return void
     */
    public function testFromPromptDataWithImageInput(): void
    {
        $b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';
        $imageFile = new File('data:image/png;base64,' . $b64);
        $messages = [
            new UserMessage([
                new MessagePart('Describe this image'),
                new MessagePart($imageFile)
            ])
        ];
        $modelConfig = new ModelConfig();

        $requirements = ModelRequirements::fromPromptData(
            CapabilityEnum::textGeneration(),
            $messages,
            $modelConfig
        );

        $inputModalityOptions = array_filter(
            $requirements->getRequiredOptions(),
            fn($opt) => $opt->getName()->isInputModalities()
        );
        $this->assertNotEmpty($inputModalityOptions);

        $modalityValues = array_values($inputModalityOptions)[0]->getValue();
        $this->assertContains(ModalityEnum::text(), $modalityValues);
        $this->assertContains(ModalityEnum::image(), $modalityValues);
    }

    /**
     * Tests fromPromptData method with model configuration options.
     *
     * @return void
     */
    public function testFromPromptDataWithModelConfigOptions(): void
    {
        $messages = [new UserMessage([new MessagePart('Test')])];
        $modelConfig = new ModelConfig();
        $modelConfig->setTemperature(0.9);
        $modelConfig->setMaxTokens(2000);
        $modelConfig->setTopP(0.95);
        $modelConfig->setStopSequences(['END']);

        $requirements = ModelRequirements::fromPromptData(
            CapabilityEnum::textGeneration(),
            $messages,
            $modelConfig
        );

        $options = $requirements->getRequiredOptions();
        $this->assertNotEmpty($options);

        // Check that we have the expected options based on ModelConfig settings
        $hasTemperature = false;
        $hasMaxTokens = false;
        $hasTopP = false;

        foreach ($options as $option) {
            if ($option->getName()->isTemperature()) {
                $hasTemperature = true;
                $this->assertEquals(0.9, $option->getValue());
            }
            if ($option->getName()->isMaxTokens()) {
                $hasMaxTokens = true;
                $this->assertEquals(2000, $option->getValue());
            }
            if ($option->getName()->isTopP()) {
                $hasTopP = true;
                $this->assertEquals(0.95, $option->getValue());
            }
        }

        $this->assertTrue($hasTemperature, 'Temperature option should be present');
        $this->assertTrue($hasMaxTokens, 'Max tokens option should be present');
        $this->assertTrue($hasTopP, 'Top P option should be present');
    }

    /**
     * Tests fromPromptData does not mark embeddings as requiring chat history.
     *
     * @return void
     */
    public function testFromPromptDataForEmbeddings(): void
    {
        $messages = [
            new UserMessage([new MessagePart('First doc')]),
            new UserMessage([new MessagePart('Second doc')]),
        ];

        $requirements = ModelRequirements::fromPromptData(
            CapabilityEnum::embeddingGeneration(),
            $messages,
            new ModelConfig()
        );

        $capabilities = $requirements->getRequiredCapabilities();
        $this->assertCount(1, $capabilities);
        $this->assertTrue($capabilities[0]->isEmbeddingGeneration());
    }
}

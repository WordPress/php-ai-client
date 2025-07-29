<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\DTO\RequiredOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

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
            new RequiredOption('temperature', 0.7),
            new RequiredOption('max_tokens', 1000)
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
        $this->assertArrayHasKey('requiredCapabilities', $schema['properties']);
        $this->assertArrayHasKey('requiredOptions', $schema['properties']);

        // Check property types
        $this->assertEquals('array', $schema['properties']['requiredCapabilities']['type']);
        $this->assertEquals('array', $schema['properties']['requiredOptions']['type']);

        // Check array items
        $this->assertArrayHasKey('items', $schema['properties']['requiredCapabilities']);
        $this->assertEquals('string', $schema['properties']['requiredCapabilities']['items']['type']);
        $this->assertArrayHasKey('enum', $schema['properties']['requiredCapabilities']['items']);
        $this->assertEquals(CapabilityEnum::getValues(), $schema['properties']['requiredCapabilities']['items']['enum']);

        $this->assertArrayHasKey('items', $schema['properties']['requiredOptions']);
        $this->assertEquals(RequiredOption::getJsonSchema(), $schema['properties']['requiredOptions']['items']);

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals(['requiredCapabilities', 'requiredOptions'], $schema['required']);
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
                new RequiredOption('resolution', '1024x1024'),
                new RequiredOption('style', 'realistic')
            ]
        );

        $array = $requirements->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(['image_generation', 'text_generation'], $array['requiredCapabilities']);
        $this->assertCount(2, $array['requiredOptions']);
        $this->assertEquals('resolution', $array['requiredOptions'][0]['name']);
        $this->assertEquals('1024x1024', $array['requiredOptions'][0]['value']);
        $this->assertEquals('style', $array['requiredOptions'][1]['name']);
        $this->assertEquals('realistic', $array['requiredOptions'][1]['value']);
    }

    /**
     * Tests creating from array.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $data = [
            'requiredCapabilities' => ['text_generation', 'chat_history', 'embedding_generation'],
            'requiredOptions' => [
                [
                    'name' => 'response_format',
                    'value' => ['type' => 'json_object']
                ],
                [
                    'name' => 'temperature',
                    'value' => 0.5
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
        $this->assertEquals('response_format', $options[0]->getName());
        $this->assertEquals(['type' => 'json_object'], $options[0]->getValue());
        $this->assertEquals('temperature', $options[1]->getName());
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
                new RequiredOption('voice', 'alloy'),
                new RequiredOption('language', 'en-US'),
                new RequiredOption('sample_rate', 44100)
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
            [new RequiredOption('dimensions', 1536)]
        );

        $json = json_encode($requirements);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals(['embedding_generation'], $decoded['requiredCapabilities']);
        $this->assertCount(1, $decoded['requiredOptions']);
        $this->assertEquals('dimensions', $decoded['requiredOptions'][0]['name']);
        $this->assertEquals(1536, $decoded['requiredOptions'][0]['value']);
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

        $this->assertCount(count($allCapabilities), $array['requiredCapabilities']);

        // Verify all capabilities are preserved with correct values
        $expectedValues = array_map(function ($cap) {
            return $cap->value;
        }, $allCapabilities);
        $this->assertEquals($expectedValues, $array['requiredCapabilities']);
    }

    /**
     * Tests with various option value types.
     *
     * @return void
     */
    public function testWithVariousOptionValueTypes(): void
    {
        $options = [
            new RequiredOption('string_option', 'text value'),
            new RequiredOption('int_option', 42),
            new RequiredOption('float_option', 3.14),
            new RequiredOption('bool_option', true),
            new RequiredOption('null_option', null),
            new RequiredOption('array_option', ['a', 'b', 'c']),
            new RequiredOption('object_option', ['key' => 'value', 'nested' => ['inner' => true]])
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
                new RequiredOption('api_key', 'secret-key'),
                new RequiredOption('base_url', 'https://api.example.com')
            ]
        );

        $array = $requirements->toArray();
        $this->assertEquals([], $array['requiredCapabilities']);
        $this->assertCount(2, $array['requiredOptions']);
        $this->assertEquals('api_key', $array['requiredOptions'][0]['name']);
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
                new RequiredOption('opt1', 'val1'),
                new RequiredOption('opt2', 'val2')
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
            \WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface::class,
            $requirements
        );
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface::class,
            $requirements
        );
        $this->assertInstanceOf(
            JsonSerializable::class,
            $requirements
        );
    }
}


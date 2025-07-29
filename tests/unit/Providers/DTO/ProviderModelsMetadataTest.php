<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\DTO\ProviderModelsMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\Tool;

/**
 * @covers \WordPress\AiClient\Providers\DTO\ProviderModelsMetadata
 */
class ProviderModelsMetadataTest extends TestCase
{
    /**
     * Creates a sample provider metadata.
     *
     * @return ProviderMetadata
     */
    private function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata('openai', 'OpenAI', ProviderTypeEnum::cloud());
    }

    /**
     * Creates a sample model metadata.
     *
     * @param string $id
     * @param string $name
     * @return ModelMetadata
     */
    private function createModelMetadata(string $id, string $name): ModelMetadata
    {
        return new ModelMetadata(
            $id,
            $name,
            [CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory()],
            [new SupportedOption('temperature', [0.0, 0.5, 1.0, 1.5, 2.0])]
        );
    }

    /**
     * Tests constructor and getter methods.
     *
     * @return void
     */
    public function testConstructorAndGetters(): void
    {
        $provider = $this->createProviderMetadata();
        $models = [
            $this->createModelMetadata('gpt-3.5-turbo', 'GPT-3.5 Turbo'),
            $this->createModelMetadata('gpt-4', 'GPT-4'),
        ];

        $metadata = new ProviderModelsMetadata($provider, $models);

        $this->assertSame($provider, $metadata->getProvider());
        $this->assertSame($models, $metadata->getModels());
        $this->assertCount(2, $metadata->getModels());
    }

    /**
     * Tests with empty models array.
     *
     * @return void
     */
    public function testWithEmptyModels(): void
    {
        $provider = $this->createProviderMetadata();
        $metadata = new ProviderModelsMetadata($provider, []);

        $this->assertSame($provider, $metadata->getProvider());
        $this->assertEquals([], $metadata->getModels());
        $this->assertCount(0, $metadata->getModels());
    }

    /**
     * Tests JSON schema generation.
     *
     * @return void
     */
    public function testGetJsonSchema(): void
    {
        $schema = ProviderModelsMetadata::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);

        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey(ProviderModelsMetadata::KEY_PROVIDER, $schema['properties']);
        $this->assertArrayHasKey(ProviderModelsMetadata::KEY_MODELS, $schema['properties']);

        // Check provider property
        $this->assertEquals(ProviderMetadata::getJsonSchema(), $schema['properties'][ProviderModelsMetadata::KEY_PROVIDER]);

        // Check models property
        $this->assertEquals('array', $schema['properties'][ProviderModelsMetadata::KEY_MODELS]['type']);
        $this->assertArrayHasKey('items', $schema['properties'][ProviderModelsMetadata::KEY_MODELS]);
        $this->assertEquals(ModelMetadata::getJsonSchema(), $schema['properties'][ProviderModelsMetadata::KEY_MODELS]['items']);

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals([ProviderModelsMetadata::KEY_PROVIDER, ProviderModelsMetadata::KEY_MODELS], $schema['required']);
    }

    /**
     * Tests array conversion.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $provider = $this->createProviderMetadata();
        $models = [
            $this->createModelMetadata('model-1', 'Model 1'),
            $this->createModelMetadata('model-2', 'Model 2'),
        ];

        $metadata = new ProviderModelsMetadata($provider, $models);
        $array = $metadata->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey(ProviderModelsMetadata::KEY_PROVIDER, $array);
        $this->assertArrayHasKey(ProviderModelsMetadata::KEY_MODELS, $array);

        // Check provider data
        $this->assertEquals('openai', $array[ProviderModelsMetadata::KEY_PROVIDER][ProviderMetadata::KEY_ID]);
        $this->assertEquals('OpenAI', $array[ProviderModelsMetadata::KEY_PROVIDER][ProviderMetadata::KEY_NAME]);
        $this->assertEquals('cloud', $array[ProviderModelsMetadata::KEY_PROVIDER][ProviderMetadata::KEY_TYPE]);

        // Check models data
        $this->assertCount(2, $array[ProviderModelsMetadata::KEY_MODELS]);
        $this->assertEquals('model-1', $array[ProviderModelsMetadata::KEY_MODELS][0][ModelMetadata::KEY_ID]);
        $this->assertEquals('Model 1', $array[ProviderModelsMetadata::KEY_MODELS][0][ModelMetadata::KEY_NAME]);
        $this->assertEquals('model-2', $array[ProviderModelsMetadata::KEY_MODELS][1][ModelMetadata::KEY_ID]);
        $this->assertEquals('Model 2', $array[ProviderModelsMetadata::KEY_MODELS][1][ModelMetadata::KEY_NAME]);
    }

    /**
     * Tests creating from array.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $data = [
            ProviderModelsMetadata::KEY_PROVIDER => [
                ProviderMetadata::KEY_ID => 'anthropic',
                ProviderMetadata::KEY_NAME => 'Anthropic',
                ProviderMetadata::KEY_TYPE => 'cloud'
            ],
            ProviderModelsMetadata::KEY_MODELS => [
                [
                    ModelMetadata::KEY_ID => 'claude-2',
                    ModelMetadata::KEY_NAME => 'Claude 2',
                    ModelMetadata::KEY_SUPPORTED_CAPABILITIES => ['text_generation'],
                    ModelMetadata::KEY_SUPPORTED_OPTIONS => []
                ],
                [
                    ModelMetadata::KEY_ID => 'claude-instant',
                    ModelMetadata::KEY_NAME => 'Claude Instant',
                    ModelMetadata::KEY_SUPPORTED_CAPABILITIES => ['text_generation', 'chat_history'],
                    ModelMetadata::KEY_SUPPORTED_OPTIONS => [
                        [
                            SupportedOption::KEY_NAME => 'max_tokens',
                            SupportedOption::KEY_SUPPORTED_VALUES => [100, 1000, 10000]
                        ]
                    ]
                ]
            ]
        ];

        $metadata = ProviderModelsMetadata::fromArray($data);

        $this->assertInstanceOf(ProviderModelsMetadata::class, $metadata);

        // Check provider
        $provider = $metadata->getProvider();
        $this->assertEquals('anthropic', $provider->getId());
        $this->assertEquals('Anthropic', $provider->getName());
        $this->assertTrue($provider->getType()->isCloud());

        // Check models
        $models = $metadata->getModels();
        $this->assertCount(2, $models);
        $this->assertEquals('claude-2', $models[0]->getId());
        $this->assertEquals('Claude 2', $models[0]->getName());
        $this->assertEquals('claude-instant', $models[1]->getId());
        $this->assertEquals('Claude Instant', $models[1]->getName());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $original = new ProviderModelsMetadata(
            $this->createProviderMetadata(),
            [
                $this->createModelMetadata('test-1', 'Test Model 1'),
                $this->createModelMetadata('test-2', 'Test Model 2'),
            ]
        );

        $array = $original->toArray();
        $restored = ProviderModelsMetadata::fromArray($array);

        // Check provider
        $this->assertEquals(
            $original->getProvider()->getId(),
            $restored->getProvider()->getId()
        );
        $this->assertEquals(
            $original->getProvider()->getName(),
            $restored->getProvider()->getName()
        );
        $this->assertEquals(
            $original->getProvider()->getType()->value,
            $restored->getProvider()->getType()->value
        );

        // Check models
        $originalModels = $original->getModels();
        $restoredModels = $restored->getModels();
        $this->assertCount(count($originalModels), $restoredModels);

        for ($i = 0; $i < count($originalModels); $i++) {
            $this->assertEquals($originalModels[$i]->getId(), $restoredModels[$i]->getId());
            $this->assertEquals($originalModels[$i]->getName(), $restoredModels[$i]->getName());
        }
    }

    /**
     * Tests JSON serialization.
     *
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $metadata = new ProviderModelsMetadata(
            $this->createProviderMetadata(),
            [$this->createModelMetadata('json-model', 'JSON Model')]
        );

        $json = json_encode($metadata);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey(ProviderModelsMetadata::KEY_PROVIDER, $decoded);
        $this->assertArrayHasKey(ProviderModelsMetadata::KEY_MODELS, $decoded);
        $this->assertCount(1, $decoded[ProviderModelsMetadata::KEY_MODELS]);
        $this->assertEquals('json-model', $decoded[ProviderModelsMetadata::KEY_MODELS][0][ModelMetadata::KEY_ID]);
    }

    /**
     * Tests with multiple models having different capabilities.
     *
     * @return void
     */
    public function testWithMultipleModelsAndCapabilities(): void
    {
        $provider = new ProviderMetadata('multi-provider', 'Multi Provider', ProviderTypeEnum::server());
        $models = [
            new ModelMetadata(
                'text-only',
                'Text Only Model',
                [CapabilityEnum::textGeneration()],
                []
            ),
            new ModelMetadata(
                'multimodal',
                'Multimodal Model',
                [
                    CapabilityEnum::textGeneration(),
                    CapabilityEnum::imageGeneration(),
                    CapabilityEnum::chatHistory()
                ],
                [
                    new SupportedOption('resolution', ['256x256', '512x512', '1024x1024']),
                    new SupportedOption('style', ['realistic', 'artistic', 'cartoon'])
                ]
            ),
            new ModelMetadata(
                'embedding',
                'Embedding Model',
                [CapabilityEnum::embeddingGeneration()],
                [new SupportedOption('dimensions', [256, 512, 1024])]
            )
        ];

        $metadata = new ProviderModelsMetadata($provider, $models);
        $array = $metadata->toArray();

        $this->assertCount(3, $array[ProviderModelsMetadata::KEY_MODELS]);

        // Verify each model's capabilities are preserved
        $this->assertCount(1, $array[ProviderModelsMetadata::KEY_MODELS][0][ModelMetadata::KEY_SUPPORTED_CAPABILITIES]);
        $this->assertCount(3, $array[ProviderModelsMetadata::KEY_MODELS][1][ModelMetadata::KEY_SUPPORTED_CAPABILITIES]);
        $this->assertCount(1, $array[ProviderModelsMetadata::KEY_MODELS][2][ModelMetadata::KEY_SUPPORTED_CAPABILITIES]);

        // Verify supported options
        $this->assertCount(0, $array[ProviderModelsMetadata::KEY_MODELS][0][ModelMetadata::KEY_SUPPORTED_OPTIONS]);
        $this->assertCount(2, $array[ProviderModelsMetadata::KEY_MODELS][1][ModelMetadata::KEY_SUPPORTED_OPTIONS]);
        $this->assertCount(1, $array[ProviderModelsMetadata::KEY_MODELS][2][ModelMetadata::KEY_SUPPORTED_OPTIONS]);
    }

    /**
     * Tests numeric array keys are preserved.
     *
     * @return void
     */
    public function testNumericArrayKeysPreserved(): void
    {
        $metadata = new ProviderModelsMetadata(
            $this->createProviderMetadata(),
            [
                $this->createModelMetadata('model-1', 'Model 1'),
                $this->createModelMetadata('model-2', 'Model 2'),
            ]
        );

        $array = $metadata->toArray();

        // Ensure models array has numeric keys starting from 0
        $this->assertArrayHasKey(0, $array[ProviderModelsMetadata::KEY_MODELS]);
        $this->assertArrayHasKey(1, $array[ProviderModelsMetadata::KEY_MODELS]);
        $this->assertEquals(['models' => array_keys($array[ProviderModelsMetadata::KEY_MODELS])], ['models' => [0, 1]]);
    }

    /**
     * Tests implements correct interfaces.
     *
     * @return void
     */
    public function testImplementsCorrectInterfaces(): void
    {
        $metadata = new ProviderModelsMetadata($this->createProviderMetadata(), []);

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


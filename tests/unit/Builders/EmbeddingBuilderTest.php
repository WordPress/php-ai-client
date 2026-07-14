<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Builders\EmbeddingBuilder;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\DTO\ProviderModelsMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * @covers \WordPress\AiClient\Builders\EmbeddingBuilder
 */
class EmbeddingBuilderTest extends TestCase
{
    use MockModelCreationTrait;

    /**
     * @var ProviderRegistry&\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = $this->createMock(ProviderRegistry::class);
    }

    /**
     * Reads the parsed inputs from a builder.
     *
     * @param EmbeddingBuilder $builder The builder to inspect.
     * @return list<MessagePart> The parsed inputs.
     */
    private function getInputs(EmbeddingBuilder $builder): array
    {
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('inputs');
        $property->setAccessible(true);

        /** @var list<MessagePart> $inputs */
        $inputs = $property->getValue($builder);

        return $inputs;
    }

    /**
     * Reads the model configuration from a builder.
     *
     * @param EmbeddingBuilder $builder The builder to inspect.
     * @return ModelConfig The model configuration.
     */
    private function getModelConfig(EmbeddingBuilder $builder): ModelConfig
    {
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);

        /** @var ModelConfig $config */
        $config = $property->getValue($builder);

        return $config;
    }

    /**
     * Tests generating an embedding result from a single string input.
     *
     * @return void
     */
    public function testGenerateEmbeddingResultWithModel(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2, 0.3]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $builder = new EmbeddingBuilder($this->registry, 'Generate embedding');
        $builder->usingModel($model);
        $builder->usingDimensions(3);

        $actualResult = $builder->generateEmbeddingResult();

        $this->assertSame($result, $actualResult);
        $this->assertSame(3, $model->getConfig()->getDimensions());
    }

    /**
     * Tests generateEmbedding returns the first vector.
     *
     * @return void
     */
    public function testGenerateEmbeddingReturnsFirstVector(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $builder = new EmbeddingBuilder($this->registry, 'Generate embedding');
        $builder->usingModel($model);

        $this->assertSame([0.1, 0.2], $builder->generateEmbedding()->getValues());
    }

    /**
     * Tests generateEmbeddings returns one vector per input.
     *
     * @return void
     */
    public function testGenerateEmbeddingsReturnsBatchVectors(): void
    {
        $embeddings = [[0.1, 0.2], [0.3, 0.4]];
        $result = $this->createTestEmbeddingResult($embeddings);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $builder = new EmbeddingBuilder($this->registry, ['First input', 'Second input']);
        $builder->usingModel($model);

        $this->assertSame($embeddings, array_map(
            static fn ($embedding): array => $embedding->getValues(),
            $builder->generateEmbeddings()
        ));
    }

    /**
     * Tests the constructor parses a single string input.
     *
     * @return void
     */
    public function testConstructorWithSingleString(): void
    {
        $builder = new EmbeddingBuilder($this->registry, 'A single input');

        $inputs = $this->getInputs($builder);
        $this->assertCount(1, $inputs);
        $this->assertSame('A single input', $inputs[0]->getText());
    }

    /**
     * Tests the constructor parses a list of inputs.
     *
     * @return void
     */
    public function testConstructorWithListOfInputs(): void
    {
        $builder = new EmbeddingBuilder($this->registry, ['First', 'Second', 'Third']);

        $this->assertCount(3, $this->getInputs($builder));
    }

    /**
     * Tests variadic withInput appends to existing inputs.
     *
     * @return void
     */
    public function testWithInputAppendsMultipleInputs(): void
    {
        $builder = new EmbeddingBuilder($this->registry, 'First');
        $builder->withInput('Second', 'Third');

        $this->assertCount(3, $this->getInputs($builder));
    }

    /**
     * Tests withInput appends a single input.
     *
     * @return void
     */
    public function testWithInputAppends(): void
    {
        $builder = new EmbeddingBuilder($this->registry);
        $builder->withInput('First');
        $builder->withInput('Second');

        $this->assertCount(2, $this->getInputs($builder));
    }

    /**
     * Tests withInput rejects an empty invocation.
     *
     * @return void
     */
    public function testWithInputRejectsNoInputs(): void
    {
        $builder = new EmbeddingBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one input must be provided.');

        $builder->withInput();
    }

    /**
     * Tests an empty string input is rejected.
     *
     * @return void
     */
    public function testWithInputRejectsEmptyString(): void
    {
        $builder = new EmbeddingBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create an embedding input from an empty string.');

        $builder->withInput('   ');
    }

    /**
     * Tests a function response message part is rejected.
     *
     * @return void
     */
    public function testWithInputRejectsFunctionPart(): void
    {
        $part = new MessagePart(new FunctionResponse('call-1', 'get_weather', ['temp' => 21]));

        $builder = new EmbeddingBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Embedding inputs must be text or file parts.');

        $builder->withInput($part);
    }

    /**
     * Tests a File input is accepted.
     *
     * @return void
     */
    public function testWithInputAcceptsFile(): void
    {
        $builder = new EmbeddingBuilder($this->registry);
        $builder->withInput(new File('https://example.com/image.jpg', 'image/jpeg'));

        $inputs = $this->getInputs($builder);
        $this->assertCount(1, $inputs);
        $this->assertTrue($inputs[0]->getType()->isFile());
    }

    /**
     * Tests a MessagePart input is accepted.
     *
     * @return void
     */
    public function testWithInputAcceptsMessagePart(): void
    {
        $builder = new EmbeddingBuilder($this->registry);
        $builder->withInput(new MessagePart('Part text'));

        $inputs = $this->getInputs($builder);
        $this->assertCount(1, $inputs);
        $this->assertSame('Part text', $inputs[0]->getText());
    }

    /**
     * Tests a message part array shape input is accepted.
     *
     * @return void
     */
    public function testWithInputAcceptsMessagePartArrayShape(): void
    {
        $builder = new EmbeddingBuilder($this->registry);
        $builder->withInput(['type' => 'text', 'text' => 'Shape text']);

        $inputs = $this->getInputs($builder);
        $this->assertCount(1, $inputs);
        $this->assertSame('Shape text', $inputs[0]->getText());
    }

    /**
     * Tests an unsupported input type is rejected.
     *
     * @return void
     */
    public function testWithInputRejectsUnsupportedType(): void
    {
        $builder = new EmbeddingBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Embedding input must be a string, MessagePart, File, or MessagePartArrayShape.');

        /** @phpstan-ignore-next-line Intentionally passing an invalid type. */
        $builder->withInput(123);
    }

    /**
     * Tests generateEmbedding throws when multiple inputs are provided.
     *
     * @return void
     */
    public function testGenerateEmbeddingThrowsForMultipleInputs(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2], [0.3, 0.4]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $builder = new EmbeddingBuilder($this->registry, ['First input', 'Second input']);
        $builder->usingModel($model);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'generateEmbedding() returns a single vector; use generateEmbeddings() for multiple inputs.'
        );

        $builder->generateEmbedding();
    }

    /**
     * Tests generateEmbeddingResult throws when no inputs are configured.
     *
     * @return void
     */
    public function testGenerateEmbeddingResultThrowsForEmptyInput(): void
    {
        $builder = new EmbeddingBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot generate embeddings from empty input. Add content using withInput().');

        $builder->generateEmbeddingResult();
    }

    /**
     * Tests generateEmbeddingResult throws when the model returns a mismatched embedding count.
     *
     * @return void
     */
    public function testGenerateEmbeddingResultThrowsOnCountMismatch(): void
    {
        // Model returns a single embedding, but two inputs are requested.
        $result = $this->createTestEmbeddingResult([[0.1, 0.2]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $builder = new EmbeddingBuilder($this->registry, ['First input', 'Second input']);
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected 2 embedding(s) from the model, but received 1.');

        $builder->generateEmbeddings();
    }

    /**
     * Tests generateEmbeddingResult throws for a model that does not support embeddings.
     *
     * @return void
     */
    public function testGenerateEmbeddingResultThrowsForUnsupportedModel(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);

        $builder = new EmbeddingBuilder($this->registry, 'Generate embedding');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "test-model" does not support embedding generation');

        $builder->generateEmbeddingResult();
    }

    /**
     * Tests model selection derives text input modality from the inputs.
     *
     * @return void
     */
    public function testModelSelectionUsesInputModalities(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2], [0.3, 0.4]]);
        $model = $this->createMockEmbeddingGenerationModel($result);
        $modelMetadata = $this->createTestEmbeddingModelMetadata('batch-embedding-model');
        $providerMetadata = new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud());

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->with($this->callback(static function (ModelRequirements $requirements): bool {
                foreach ($requirements->getRequiredOptions() as $requiredOption) {
                    if ($requiredOption->getName()->isInputModalities()) {
                        return [ModalityEnum::text()] === $requiredOption->getValue();
                    }
                }

                return false;
            }))
            ->willReturn([new ProviderModelsMetadata($providerMetadata, [$modelMetadata])]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('mock', 'batch-embedding-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $builder = new EmbeddingBuilder($this->registry, ['First input', 'Second input']);

        $this->assertCount(2, $builder->generateEmbeddings());
    }

    /**
     * Tests model preferences are honored through the shared trait.
     *
     * @return void
     */
    public function testModelPreferenceSelectsModel(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2]]);
        $metadata = $this->createTestEmbeddingModelMetadata('preferred-embedding-model');
        $model = $this->createMockEmbeddingGenerationModel($result, $metadata);
        $providerMetadata = new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud());

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->willReturn([new ProviderModelsMetadata($providerMetadata, [$metadata])]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('mock', 'preferred-embedding-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $builder = new EmbeddingBuilder($this->registry, 'Embed this');
        $builder->usingModelPreference('preferred-embedding-model');

        $this->assertCount(1, $builder->generateEmbeddings());
    }

    /**
     * Tests usingDimensions is reflected in the model configuration.
     *
     * @return void
     */
    public function testUsingDimensionsIsAppliedToConfig(): void
    {
        $builder = new EmbeddingBuilder($this->registry, 'Embed this');
        $builder->usingDimensions(512);

        $this->assertSame(512, $this->getModelConfig($builder)->getDimensions());
    }

    /**
     * Tests isSupported delegates to the resolver.
     *
     * @return void
     */
    public function testIsSupportedReturnsFalseWhenNoModels(): void
    {
        $this->registry->method('findModelsMetadataForSupport')->willReturn([]);

        $builder = new EmbeddingBuilder($this->registry, 'Embed this');

        $this->assertFalse($builder->isSupported());
    }

    /**
     * Tests cloning deep-copies inputs and configuration.
     *
     * @return void
     */
    public function testCloneDeepCopiesInputsAndConfig(): void
    {
        $original = new EmbeddingBuilder($this->registry, 'Original input');
        $original->usingDimensions(256);

        $cloned = clone $original;
        $cloned->withInput('Added to clone');

        // The clone's new input must not leak back into the original.
        $this->assertCount(1, $this->getInputs($original));
        $this->assertCount(2, $this->getInputs($cloned));

        // Input parts are distinct instances.
        $this->assertNotSame(
            $this->getInputs($original)[0],
            $this->getInputs($cloned)[0]
        );

        // Config is a distinct instance with equal values.
        $this->assertNotSame($this->getModelConfig($original), $this->getModelConfig($cloned));
        $this->assertSame(256, $this->getModelConfig($cloned)->getDimensions());
    }
}

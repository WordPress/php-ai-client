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
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
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

        // A model is mandatory, and its provider must be configured to be usable. Tests that need an
        // unconfigured provider create their own registry mock.
        $this->registry->method('isProviderConfigured')->willReturn(true);
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
     * Tests a spread input list appends multiple inputs.
     *
     * @return void
     */
    public function testWithInputAppendsSpreadInputList(): void
    {
        $builder = new EmbeddingBuilder($this->registry, 'First');
        $builder->withInput(...['Second', 'Third']);

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
        $model = $this->createMockUnsupportedModel('test-model');

        $builder = new EmbeddingBuilder($this->registry, 'Generate embedding');
        $builder->usingModel($model);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model "test-model" from provider "mock" does not support embedding generation.'
        );

        $builder->generateEmbeddingResult();
    }

    /**
     * Tests generateEmbeddingResult throws when no model was specified.
     *
     * @return void
     */
    public function testGenerateEmbeddingResultThrowsWhenNoModelSpecified(): void
    {
        $builder = new EmbeddingBuilder($this->registry, 'Embed this');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('An embedding model must be specified.');

        $builder->generateEmbeddingResult();
    }

    /**
     * Tests generateEmbeddingResult throws when the model's provider is not configured.
     *
     * @return void
     */
    public function testGenerateEmbeddingResultThrowsWhenProviderNotConfigured(): void
    {
        $registry = $this->createMock(ProviderRegistry::class);
        $registry->method('isProviderConfigured')->willReturn(false);

        $result = $this->createTestEmbeddingResult([[0.1, 0.2]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $builder = new EmbeddingBuilder($registry, 'Embed this');
        $builder->usingModel($model);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Provider "mock" is not registered, or is not configured with valid credentials.'
        );

        $builder->generateEmbeddingResult();
    }

    /**
     * Tests generateEmbeddingResult throws when the model does not support the configured dimensions.
     *
     * @return void
     */
    public function testGenerateEmbeddingResultThrowsForUnsupportedOption(): void
    {
        // A model that supports text input, but does not advertise support for dimensions.
        $metadata = $this->createTestEmbeddingModelMetadata(
            'fixed-dimensions-model',
            'Fixed Dimensions Model',
            [new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]])]
        );
        $model = $this->createMockEmbeddingGenerationModel(
            $this->createTestEmbeddingResult([[0.1, 0.2]]),
            $metadata
        );

        $builder = new EmbeddingBuilder($this->registry, 'Embed this');
        $builder->usingModel($model);
        $builder->usingDimensions(256);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model "fixed-dimensions-model" from provider "mock" cannot fulfill this embedding request. '
            . 'Unsupported options: dimensions (256).'
        );

        $builder->generateEmbeddingResult();
    }

    /**
     * Tests generateEmbeddingResult throws when the model does not support the input modality.
     *
     * @return void
     */
    public function testGenerateEmbeddingResultThrowsForUnsupportedInputModality(): void
    {
        // The default embedding metadata supports text input only.
        $model = $this->createMockEmbeddingGenerationModel($this->createTestEmbeddingResult([[0.1, 0.2]]));

        $builder = new EmbeddingBuilder($this->registry);
        $builder->withInput(new File('https://example.com/image.jpg', 'image/jpeg'));
        $builder->usingModel($model);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported options: inputModalities ([image]).');

        $builder->generateEmbeddingResult();
    }

    /**
     * Tests usingProviderModel retrieves the model from the registry.
     *
     * @return void
     */
    public function testUsingProviderModelResolvesModelFromRegistry(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('mock', 'test-embedding-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $builder = new EmbeddingBuilder($this->registry, 'Embed this');
        $builder->usingProviderModel('mock', 'test-embedding-model');

        $this->assertCount(1, $builder->generateEmbeddings());
    }

    /**
     * Tests usingProviderModel defers resolution so later configuration still reaches the model.
     *
     * @return void
     */
    public function testUsingProviderModelAppliesLaterConfiguration(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with(
                'mock',
                'test-embedding-model',
                $this->callback(
                    static fn (ModelConfig $config): bool => $config->getDimensions() === 256
                )
            )
            ->willReturn($model);

        $builder = new EmbeddingBuilder($this->registry, 'Embed this');
        $builder->usingProviderModel('mock', 'test-embedding-model');

        // Configured after the model, so resolution must be deferred until generation.
        $builder->usingDimensions(256);

        $this->assertCount(1, $builder->generateEmbeddings());
    }

    /**
     * Tests usingProviderModel rejects empty identifiers.
     *
     * @return void
     */
    public function testUsingProviderModelRejectsEmptyProviderIdentifier(): void
    {
        $builder = new EmbeddingBuilder($this->registry, 'Embed this');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider identifier cannot be empty.');

        $builder->usingProviderModel('  ', 'test-embedding-model');
    }

    /**
     * Tests usingProviderModel rejects an empty model identifier.
     *
     * @return void
     */
    public function testUsingProviderModelRejectsEmptyModelIdentifier(): void
    {
        $builder = new EmbeddingBuilder($this->registry, 'Embed this');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model identifier cannot be empty.');

        $builder->usingProviderModel('mock', '  ');
    }

    /**
     * Tests usingModel supersedes a previously set provider model.
     *
     * @return void
     */
    public function testUsingModelSupersedesProviderModel(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        // The registry must not be asked for a model once an instance is provided.
        $this->registry->expects($this->never())->method('getProviderModel');

        $builder = new EmbeddingBuilder($this->registry, 'Embed this');
        $builder->usingProviderModel('mock', 'test-embedding-model');
        $builder->usingModel($model);

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
     * Tests the model configuration is applied to the specified model during generation.
     *
     * @return void
     */
    public function testModelConfigIsAppliedToSpecifiedModel(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $this->registry->expects($this->once())
            ->method('bindModelDependencies')
            ->with($model);

        $builder = new EmbeddingBuilder($this->registry, 'Embed this');
        $builder->usingModel($model);
        $builder->usingDimensions(256);

        $builder->generateEmbeddingResult();

        $this->assertSame(256, $model->getConfig()->getDimensions());
    }

    /**
     * Tests isSupported returns true when the specified model supports the request.
     *
     * @return void
     */
    public function testIsSupportedReturnsTrueForSupportedModel(): void
    {
        $model = $this->createMockEmbeddingGenerationModel($this->createTestEmbeddingResult());

        $builder = new EmbeddingBuilder($this->registry, 'Embed this');
        $builder->usingModel($model);
        $builder->usingDimensions(256);

        $this->assertTrue($builder->isSupported());
    }

    /**
     * Tests isSupported returns false when the specified model does not support an option.
     *
     * @return void
     */
    public function testIsSupportedReturnsFalseForUnsupportedOption(): void
    {
        $metadata = $this->createTestEmbeddingModelMetadata(
            'fixed-dimensions-model',
            'Fixed Dimensions Model',
            [new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]])]
        );
        $model = $this->createMockEmbeddingGenerationModel($this->createTestEmbeddingResult(), $metadata);

        $builder = new EmbeddingBuilder($this->registry, 'Embed this');
        $builder->usingModel($model);
        $builder->usingDimensions(256);

        $this->assertFalse($builder->isSupported());
    }

    /**
     * Tests isSupported returns false when the specified model cannot generate embeddings.
     *
     * @return void
     */
    public function testIsSupportedReturnsFalseForNonEmbeddingModel(): void
    {
        $builder = new EmbeddingBuilder($this->registry, 'Embed this');
        $builder->usingModel($this->createMockUnsupportedModel('test-model'));

        $this->assertFalse($builder->isSupported());
    }

    /**
     * Tests isSupported throws when no model was specified.
     *
     * @return void
     */
    public function testIsSupportedThrowsWhenNoModelSpecified(): void
    {
        $builder = new EmbeddingBuilder($this->registry, 'Embed this');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('An embedding model must be specified.');

        $builder->isSupported();
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

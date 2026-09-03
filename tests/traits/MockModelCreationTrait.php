<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\traits;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts\EmbeddingGenerationModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\Models\VideoGeneration\Contracts\VideoGenerationModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tests\mocks\MockProvider;

/**
 * Trait providing shared mock model creation methods for testing.
 *
 * This trait consolidates common mock model creation logic to reduce
 * code duplication across test classes and improve maintainability.
 */
trait MockModelCreationTrait
{
    /**
     * Creates a provider registry with the mock provider registered.
     *
     * @return ProviderRegistry The registry with mock provider.
     */
    protected function createRegistryWithMockProvider(): ProviderRegistry
    {
        $registry = new ProviderRegistry();
        $registry->registerProvider(MockProvider::class);
        return $registry;
    }
    /**
     * Creates a test GenerativeAiResult for testing purposes.
     *
     * @param string $content Optional content for the response.
     * @return GenerativeAiResult
     */
    protected function createTestResult(string $content = 'Test response'): GenerativeAiResult
    {
        return $this->createTestResultWithMessage(new ModelMessage([new MessagePart($content)]));
    }

    /**
     * Creates a test GenerativeAiResult with the given model message.
     *
     * @param Message $message The model message.
     * @param TokenUsage|null $tokenUsage Optional token usage. Defaults to 10/20/30.
     * @param FinishReasonEnum|null $finishReason Optional finish reason. Defaults to stop.
     * @return GenerativeAiResult
     */
    protected function createTestResultWithMessage(
        Message $message,
        ?TokenUsage $tokenUsage = null,
        ?FinishReasonEnum $finishReason = null
    ): GenerativeAiResult {
        $candidate = new Candidate($message, $finishReason ?? FinishReasonEnum::stop());
        $tokenUsage = $tokenUsage ?? new TokenUsage(10, 20, 30);

        $providerMetadata = new ProviderMetadata(
            'mock',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );
        $modelMetadata = new ModelMetadata(
            'mock-model',
            'Mock Model',
            [],
            []
        );

        return new GenerativeAiResult(
            'test-result-id',
            [$candidate],
            $tokenUsage,
            $providerMetadata,
            $modelMetadata
        );
    }

    /**
     * Creates a test EmbeddingResult for testing purposes.
     *
     * @param list<list<float|int>>|null $embeddings Optional embeddings for the response.
     * @return EmbeddingResult
     */
    protected function createTestEmbeddingResult(?array $embeddings = null): EmbeddingResult
    {
        $embeddings = $embeddings ?? [[0.1, 0.2, 0.3]];

        $providerMetadata = new ProviderMetadata(
            'mock',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );
        $modelMetadata = new ModelMetadata(
            'mock-embedding-model',
            'Mock Embedding Model',
            [CapabilityEnum::embeddingGeneration()],
            []
        );

        return new EmbeddingResult(
            'test-embedding-result-id',
            $embeddings,
            count($embeddings[0]),
            new TokenUsage(10, 0, 10),
            $providerMetadata,
            $modelMetadata
        );
    }

    /**
     * Creates a test model metadata instance for text generation.
     *
     * @param string $id Optional model ID.
     * @param string $name Optional model name.
     * @return ModelMetadata
     */
    protected function createTestTextModelMetadata(
        string $id = 'test-text-model',
        string $name = 'Test Text Model'
    ): ModelMetadata {
        return new ModelMetadata(
            $id,
            $name,
            [CapabilityEnum::textGeneration()],
            []
        );
    }

    /**
     * Creates a test model metadata instance for image generation.
     *
     * @param string $id Optional model ID.
     * @param string $name Optional model name.
     * @return ModelMetadata
     */
    protected function createTestImageModelMetadata(
        string $id = 'test-image-model',
        string $name = 'Test Image Model'
    ): ModelMetadata {
        return new ModelMetadata(
            $id,
            $name,
            [CapabilityEnum::imageGeneration()],
            []
        );
    }

    /**
     * Creates a test model metadata instance for video generation.
     *
     * @param string $id   Optional model ID.
     * @param string $name Optional model name.
     * @return ModelMetadata
     */
    protected function createTestVideoModelMetadata(
        string $id = 'test-video-model',
        string $name = 'Test Video Model'
    ): ModelMetadata {
        return new ModelMetadata(
            $id,
            $name,
            [CapabilityEnum::videoGeneration()],
            []
        );
    }

    /**
     * Creates a test model metadata instance for embedding generation.
     *
     * The default supported options mirror what the Google and OpenAI providers declare for their
     * embedding models: text-only input, any dimensions, and any custom options. Pass an explicit
     * list to simulate a model with narrower support.
     *
     * @param string $id Optional model ID.
     * @param string $name Optional model name.
     * @param list<SupportedOption>|null $supportedOptions Optional supported options.
     * @return ModelMetadata
     */
    protected function createTestEmbeddingModelMetadata(
        string $id = 'test-embedding-model',
        string $name = 'Test Embedding Model',
        ?array $supportedOptions = null
    ): ModelMetadata {
        $supportedOptions = $supportedOptions ?? [
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::dimensions()),
            new SupportedOption(OptionEnum::customOptions()),
        ];

        return new ModelMetadata(
            $id,
            $name,
            [CapabilityEnum::embeddingGeneration()],
            $supportedOptions
        );
    }

    /**
     * Creates a mock text generation model using anonymous class.
     *
     * @param GenerativeAiResult $result The result to return from generation.
     * @param ModelMetadata|null $metadata Optional metadata (uses default if not provided).
     * @return ModelInterface&TextGenerationModelInterface The mock model.
     */
    protected function createMockTextGenerationModel(
        GenerativeAiResult $result,
        ?ModelMetadata $metadata = null
    ): ModelInterface {
        return $this->createScriptedTextGenerationModel([$result], $metadata);
    }

    /**
     * Creates a mock text generation model that returns scripted results in order.
     *
     * Each call to generateTextResult() returns the next result from the given
     * list. Once the list is exhausted, the last result is returned again.
     *
     * @param list<GenerativeAiResult> $results The results to return, in order. Must not be empty.
     * @param ModelMetadata|null $metadata Optional metadata (uses default if not provided).
     * @return ModelInterface&TextGenerationModelInterface The mock model.
     */
    protected function createScriptedTextGenerationModel(
        array $results,
        ?ModelMetadata $metadata = null
    ): ModelInterface {
        if (empty($results)) {
            throw new InvalidArgumentException('At least one scripted result must be provided.');
        }

        $metadata = $metadata ?? $this->createTestTextModelMetadata();

        $providerMetadata = new ProviderMetadata(
            'mock',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );

        return new class (
            $metadata,
            $providerMetadata,
            $results
        ) implements ModelInterface, TextGenerationModelInterface {
            private ModelMetadata $metadata;
            private ProviderMetadata $providerMetadata;
            /** @var list<GenerativeAiResult> */
            private array $results;
            private int $callCount = 0;
            private ModelConfig $config;

            /**
             * @param list<GenerativeAiResult> $results
             */
            public function __construct(
                ModelMetadata $metadata,
                ProviderMetadata $providerMetadata,
                array $results
            ) {
                $this->metadata = $metadata;
                $this->providerMetadata = $providerMetadata;
                $this->results = $results;
                $this->config = new ModelConfig();
            }

            public function metadata(): ModelMetadata
            {
                return $this->metadata;
            }

            public function providerMetadata(): ProviderMetadata
            {
                return $this->providerMetadata;
            }

            public function setConfig(ModelConfig $config): void
            {
                $this->config = $config;
            }

            public function getConfig(): ModelConfig
            {
                return $this->config;
            }

            public function generateTextResult(array $prompt): GenerativeAiResult
            {
                $index = min($this->callCount, count($this->results) - 1);
                $this->callCount++;
                return $this->results[$index];
            }
        };
    }

    /**
     * Creates a mock image generation model using anonymous class.
     *
     * @param GenerativeAiResult $result The result to return from generation.
     * @param ModelMetadata|null $metadata Optional metadata (uses default if not provided).
     * @return ModelInterface&ImageGenerationModelInterface The mock model.
     */
    protected function createMockImageGenerationModel(
        GenerativeAiResult $result,
        ?ModelMetadata $metadata = null
    ): ModelInterface {
        $metadata = $metadata ?? $this->createTestImageModelMetadata();

        $providerMetadata = new ProviderMetadata(
            'mock',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );

        return new class (
            $metadata,
            $providerMetadata,
            $result
        ) implements ModelInterface, ImageGenerationModelInterface {
            private ModelMetadata $metadata;
            private ProviderMetadata $providerMetadata;
            private GenerativeAiResult $result;
            private ModelConfig $config;

            public function __construct(
                ModelMetadata $metadata,
                ProviderMetadata $providerMetadata,
                GenerativeAiResult $result
            ) {
                $this->metadata = $metadata;
                $this->providerMetadata = $providerMetadata;
                $this->result = $result;
                $this->config = new ModelConfig();
            }

            public function metadata(): ModelMetadata
            {
                return $this->metadata;
            }

            public function providerMetadata(): ProviderMetadata
            {
                return $this->providerMetadata;
            }

            public function setConfig(ModelConfig $config): void
            {
                $this->config = $config;
            }

            public function getConfig(): ModelConfig
            {
                return $this->config;
            }

            public function generateImageResult(array $prompt): GenerativeAiResult
            {
                return $this->result;
            }
        };
    }

    /**
     * Creates a mock video generation model using anonymous class.
     *
     * @param GenerativeAiResult $result   The result to return from generation.
     * @param ModelMetadata|null $metadata Optional metadata (uses default if not provided).
     * @return ModelInterface&VideoGenerationModelInterface The mock model.
     */
    protected function createMockVideoGenerationModel(
        GenerativeAiResult $result,
        ?ModelMetadata $metadata = null
    ): ModelInterface {
        $metadata = $metadata ?? $this->createTestVideoModelMetadata();

        $providerMetadata = new ProviderMetadata(
            'mock',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );

        return new class (
            $metadata,
            $providerMetadata,
            $result
        ) implements ModelInterface, VideoGenerationModelInterface {
            private ModelMetadata $metadata;
            private ProviderMetadata $providerMetadata;
            private GenerativeAiResult $result;
            private ModelConfig $config;

            public function __construct(
                ModelMetadata $metadata,
                ProviderMetadata $providerMetadata,
                GenerativeAiResult $result
            ) {
                $this->metadata = $metadata;
                $this->providerMetadata = $providerMetadata;
                $this->result = $result;
                $this->config = new ModelConfig();
            }

            public function metadata(): ModelMetadata
            {
                return $this->metadata;
            }

            public function providerMetadata(): ProviderMetadata
            {
                return $this->providerMetadata;
            }

            public function setConfig(ModelConfig $config): void
            {
                $this->config = $config;
            }

            public function getConfig(): ModelConfig
            {
                return $this->config;
            }

            public function generateVideoResult(array $prompt): GenerativeAiResult
            {
                return $this->result;
            }
        };
    }

    /**
     * Creates a mock embedding generation model using anonymous class.
     *
     * @param EmbeddingResult $result The result to return from generation.
     * @param ModelMetadata|null $metadata Optional metadata (uses default if not provided).
     * @return ModelInterface&EmbeddingGenerationModelInterface The mock model.
     */
    protected function createMockEmbeddingGenerationModel(
        EmbeddingResult $result,
        ?ModelMetadata $metadata = null
    ): ModelInterface {
        $metadata = $metadata ?? $this->createTestEmbeddingModelMetadata();

        $providerMetadata = new ProviderMetadata(
            'mock',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );

        return new class (
            $metadata,
            $providerMetadata,
            $result
        ) implements ModelInterface, EmbeddingGenerationModelInterface {
            private ModelMetadata $metadata;
            private ProviderMetadata $providerMetadata;
            private EmbeddingResult $result;
            private ModelConfig $config;

            public function __construct(
                ModelMetadata $metadata,
                ProviderMetadata $providerMetadata,
                EmbeddingResult $result
            ) {
                $this->metadata = $metadata;
                $this->providerMetadata = $providerMetadata;
                $this->result = $result;
                $this->config = new ModelConfig();
            }

            public function metadata(): ModelMetadata
            {
                return $this->metadata;
            }

            public function providerMetadata(): ProviderMetadata
            {
                return $this->providerMetadata;
            }

            public function setConfig(ModelConfig $config): void
            {
                $this->config = $config;
            }

            public function getConfig(): ModelConfig
            {
                return $this->config;
            }

            /**
             * @param list<MessagePart> $inputs The inputs to embed.
             */
            public function generateEmbeddingResult(array $inputs): EmbeddingResult
            {
                return $this->result;
            }
        };
    }

    /**
     * Creates a mock model that doesn't implement any generation interfaces.
     *
     * @param string $modelId Optional model ID for error messages.
     * @return ModelInterface The mock model.
     */
    protected function createMockUnsupportedModel(string $modelId = 'unsupported-model'): ModelInterface
    {
        $mockModel = $this->createMock(ModelInterface::class);
        $mockMetadata = $this->createMock(ModelMetadata::class);
        $mockProviderMetadata = new ProviderMetadata(
            'mock',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );

        $mockMetadata->expects($this->any())
            ->method('getId')
            ->willReturn($modelId);

        $mockModel->expects($this->any())
            ->method('metadata')
            ->willReturn($mockMetadata);

        $mockModel->expects($this->any())
            ->method('providerMetadata')
            ->willReturn($mockProviderMetadata);

        $mockModel->expects($this->any())
            ->method('getConfig')
            ->willReturn(new ModelConfig());

        return $mockModel;
    }
}

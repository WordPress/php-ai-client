<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\traits;

use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\Candidate;
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
        $candidate = new Candidate(
            new ModelMessage([new MessagePart($content)]),
            FinishReasonEnum::stop()
        );
        $tokenUsage = new TokenUsage(10, 20, 30);

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
        $metadata = $metadata ?? $this->createTestTextModelMetadata();

        $providerMetadata = new ProviderMetadata(
            'mock',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );

        return new class (
            $metadata,
            $providerMetadata,
            $result
        ) implements ModelInterface, TextGenerationModelInterface {
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

            public function generateTextResult(array $prompt): GenerativeAiResult
            {
                return $this->result;
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

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit;

use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
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

/**
 * @covers \WordPress\AiClient\AiClient
 */
class AiClientTest extends TestCase
{
    protected function setUp(): void
    {
        // Set a clean registry for each test
        AiClient::setDefaultRegistry(new ProviderRegistry());
    }

    /**
     * Creates a test GenerativeAiResult for testing purposes.
     */
    private function createTestResult(): GenerativeAiResult
    {
        $candidate = new Candidate(
            new ModelMessage([new MessagePart('Test response')]),
            FinishReasonEnum::stop()
        );
        $tokenUsage = new TokenUsage(10, 20, 30);

        $providerMetadata = new ProviderMetadata(
            'mock-provider',
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

    protected function tearDown(): void
    {
        // Reset the default registry
        AiClient::setDefaultRegistry(new ProviderRegistry());
    }


    /**
     * Creates a test model metadata instance for text generation.
     *
     * @return ModelMetadata
     */
    private function createTestTextModelMetadata(): ModelMetadata
    {
        return new ModelMetadata(
            'test-text-model',
            'Test Text Model',
            [CapabilityEnum::textGeneration()],
            []
        );
    }

    /**
     * Creates a test model metadata instance for image generation.
     *
     * @return ModelMetadata
     */
    private function createTestImageModelMetadata(): ModelMetadata
    {
        return new ModelMetadata(
            'test-image-model',
            'Test Image Model',
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
    private function createMockTextGenerationModel(
        GenerativeAiResult $result,
        ?ModelMetadata $metadata = null
    ): ModelInterface {
        $metadata = $metadata ?? $this->createTestTextModelMetadata();

        return new class ($metadata, $result) implements ModelInterface, TextGenerationModelInterface {
            private ModelMetadata $metadata;
            private GenerativeAiResult $result;
            private ModelConfig $config;

            public function __construct(ModelMetadata $metadata, GenerativeAiResult $result)
            {
                $this->metadata = $metadata;
                $this->result = $result;
                $this->config = new ModelConfig();
            }

            public function metadata(): ModelMetadata
            {
                return $this->metadata;
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

            public function streamGenerateTextResult(array $prompt): Generator
            {
                yield $this->result;
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
    private function createMockImageGenerationModel(
        GenerativeAiResult $result,
        ?ModelMetadata $metadata = null
    ): ModelInterface {
        $metadata = $metadata ?? $this->createTestImageModelMetadata();

        return new class ($metadata, $result) implements ModelInterface, ImageGenerationModelInterface {
            private ModelMetadata $metadata;
            private GenerativeAiResult $result;
            private ModelConfig $config;

            public function __construct(ModelMetadata $metadata, GenerativeAiResult $result)
            {
                $this->metadata = $metadata;
                $this->result = $result;
                $this->config = new ModelConfig();
            }

            public function metadata(): ModelMetadata
            {
                return $this->metadata;
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
     * Tests default registry getter and setter.
     */
    public function testDefaultRegistry(): void
    {
        $registry = AiClient::defaultRegistry();
        $this->assertInstanceOf(ProviderRegistry::class, $registry);

        $newRegistry = new ProviderRegistry();
        AiClient::setDefaultRegistry($newRegistry);

        $this->assertSame($newRegistry, AiClient::defaultRegistry());
    }

    /**
     * Tests message method throws exception when MessageBuilder is not available.
     */
    public function testMessageThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'MessageBuilder is not yet available. This method depends on builder infrastructure. ' .
            'Use direct generation methods (generateTextResult, generateImageResult, etc.) for now.'
        );

        AiClient::message('Test message');
    }

    /**
     * Tests generateTextResult with string prompt and provided model.
     */
    public function testGenerateTextResultWithStringAndModel(): void
    {
        $prompt = 'Generate text';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);

        $result = AiClient::generateTextResult($prompt, $mockModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateTextResult throws exception for model without text generation interface.
     */
    public function testGenerateTextResultWithInvalidModel(): void
    {
        $prompt = 'Generate text';
        $invalidModel = $this->createMock(ModelInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "" does not support text generation.');

        AiClient::generateTextResult($prompt, $invalidModel);
    }

    /**
     * Tests generateImageResult with string prompt and provided model.
     */
    public function testGenerateImageResultWithStringAndModel(): void
    {
        $prompt = 'Generate image';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockImageGenerationModel($expectedResult);

        $result = AiClient::generateImageResult($prompt, $mockModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateImageResult throws exception for model without image generation interface.
     */
    public function testGenerateImageResultWithInvalidModel(): void
    {
        $prompt = 'Generate image';
        $invalidModel = $this->createMock(ModelInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "" does not support image generation.');

        AiClient::generateImageResult($prompt, $invalidModel);
    }


    /**
     * Tests generateTextResult with Message object.
     */
    public function testGenerateTextResultWithMessage(): void
    {
        $messagePart = new MessagePart('Test message');
        $message = new UserMessage([$messagePart]);
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);

        $result = AiClient::generateTextResult($message, $mockModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateTextResult with MessagePart object.
     */
    public function testGenerateTextResultWithMessagePart(): void
    {
        $messagePart = new MessagePart('Test message part');
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);

        $result = AiClient::generateTextResult($messagePart, $mockModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateTextResult with array of Messages.
     */
    public function testGenerateTextResultWithMessageArray(): void
    {
        $messagePart1 = new MessagePart('First message');
        $messagePart2 = new MessagePart('Second message');
        $message1 = new UserMessage([$messagePart1]);
        $message2 = new UserMessage([$messagePart2]);
        $messages = [$message1, $message2];

        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);

        $result = AiClient::generateTextResult($messages, $mockModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateTextResult with array of MessageParts.
     */
    public function testGenerateTextResultWithMessagePartArray(): void
    {
        $messagePart1 = new MessagePart('First part');
        $messagePart2 = new MessagePart('Second part');
        $messageParts = [$messagePart1, $messagePart2];

        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);

        $result = AiClient::generateTextResult($messageParts, $mockModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests isConfigured method returns true when provider availability is configured.
     */
    public function testIsConfiguredReturnsTrueWhenProviderIsConfigured(): void
    {
        $mockAvailability = $this->createMock(ProviderAvailabilityInterface::class);
        $mockAvailability->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $result = AiClient::isConfigured($mockAvailability);

        $this->assertTrue($result);
    }

    /**
     * Tests isConfigured method returns false when provider availability is not configured.
     */
    public function testIsConfiguredReturnsFalseWhenProviderIsNotConfigured(): void
    {
        $mockAvailability = $this->createMock(ProviderAvailabilityInterface::class);
        $mockAvailability->expects($this->once())
            ->method('isConfigured')
            ->willReturn(false);

        $result = AiClient::isConfigured($mockAvailability);

        $this->assertFalse($result);
    }

    /**
     * Tests generateResult delegates to generateTextResult when model supports text generation.
     */
    public function testGenerateResultDelegatesToTextGeneration(): void
    {
        $prompt = 'Test prompt';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);

        $result = AiClient::generateResult($prompt, $mockModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateResult delegates to generateImageResult when model supports image generation.
     */
    public function testGenerateResultDelegatesToImageGeneration(): void
    {
        $prompt = 'Generate image prompt';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockImageGenerationModel($expectedResult);

        $result = AiClient::generateResult($prompt, $mockModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateResult throws exception when model doesn't support any generation interface.
     */
    public function testGenerateResultThrowsExceptionForUnsupportedModel(): void
    {
        $prompt = 'Test prompt';
        $unsupportedModel = $this->createMock(ModelInterface::class);

        // Mock the metadata to return an ID
        $mockMetadata = $this->createMock(\WordPress\AiClient\Providers\Models\DTO\ModelMetadata::class);
        $mockMetadata->expects($this->once())
            ->method('getId')
            ->willReturn('unsupported-model');

        $unsupportedModel->expects($this->once())
            ->method('metadata')
            ->willReturn($mockMetadata);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model "unsupported-model" must implement at least one supported generation interface ' .
            '(TextGeneration, ImageGeneration, TextToSpeechConversion, SpeechGeneration)'
        );

        AiClient::generateResult($prompt, $unsupportedModel);
    }








    /**
     * Tests generateResult with null model delegates to PromptBuilder.
     */
    public function testGenerateResultWithNullModelDelegatesToPromptBuilder(): void
    {
        $prompt = 'Test prompt for auto-discovery';

        // Create a mock registry that returns empty results
        $mockRegistry = $this->createMock(ProviderRegistry::class);
        $mockRegistry
            ->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->willReturn([]);

        // Set the mock registry as default
        AiClient::setDefaultRegistry($mockRegistry);

        // This should delegate to PromptBuilder's intelligent discovery
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No models found that support the required capabilities');

        AiClient::generateResult($prompt);
    }

    /**
     * Tests generateResult with text generation model.
     */
    public function testGenerateResultWithTextGenerationModel(): void
    {
        $prompt = 'Generate text content';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);

        $result = AiClient::generateResult($prompt, $mockModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateResult with image generation model.
     */
    public function testGenerateResultWithImageGenerationModel(): void
    {
        $prompt = 'Generate an image';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockImageGenerationModel($expectedResult);

        $result = AiClient::generateResult($prompt, $mockModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateResult with invalid model throws exception with model ID.
     */
    public function testGenerateResultWithInvalidModelThrowsExceptionWithModelId(): void
    {
        $prompt = 'Test prompt';
        $invalidModel = $this->createMock(ModelInterface::class);

        $mockMetadata = $this->createMock(\WordPress\AiClient\Providers\Models\DTO\ModelMetadata::class);
        $mockMetadata->expects($this->once())
            ->method('getId')
            ->willReturn('invalid-model-id');

        $invalidModel->expects($this->once())
            ->method('metadata')
            ->willReturn($mockMetadata);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model "invalid-model-id" must implement at least one supported generation interface'
        );

        AiClient::generateResult($prompt, $invalidModel);
    }

    /**
     * Tests generateResultWithCapability with null model delegates to PromptBuilder.
     */
    public function testGenerateResultWithCapabilityNullModelDelegatesToPromptBuilder(): void
    {
        $prompt = 'Test prompt';
        $capability = CapabilityEnum::textGeneration();

        // Create a mock registry that returns empty results
        $mockRegistry = $this->createMock(ProviderRegistry::class);
        $mockRegistry
            ->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->willReturn([]);

        // Set the mock registry as default
        AiClient::setDefaultRegistry($mockRegistry);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No models found that support the required capabilities');

        AiClient::generateResultWithCapability($prompt, $capability);
    }

    /**
     * Tests generateResultWithCapability with valid model and capability.
     */
    public function testGenerateResultWithCapabilityValidModelAndCapability(): void
    {
        $prompt = 'Generate text';
        $capability = CapabilityEnum::textGeneration();
        $expectedResult = $this->createTestResult();

        $customMetadata = new ModelMetadata(
            'test-text-model',
            'Test Text Model',
            [$capability],
            []
        );
        $mockModel = $this->createMockTextGenerationModel($expectedResult, $customMetadata);

        $result = AiClient::generateResultWithCapability($prompt, $capability, $mockModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateResultWithCapability with model that doesn't support capability.
     */
    public function testGenerateResultWithCapabilityUnsupportedCapability(): void
    {
        $prompt = 'Generate content';
        $capability = CapabilityEnum::imageGeneration();
        $expectedResult = $this->createTestResult();

        // Create metadata with only text generation capability
        $customMetadata = new ModelMetadata(
            'text-only-model',
            'Text Only Model',
            [CapabilityEnum::textGeneration()], // Only supports text, not image
            []
        );
        $mockModel = $this->createMockTextGenerationModel($expectedResult, $customMetadata);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model "text-only-model" does not support the "image_generation" capability'
        );

        AiClient::generateResultWithCapability($prompt, $capability, $mockModel);
    }
}

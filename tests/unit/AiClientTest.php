<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tests\mocks\MockImageGenerationModel;
use WordPress\AiClient\Tests\mocks\MockTextGenerationModel;

/**
 * @covers \WordPress\AiClient\AiClient
 */
class AiClientTest extends TestCase
{
    private ProviderRegistry $registry;
    private MockTextGenerationModel $mockTextModel;
    private MockImageGenerationModel $mockImageModel;

    protected function setUp(): void
    {
        // Create a clean registry for each test
        $this->registry = new ProviderRegistry();

        // Create mock models that implement both base and generation interfaces
        $this->mockTextModel = new MockTextGenerationModel();
        $this->mockImageModel = new MockImageGenerationModel();

        // Set the test registry as the default
        AiClient::setDefaultRegistry($this->registry);
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
        $mockResult = $this->createMock(GenerativeAiResult::class);

        $this->mockTextModel
            ->expects($this->once())
            ->method('generateTextResult')
            ->with($this->callback(function ($messages) {
                return is_array($messages) &&
                    count($messages) === 1 &&
                    $messages[0] instanceof Message &&
                    $messages[0]->getRole()->isUser();
            }))
            ->willReturn($mockResult);

        $result = AiClient::generateTextResult($prompt, $this->mockTextModel);

        $this->assertSame($mockResult, $result);
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
        $mockResult = $this->createMock(GenerativeAiResult::class);

        $this->mockImageModel
            ->expects($this->once())
            ->method('generateImageResult')
            ->with($this->callback(function ($messages) {
                return is_array($messages) &&
                    count($messages) === 1 &&
                    $messages[0] instanceof Message &&
                    $messages[0]->getRole()->isUser();
            }))
            ->willReturn($mockResult);

        $result = AiClient::generateImageResult($prompt, $this->mockImageModel);

        $this->assertSame($mockResult, $result);
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
        $mockResult = $this->createMock(GenerativeAiResult::class);

        $this->mockTextModel
            ->expects($this->once())
            ->method('generateTextResult')
            ->with($this->callback(function ($messages) use ($message) {
                return is_array($messages) &&
                    count($messages) === 1 &&
                    $messages[0] === $message;
            }))
            ->willReturn($mockResult);

        $result = AiClient::generateTextResult($message, $this->mockTextModel);

        $this->assertSame($mockResult, $result);
    }

    /**
     * Tests generateTextResult with MessagePart object.
     */
    public function testGenerateTextResultWithMessagePart(): void
    {
        $messagePart = new MessagePart('Test message part');
        $mockResult = $this->createMock(GenerativeAiResult::class);

        $this->mockTextModel
            ->expects($this->once())
            ->method('generateTextResult')
            ->with($this->callback(function ($messages) {
                return is_array($messages) &&
                    count($messages) === 1 &&
                    $messages[0] instanceof Message &&
                    $messages[0]->getRole()->isUser();
            }))
            ->willReturn($mockResult);

        $result = AiClient::generateTextResult($messagePart, $this->mockTextModel);

        $this->assertSame($mockResult, $result);
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

        $mockResult = $this->createMock(GenerativeAiResult::class);

        $this->mockTextModel
            ->expects($this->once())
            ->method('generateTextResult')
            ->with($this->callback(function ($result) use ($messages) {
                return is_array($result) &&
                    count($result) === 2 &&
                    $result[0] === $messages[0] &&
                    $result[1] === $messages[1];
            }))
            ->willReturn($mockResult);

        $result = AiClient::generateTextResult($messages, $this->mockTextModel);

        $this->assertSame($mockResult, $result);
    }

    /**
     * Tests generateTextResult with array of MessageParts.
     */
    public function testGenerateTextResultWithMessagePartArray(): void
    {
        $messagePart1 = new MessagePart('First part');
        $messagePart2 = new MessagePart('Second part');
        $messageParts = [$messagePart1, $messagePart2];

        $mockResult = $this->createMock(GenerativeAiResult::class);

        $this->mockTextModel
            ->expects($this->once())
            ->method('generateTextResult')
            ->with($this->callback(function ($messages) {
                return is_array($messages) &&
                    count($messages) === 1 &&
                    $messages[0] instanceof Message &&
                    $messages[0]->getRole()->isUser() &&
                    count($messages[0]->getParts()) === 2;
            }))
            ->willReturn($mockResult);

        $result = AiClient::generateTextResult($messageParts, $this->mockTextModel);

        $this->assertSame($mockResult, $result);
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

        $this->mockTextModel->expects($this->once())
            ->method('generateTextResult')
            ->willReturn($expectedResult);

        $result = AiClient::generateResult($prompt, $this->mockTextModel);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateResult delegates to generateImageResult when model supports image generation.
     */
    public function testGenerateResultDelegatesToImageGeneration(): void
    {
        $prompt = 'Generate image prompt';
        $expectedResult = $this->createTestResult();

        $this->mockImageModel->expects($this->once())
            ->method('generateImageResult')
            ->willReturn($expectedResult);

        $result = AiClient::generateResult($prompt, $this->mockImageModel);

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
        $mockResult = $this->createMock(GenerativeAiResult::class);

        $this->mockTextModel
            ->expects($this->once())
            ->method('generateTextResult')
            ->willReturn($mockResult);

        $result = AiClient::generateResult($prompt, $this->mockTextModel);

        $this->assertSame($mockResult, $result);
    }

    /**
     * Tests generateResult with image generation model.
     */
    public function testGenerateResultWithImageGenerationModel(): void
    {
        $prompt = 'Generate an image';
        $mockResult = $this->createMock(GenerativeAiResult::class);

        $this->mockImageModel
            ->expects($this->once())
            ->method('generateImageResult')
            ->willReturn($mockResult);

        $result = AiClient::generateResult($prompt, $this->mockImageModel);

        $this->assertSame($mockResult, $result);
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
        $mockResult = $this->createMock(GenerativeAiResult::class);

        $mockMetadata = $this->createMock(\WordPress\AiClient\Providers\Models\DTO\ModelMetadata::class);
        $mockMetadata->expects($this->once())
            ->method('getSupportedCapabilities')
            ->willReturn([$capability]);

        $this->mockTextModel
            ->expects($this->once())
            ->method('metadata')
            ->willReturn($mockMetadata);

        $this->mockTextModel
            ->expects($this->once())
            ->method('generateTextResult')
            ->willReturn($mockResult);

        $result = AiClient::generateResultWithCapability($prompt, $capability, $this->mockTextModel);

        $this->assertSame($mockResult, $result);
    }

    /**
     * Tests generateResultWithCapability with model that doesn't support capability.
     */
    public function testGenerateResultWithCapabilityUnsupportedCapability(): void
    {
        $prompt = 'Generate content';
        $capability = CapabilityEnum::imageGeneration();

        $mockMetadata = $this->createMock(\WordPress\AiClient\Providers\Models\DTO\ModelMetadata::class);
        $mockMetadata->expects($this->once())
            ->method('getSupportedCapabilities')
            ->willReturn([CapabilityEnum::textGeneration()]); // Only supports text, not image
        $mockMetadata->expects($this->once())
            ->method('getId')
            ->willReturn('text-only-model');

        $this->mockTextModel
            ->expects($this->exactly(2))
            ->method('metadata')
            ->willReturn($mockMetadata);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model "text-only-model" does not support the "image_generation" capability'
        );

        AiClient::generateResultWithCapability($prompt, $capability, $this->mockTextModel);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit;

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
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
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
        $this->mockTextModel = $this->createMock(MockTextGenerationModel::class);
        $this->mockImageModel = $this->createMock(MockImageGenerationModel::class);

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
                    $messages[0] instanceof UserMessage;
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model must implement TextGenerationModelInterface for text generation');

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
                    $messages[0] instanceof UserMessage;
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model must implement ImageGenerationModelInterface for image generation');

        AiClient::generateImageResult($prompt, $invalidModel);
    }

    /**
     * Tests generateOperation throws not implemented exception.
     */
    public function testGenerateOperationThrowsNotImplementedException(): void
    {
        $prompt = 'Generate content';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Operations are not implemented yet. This functionality is planned for a future release.'
        );

        AiClient::generateOperation($prompt, $this->mockTextModel);
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
                    $messages[0] instanceof UserMessage;
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
                    $messages[0] instanceof UserMessage &&
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model must implement at least one supported generation interface ' .
            '(TextGeneration, ImageGeneration, TextToSpeechConversion, SpeechGeneration)'
        );

        AiClient::generateResult($prompt, $unsupportedModel);
    }

    /**
     * Tests streamGenerateTextResult delegates to model's streaming method.
     */
    public function testStreamGenerateTextResultThrowsNotImplementedException(): void
    {
        $prompt = 'Stream this text';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Text streaming is not implemented yet. Use generateTextResult() for non-streaming text generation.'
        );

        iterator_to_array(AiClient::streamGenerateTextResult($prompt, $this->mockTextModel));
    }

    /**
     * Tests streamGenerateTextResult with model auto-discovery.
     */
    public function testStreamGenerateTextResultWithAutoDiscoveryThrowsNotImplementedException(): void
    {
        $prompt = 'Auto-discover and stream';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Text streaming is not implemented yet. Use generateTextResult() for non-streaming text generation.'
        );

        iterator_to_array(AiClient::streamGenerateTextResult($prompt));
    }

    /**
     * Tests streamGenerateTextResult throws exception when model doesn't support text generation.
     */
    public function testStreamGenerateTextResultForNonTextModelThrowsNotImplementedException(): void
    {
        $prompt = 'Test prompt';
        $nonTextModel = $this->createMock(ModelInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Text streaming is not implemented yet. Use generateTextResult() for non-streaming text generation.'
        );

        iterator_to_array(AiClient::streamGenerateTextResult($prompt, $nonTextModel));
    }

    /**
     * Tests generateTextOperation throws not implemented exception.
     */
    public function testGenerateTextOperationThrowsNotImplementedException(): void
    {
        $prompt = 'Text operation prompt';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Text generation operations are not implemented yet. This functionality is planned for a future release.'
        );

        AiClient::generateTextOperation($prompt, $this->mockTextModel);
    }


    /**
     * Tests generateImageOperation throws not implemented exception.
     */
    public function testGenerateImageOperationThrowsNotImplementedException(): void
    {
        $prompt = 'Image operation prompt';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Image generation operations are not implemented yet. This functionality is planned for a future release.'
        );

        AiClient::generateImageOperation($prompt, $this->mockImageModel);
    }

    /**
     * Tests convertTextToSpeechOperation throws not implemented exception.
     */
    public function testConvertTextToSpeechOperationThrowsNotImplementedException(): void
    {
        $prompt = 'Text to speech operation prompt';
        $mockModel = $this->createMock(ModelInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Text-to-speech conversion operations are not implemented yet. ' .
            'This functionality is planned for a future release.'
        );

        AiClient::convertTextToSpeechOperation($prompt, $mockModel);
    }

    /**
     * Tests generateSpeechOperation throws not implemented exception.
     */
    public function testGenerateSpeechOperationThrowsNotImplementedException(): void
    {
        $prompt = 'Speech operation prompt';
        $mockModel = $this->createMock(ModelInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Speech generation operations are not implemented yet. This functionality is planned for a future release.'
        );

        AiClient::generateSpeechOperation($prompt, $mockModel);
    }
}

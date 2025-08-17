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
use WordPress\AiClient\Operations\DTO\EmbeddingOperation;
use WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
use WordPress\AiClient\Operations\Enums\OperationStateEnum;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tests\mocks\MockEmbeddingGenerationModel;
use WordPress\AiClient\Tests\mocks\MockEmbeddingGenerationOperationModel;
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
    private MockEmbeddingGenerationModel $mockEmbeddingModel;
    private MockEmbeddingGenerationOperationModel $mockEmbeddingOperationModel;

    protected function setUp(): void
    {
        // Create a clean registry for each test
        $this->registry = new ProviderRegistry();

        // Create mock models that implement both base and generation interfaces
        $this->mockTextModel = $this->createMock(MockTextGenerationModel::class);
        $this->mockImageModel = $this->createMock(MockImageGenerationModel::class);
        $this->mockEmbeddingModel = $this->createMock(MockEmbeddingGenerationModel::class);
        $this->mockEmbeddingOperationModel = $this->createMock(MockEmbeddingGenerationOperationModel::class);

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

        return new GenerativeAiResult('test-result-id', [$candidate], $tokenUsage);
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
     * Tests prompt method throws exception when PromptBuilder is not available.
     */
    public function testPromptThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'PromptBuilder is not yet available. This method depends on PR #49. ' .
            'All generation methods (text, image, text-to-speech, speech, embeddings) are ready for integration.'
        );

        AiClient::prompt('Test prompt');
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
     * Tests generateOperation with valid model.
     */
    public function testGenerateOperation(): void
    {
        $prompt = 'Generate content';

        $operation = AiClient::generateOperation($prompt, $this->mockTextModel);

        $this->assertInstanceOf(GenerativeAiOperation::class, $operation);
        $this->assertNotEmpty($operation->getId());
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
                       count($messages) === 2 &&
                       $messages[0] instanceof UserMessage &&
                       $messages[1] instanceof UserMessage;
            }))
            ->willReturn($mockResult);

        $result = AiClient::generateTextResult($messageParts, $this->mockTextModel);

        $this->assertSame($mockResult, $result);
    }

    /**
     * Tests prompt normalization throws exception for invalid array content.
     */
    public function testNormalizePromptWithInvalidArrayContent(): void
    {
        $invalidArray = ['string', 123, new \stdClass()];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array must contain only Message or MessagePart objects');

        AiClient::generateTextResult($invalidArray, $this->mockTextModel);
    }

    /**
     * Tests prompt normalization throws exception for completely invalid input.
     */
    public function testNormalizePromptWithInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid prompt format provided');

        AiClient::generateTextResult(123, $this->mockTextModel);
    }

    /**
     * Tests automatic model discovery when no model is provided (throws RuntimeException in current implementation).
     */
    public function testAutoModelDiscoveryThrowsException(): void
    {
        $prompt = 'Generate text';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No text generation models available');

        AiClient::generateTextResult($prompt);
    }

    /**
     * Tests automatic image model discovery when no model is provided (throws RuntimeException currently).
     */
    public function testAutoImageModelDiscoveryThrowsException(): void
    {
        $prompt = 'Generate image';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No image generation models available');

        AiClient::generateImageResult($prompt);
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
    public function testStreamGenerateTextResultDelegatesToModel(): void
    {
        $prompt = 'Stream this text';
        $result1 = $this->createTestResult();
        $result2 = $this->createTestResult();

        // Create a generator that yields test results
        $generator = (function () use ($result1, $result2) {
            yield $result1;
            yield $result2;
        })();

        $this->mockTextModel->expects($this->once())
            ->method('streamGenerateTextResult')
            ->willReturn($generator);

        $streamResults = AiClient::streamGenerateTextResult($prompt, $this->mockTextModel);

        // Convert generator to array for testing
        $results = iterator_to_array($streamResults);

        $this->assertCount(2, $results);
        $this->assertSame($result1, $results[0]);
        $this->assertSame($result2, $results[1]);
    }

    /**
     * Tests streamGenerateTextResult with model auto-discovery.
     */
    public function testStreamGenerateTextResultWithAutoDiscovery(): void
    {
        $prompt = 'Auto-discover and stream';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No text generation models available');

        iterator_to_array(AiClient::streamGenerateTextResult($prompt));
    }

    /**
     * Tests streamGenerateTextResult throws exception when model doesn't support text generation.
     */
    public function testStreamGenerateTextResultThrowsExceptionForNonTextModel(): void
    {
        $prompt = 'Test prompt';
        $nonTextModel = $this->createMock(ModelInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model must implement TextGenerationModelInterface for text generation');

        iterator_to_array(AiClient::streamGenerateTextResult($prompt, $nonTextModel));
    }

    /**
     * Tests generateTextOperation creates operation with text model validation.
     */
    public function testGenerateTextOperationWithValidTextModel(): void
    {
        $prompt = 'Text operation prompt';

        $operation = AiClient::generateTextOperation($prompt, $this->mockTextModel);

        $this->assertInstanceOf(GenerativeAiOperation::class, $operation);
        $this->assertStringStartsWith('text_op_', $operation->getId());
        $this->assertEquals(OperationStateEnum::starting(), $operation->getState());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests generateTextOperation throws exception for non-text model.
     */
    public function testGenerateTextOperationThrowsExceptionForNonTextModel(): void
    {
        $prompt = 'Text operation prompt';
        $nonTextModel = $this->createMock(ModelInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model must implement TextGenerationModelInterface for text generation operations'
        );

        AiClient::generateTextOperation($prompt, $nonTextModel);
    }

    /**
     * Tests generateImageOperation creates operation with image model validation.
     */
    public function testGenerateImageOperationWithValidImageModel(): void
    {
        $prompt = 'Image operation prompt';

        $operation = AiClient::generateImageOperation($prompt, $this->mockImageModel);

        $this->assertInstanceOf(GenerativeAiOperation::class, $operation);
        $this->assertStringStartsWith('image_op_', $operation->getId());
        $this->assertEquals(OperationStateEnum::starting(), $operation->getState());
        $this->assertNull($operation->getResult());
    }

    /**
     * Tests generateImageOperation throws exception for non-image model.
     */
    public function testGenerateImageOperationThrowsExceptionForNonImageModel(): void
    {
        $prompt = 'Image operation prompt';
        $nonImageModel = $this->createMock(ModelInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model must implement ImageGenerationModelInterface for image generation operations'
        );

        AiClient::generateImageOperation($prompt, $nonImageModel);
    }

    /**
     * Tests generateEmbeddingsResult delegates to model's generateEmbeddingsResult method.
     */
    public function testGenerateEmbeddingsResultDelegatesToModel(): void
    {
        $input = ['test input text', 'another text'];
        $expectedResult = $this->createTestEmbeddingResult();

        $this->mockEmbeddingModel
            ->expects($this->once())
            ->method('generateEmbeddingsResult')
            ->willReturn($expectedResult);

        $result = AiClient::generateEmbeddingsResult($input, $this->mockEmbeddingModel);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests generateEmbeddingsResult with Message array input.
     */
    public function testGenerateEmbeddingsResultWithMessageInput(): void
    {
        $input = [new UserMessage([new MessagePart('test message')])];
        $expectedResult = $this->createTestEmbeddingResult();

        $this->mockEmbeddingModel
            ->expects($this->once())
            ->method('generateEmbeddingsResult')
            ->willReturn($expectedResult);

        $result = AiClient::generateEmbeddingsResult($input, $this->mockEmbeddingModel);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Tests generateEmbeddingsResult throws exception for non-embedding model.
     */
    public function testGenerateEmbeddingsResultThrowsExceptionForNonEmbeddingModel(): void
    {
        $input = ['test input'];
        $nonEmbeddingModel = $this->createMock(ModelInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model must implement EmbeddingGenerationModelInterface for embedding generation'
        );

        AiClient::generateEmbeddingsResult($input, $nonEmbeddingModel);
    }

    /**
     * Tests generateEmbeddingsOperation delegates to model's generateEmbeddingsOperation method.
     */
    public function testGenerateEmbeddingsOperationDelegatesToModel(): void
    {
        $input = ['test input text'];
        $expectedOperation = $this->createTestEmbeddingOperation();

        $this->mockEmbeddingOperationModel
            ->expects($this->once())
            ->method('generateEmbeddingsOperation')
            ->willReturn($expectedOperation);

        $result = AiClient::generateEmbeddingsOperation($input, $this->mockEmbeddingOperationModel);

        $this->assertEquals($expectedOperation, $result);
    }

    /**
     * Tests generateEmbeddingsOperation throws exception for non-embedding operation model.
     */
    public function testGenerateEmbeddingsOperationThrowsExceptionForNonEmbeddingOperationModel(): void
    {
        $input = ['test input'];
        $nonEmbeddingOperationModel = $this->createMock(ModelInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model must implement EmbeddingGenerationOperationModelInterface ' .
            'for embedding generation operations'
        );

        AiClient::generateEmbeddingsOperation($input, $nonEmbeddingOperationModel);
    }

    /**
     * Creates a test EmbeddingResult for testing purposes.
     */
    private function createTestEmbeddingResult(): EmbeddingResult
    {
        $embedding = new \WordPress\AiClient\Embeddings\DTO\Embedding([0.1, 0.2, 0.3]);
        $tokenUsage = new TokenUsage(5, 0, 5);

        return new EmbeddingResult(
            'test-embedding-result',
            [$embedding],
            $tokenUsage,
            ['model' => 'test-embedding-model']
        );
    }

    /**
     * Creates a test EmbeddingOperation for testing purposes.
     */
    private function createTestEmbeddingOperation(): EmbeddingOperation
    {
        return new EmbeddingOperation(
            'test-embedding-operation',
            OperationStateEnum::starting(),
            null
        );
    }
}

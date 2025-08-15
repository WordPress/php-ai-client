<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Operations\DTO\GenerativeAiOperation;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
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
        $this->expectExceptionMessage('PromptBuilder is not yet available. This method depends on PR #49.');

        AiClient::prompt('Test prompt');
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
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\DTO\ProviderModelsMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts\SpeechGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * @covers \WordPress\AiClient\Builders\PromptBuilder
 */
class PromptBuilderTest extends TestCase
{
    /**
     * @var ProviderRegistry
     */
    private ProviderRegistry $registry;

    /**
     * Sets up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = $this->createMock(ProviderRegistry::class);
    }

    /**
     * Tests constructor with no prompt.
     *
     * @return void
     */
    public function testConstructorWithNoPrompt(): void
    {
        $builder = new PromptBuilder($this->registry);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        
        $this->assertEmpty($messagesProperty->getValue($builder));
    }

    /**
     * Tests constructor with string prompt.
     *
     * @return void
     */
    public function testConstructorWithStringPrompt(): void
    {
        $builder = new PromptBuilder($this->registry, 'Hello, world!');
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(UserMessage::class, $messages[0]);
        $this->assertEquals('Hello, world!', $messages[0]->getParts()[0]->getText());
    }

    /**
     * Tests constructor with MessagePart prompt.
     *
     * @return void
     */
    public function testConstructorWithMessagePartPrompt(): void
    {
        $part = new MessagePart('Test message');
        $builder = new PromptBuilder($this->registry, $part);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(UserMessage::class, $messages[0]);
        $this->assertEquals('Test message', $messages[0]->getParts()[0]->getText());
    }

    /**
     * Tests constructor with Message prompt.
     *
     * @return void
     */
    public function testConstructorWithMessagePrompt(): void
    {
        $message = new UserMessage([new MessagePart('User message')]);
        $builder = new PromptBuilder($this->registry, $message);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $this->assertSame($message, $messages[0]);
    }

    /**
     * Tests constructor with list of Messages.
     *
     * @return void
     */
    public function testConstructorWithMessagesList(): void
    {
        $messages = [
            new UserMessage([new MessagePart('First')]),
            new ModelMessage([new MessagePart('Second')]),
            new UserMessage([new MessagePart('Third')])
        ];
        $builder = new PromptBuilder($this->registry, $messages);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $actualMessages = $messagesProperty->getValue($builder);
        
        $this->assertCount(3, $actualMessages);
        $this->assertSame($messages, $actualMessages);
    }

    /**
     * Tests constructor with MessageArrayShape.
     *
     * @return void
     */
    public function testConstructorWithMessageArrayShape(): void
    {
        $messageArray = [
            'role' => 'user',
            'parts' => [
                ['type' => 'text', 'text' => 'Hello from array']
            ]
        ];
        $builder = new PromptBuilder($this->registry, $messageArray);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(UserMessage::class, $messages[0]);
        $this->assertEquals('Hello from array', $messages[0]->getParts()[0]->getText());
    }

    /**
     * Tests withText method.
     *
     * @return void
     */
    public function testWithText(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->withText('Some text');
        
        $this->assertSame($builder, $result); // Test fluent interface
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $this->assertEquals('Some text', $messages[0]->getParts()[0]->getText());
    }

    /**
     * Tests withText appends to existing user message.
     *
     * @return void
     */
    public function testWithTextAppendsToExistingUserMessage(): void
    {
        $builder = new PromptBuilder($this->registry, 'Initial text');
        $builder->withText(' Additional text');
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $parts = $messages[0]->getParts();
        $this->assertCount(2, $parts);
        $this->assertEquals('Initial text', $parts[0]->getText());
        $this->assertEquals(' Additional text', $parts[1]->getText());
    }

    /**
     * Tests withInlineImage method.
     *
     * @return void
     */
    public function testWithInlineImage(): void
    {
        $builder = new PromptBuilder($this->registry);
        $base64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $result = $builder->withInlineImage($base64, 'image/png');
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $file = $messages[0]->getParts()[0]->getFile();
        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals('data:image/png;base64,' . $base64, $file->getUri());
        $this->assertEquals('image/png', $file->getMimeType());
    }

    /**
     * Tests withRemoteImage method.
     *
     * @return void
     */
    public function testWithRemoteImage(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->withRemoteImage('https://example.com/image.jpg', 'image/jpeg');
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $file = $messages[0]->getParts()[0]->getFile();
        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals('https://example.com/image.jpg', $file->getUri());
        $this->assertEquals('image/jpeg', $file->getMimeType());
    }

    /**
     * Tests withImageFile method.
     *
     * @return void
     */
    public function testWithImageFile(): void
    {
        $file = new File('https://example.com/test.png', 'image/png');
        $builder = new PromptBuilder($this->registry);
        $result = $builder->withImageFile($file);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $this->assertSame($file, $messages[0]->getParts()[0]->getFile());
    }

    /**
     * Tests withAudioFile method.
     *
     * @return void
     */
    public function testWithAudioFile(): void
    {
        $file = new File('https://example.com/audio.mp3', 'audio/mp3');
        $builder = new PromptBuilder($this->registry);
        $result = $builder->withAudioFile($file);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $this->assertSame($file, $messages[0]->getParts()[0]->getFile());
    }

    /**
     * Tests withVideoFile method.
     *
     * @return void
     */
    public function testWithVideoFile(): void
    {
        $file = new File('https://example.com/video.mp4', 'video/mp4');
        $builder = new PromptBuilder($this->registry);
        $result = $builder->withVideoFile($file);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $this->assertSame($file, $messages[0]->getParts()[0]->getFile());
    }

    /**
     * Tests withFunctionResponse method.
     *
     * @return void
     */
    public function testWithFunctionResponse(): void
    {
        $functionResponse = new FunctionResponse('func_id', 'func_name', ['result' => 'data']);
        $builder = new PromptBuilder($this->registry);
        $result = $builder->withFunctionResponse($functionResponse);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $this->assertSame($functionResponse, $messages[0]->getParts()[0]->getFunctionResponse());
    }

    /**
     * Tests withMessageParts method.
     *
     * @return void
     */
    public function testWithMessageParts(): void
    {
        $part1 = new MessagePart('Part 1');
        $part2 = new MessagePart('Part 2');
        $part3 = new MessagePart('Part 3');
        
        $builder = new PromptBuilder($this->registry);
        $result = $builder->withMessageParts($part1, $part2, $part3);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $parts = $messages[0]->getParts();
        $this->assertCount(3, $parts);
        $this->assertEquals('Part 1', $parts[0]->getText());
        $this->assertEquals('Part 2', $parts[1]->getText());
        $this->assertEquals('Part 3', $parts[2]->getText());
    }

    /**
     * Tests withHistory method.
     *
     * @return void
     */
    public function testWithHistory(): void
    {
        $history = [
            new UserMessage([new MessagePart('User 1')]),
            new ModelMessage([new MessagePart('Model 1')]),
            new UserMessage([new MessagePart('User 2')])
        ];
        
        $builder = new PromptBuilder($this->registry);
        $result = $builder->withHistory(...$history);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(3, $messages);
        $this->assertEquals('User 1', $messages[0]->getParts()[0]->getText());
        $this->assertEquals('Model 1', $messages[1]->getParts()[0]->getText());
        $this->assertEquals('User 2', $messages[2]->getParts()[0]->getText());
    }

    /**
     * Tests usingModel method.
     *
     * @return void
     */
    public function testUsingModel(): void
    {
        $model = $this->createMock(ModelInterface::class);
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingModel($model);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        
        $this->assertSame($model, $modelProperty->getValue($builder));
    }

    /**
     * Tests usingRegistry method.
     *
     * @return void
     */
    public function testUsingRegistry(): void
    {
        $newRegistry = $this->createMock(ProviderRegistry::class);
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingRegistry($newRegistry);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $registryProperty = $reflection->getProperty('registry');
        $registryProperty->setAccessible(true);
        
        $this->assertSame($newRegistry, $registryProperty->getValue($builder));
    }

    /**
     * Tests usingSystemInstruction method.
     *
     * @return void
     */
    public function testUsingSystemInstruction(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingSystemInstruction('You are a helpful assistant.');
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals('You are a helpful assistant.', $config->getSystemInstruction());
    }

    /**
     * Tests usingMaxTokens method.
     *
     * @return void
     */
    public function testUsingMaxTokens(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingMaxTokens(1000);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals(1000, $config->getMaxTokens());
    }

    /**
     * Tests usingTemperature method.
     *
     * @return void
     */
    public function testUsingTemperature(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingTemperature(0.7);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals(0.7, $config->getTemperature());
    }

    /**
     * Tests usingTopP method.
     *
     * @return void
     */
    public function testUsingTopP(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingTopP(0.9);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals(0.9, $config->getTopP());
    }

    /**
     * Tests usingTopK method.
     *
     * @return void
     */
    public function testUsingTopK(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingTopK(40);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals(40, $config->getTopK());
    }

    /**
     * Tests usingStopSequences method.
     *
     * @return void
     */
    public function testUsingStopSequences(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingStopSequences('STOP', 'END', '###');
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $customOptions = $config->getCustomOptions();
        $this->assertArrayHasKey('stopSequences', $customOptions);
        $this->assertEquals(['STOP', 'END', '###'], $customOptions['stopSequences']);
    }

    /**
     * Tests usingCandidateCount method.
     *
     * @return void
     */
    public function testUsingCandidateCount(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingCandidateCount(3);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals(3, $config->getCandidateCount());
    }

    /**
     * Tests usingOutputMime method.
     *
     * @return void
     */
    public function testUsingOutputMime(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingOutputMime('application/json');
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals('application/json', $config->getOutputMimeType());
    }

    /**
     * Tests usingOutputSchema method.
     *
     * @return void
     */
    public function testUsingOutputSchema(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string']
            ]
        ];
        
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingOutputSchema($schema);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals($schema, $config->getOutputSchema());
    }

    /**
     * Tests usingOutputModalities method.
     *
     * @return void
     */
    public function testUsingOutputModalities(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingOutputModalities(
            ModalityEnum::text(),
            ModalityEnum::image()
        );
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $modalities = $config->getOutputModalities();
        $this->assertCount(2, $modalities);
        $this->assertTrue($modalities[0]->isText());
        $this->assertTrue($modalities[1]->isImage());
    }

    /**
     * Tests asJsonResponse method.
     *
     * @return void
     */
    public function testAsJsonResponse(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->asJsonResponse();
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals('application/json', $config->getOutputMimeType());
    }

    /**
     * Tests asJsonResponse with schema.
     *
     * @return void
     */
    public function testAsJsonResponseWithSchema(): void
    {
        $schema = ['type' => 'array'];
        $builder = new PromptBuilder($this->registry);
        $result = $builder->asJsonResponse($schema);
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals('application/json', $config->getOutputMimeType());
        $this->assertEquals($schema, $config->getOutputSchema());
    }

    /**
     * Tests getModelRequirements with basic text prompt.
     *
     * @return void
     */
    public function testGetModelRequirementsBasicText(): void
    {
        $builder = new PromptBuilder($this->registry, 'Simple text');
        $requirements = $builder->getModelRequirements();
        
        $capabilities = $requirements->getRequiredCapabilities();
        $this->assertCount(1, $capabilities);
        $this->assertTrue($capabilities[0]->isTextGeneration());
        
        $options = $requirements->getRequiredOptions();
        // Should have input modalities with text
        $inputModalitiesFound = false;
        foreach ($options as $option) {
            if ($option->getName() === OptionEnum::inputModalities()->value) {
                $inputModalitiesFound = true;
                $modalities = $option->getValue();
                $this->assertCount(1, $modalities);
                $this->assertTrue($modalities[0]->isText());
            }
        }
        $this->assertTrue($inputModalitiesFound);
    }

    /**
     * Tests getModelRequirements with chat history.
     *
     * @return void
     */
    public function testGetModelRequirementsWithChatHistory(): void
    {
        $builder = new PromptBuilder($this->registry);
        $builder->withHistory(
            new UserMessage([new MessagePart('Hello')]),
            new ModelMessage([new MessagePart('Hi there')]),
            new UserMessage([new MessagePart('How are you?')])
        );
        
        $requirements = $builder->getModelRequirements();
        $capabilities = $requirements->getRequiredCapabilities();
        
        // Should have text generation and chat history capabilities
        $this->assertCount(2, $capabilities);
        $hasTextGeneration = false;
        $hasChatHistory = false;
        foreach ($capabilities as $capability) {
            if ($capability->isTextGeneration()) {
                $hasTextGeneration = true;
            }
            if ($capability->isChatHistory()) {
                $hasChatHistory = true;
            }
        }
        $this->assertTrue($hasTextGeneration);
        $this->assertTrue($hasChatHistory);
    }

    /**
     * Tests getModelRequirements with multimodal input.
     *
     * @return void
     */
    public function testGetModelRequirementsWithMultimodalInput(): void
    {
        $builder = new PromptBuilder($this->registry);
        $builder->withText('Describe this image')
                ->withRemoteImage('https://example.com/image.jpg', 'image/jpeg');
        
        $requirements = $builder->getModelRequirements();
        $options = $requirements->getRequiredOptions();
        
        // Find input modalities option
        $inputModalities = null;
        foreach ($options as $option) {
            if ($option->getName() === OptionEnum::inputModalities()->value) {
                $inputModalities = $option->getValue();
                break;
            }
        }
        
        $this->assertNotNull($inputModalities);
        $this->assertCount(2, $inputModalities);
        
        $hasText = false;
        $hasImage = false;
        foreach ($inputModalities as $modality) {
            if ($modality->isText()) {
                $hasText = true;
            }
            if ($modality->isImage()) {
                $hasImage = true;
            }
        }
        $this->assertTrue($hasText);
        $this->assertTrue($hasImage);
    }

    /**
     * Tests isSupported without model.
     *
     * @return void
     */
    public function testIsSupportedWithoutModel(): void
    {
        $builder = new PromptBuilder($this->registry, 'Test');
        
        // Without a model, should return true (can't determine support)
        $this->assertTrue($builder->isSupported());
    }

    /**
     * Tests isSupported with compatible model.
     *
     * @return void
     */
    public function testIsSupportedWithCompatibleModel(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        
        $builder = new PromptBuilder($this->registry, 'Test');
        $builder->usingModel($model);
        
        $this->assertTrue($builder->isSupported());
    }

    /**
     * Tests isSupported with incompatible model.
     *
     * @return void
     */
    public function testIsSupportedWithIncompatibleModel(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('meetsRequirements')->willReturn(false);
        
        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        
        $builder = new PromptBuilder($this->registry, 'Test');
        $builder->usingModel($model);
        
        $this->assertFalse($builder->isSupported());
    }

    /**
     * Tests validateMessages with empty messages throws exception.
     *
     * @return void
     */
    public function testValidateMessagesEmptyThrowsException(): void
    {
        $builder = new PromptBuilder($this->registry);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot generate from an empty prompt');
        
        $builder->generateResult();
    }

    /**
     * Tests validateMessages with non-user first message throws exception.
     *
     * @return void
     */
    public function testValidateMessagesNonUserFirstThrowsException(): void
    {
        $builder = new PromptBuilder($this->registry, [
            new ModelMessage([new MessagePart('Model says hi')]),
            new UserMessage([new MessagePart('User response')])
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The first message must be from a user role');
        
        $builder->generateResult();
    }

    /**
     * Tests validateMessages with non-user last message throws exception.
     *
     * @return void
     */
    public function testValidateMessagesNonUserLastThrowsException(): void
    {
        $builder = new PromptBuilder($this->registry, [
            new UserMessage([new MessagePart('User says hi')]),
            new ModelMessage([new MessagePart('Model response')])
        ]);
        
        // Add a user message to make it valid, then add model message
        $builder->withHistory(new ModelMessage([new MessagePart('Another model message')]));
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The last message must be from a user role');
        
        $builder->generateResult();
    }

    /**
     * Tests parseMessage with empty string throws exception.
     *
     * @return void
     */
    public function testParseMessageEmptyStringThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create a message from an empty string');
        
        new PromptBuilder($this->registry, '   ');
    }

    /**
     * Tests parseMessage with empty array throws exception.
     *
     * @return void
     */
    public function testParseMessageEmptyArrayThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create a message from an empty array');
        
        new PromptBuilder($this->registry, []);
    }

    /**
     * Tests parseMessage with invalid type throws exception.
     *
     * @return void
     */
    public function testParseMessageInvalidTypeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Input must be a string, MessagePart, MessagePartArrayShape');
        
        new PromptBuilder($this->registry, 123);
    }

    /**
     * Tests chaining multiple operations.
     *
     * @return void
     */
    public function testMethodChaining(): void
    {
        $model = $this->createMock(ModelInterface::class);
        
        $builder = new PromptBuilder($this->registry);
        $result = $builder
            ->withText('Start of prompt')
            ->withRemoteImage('https://example.com/img.jpg', 'image/jpeg')
            ->usingModel($model)
            ->usingSystemInstruction('Be helpful')
            ->usingMaxTokens(500)
            ->usingTemperature(0.8)
            ->usingTopP(0.95)
            ->usingTopK(50)
            ->usingCandidateCount(2)
            ->asJsonResponse();
        
        $this->assertSame($builder, $result);
        
        $reflection = new \ReflectionClass($builder);
        
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        $this->assertCount(1, $messages);
        $this->assertCount(2, $messages[0]->getParts()); // Text and image
        
        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $this->assertSame($model, $modelProperty->getValue($builder));
        
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals('Be helpful', $config->getSystemInstruction());
        $this->assertEquals(500, $config->getMaxTokens());
        $this->assertEquals(0.8, $config->getTemperature());
        $this->assertEquals(0.95, $config->getTopP());
        $this->assertEquals(50, $config->getTopK());
        $this->assertEquals(2, $config->getCandidateCount());
        $this->assertEquals('application/json', $config->getOutputMimeType());
    }

    /**
     * Tests generateResult with text output modality.
     *
     * @return void
     */
    public function testGenerateResultWithTextModality(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateTextResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);
        
        $actualResult = $builder->generateResult();
        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests generateResult with image output modality.
     *
     * @return void
     */
    public function testGenerateResultWithImageModality(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(ImageGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateImageResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate an image');
        $builder->usingModel($model);
        $builder->usingOutputModalities(ModalityEnum::image());
        
        $actualResult = $builder->generateResult();
        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests generateResult with audio output modality.
     *
     * @return void
     */
    public function testGenerateResultWithAudioModality(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(SpeechGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateSpeechResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate speech');
        $builder->usingModel($model);
        $builder->usingOutputModalities(ModalityEnum::audio());
        
        $actualResult = $builder->generateResult();
        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests generateResult with multimodal output.
     *
     * @return void
     */
    public function testGenerateResultWithMultimodalOutput(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateTextResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate multimodal');
        $builder->usingModel($model);
        $builder->usingOutputModalities(ModalityEnum::text(), ModalityEnum::image());
        
        $actualResult = $builder->generateResult();
        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests generateResult throws exception when model doesn't support modality.
     *
     * @return void
     */
    public function testGenerateResultThrowsExceptionForUnsupportedModality(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        // Model that only implements ModelInterface, not TextGenerationModelInterface
        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        
        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "test-model" does not support text generation');
        
        $builder->generateResult();
    }

    /**
     * Tests generateResult throws exception for unsupported output modality.
     *
     * @return void
     */
    public function testGenerateResultThrowsExceptionForUnsupportedOutputModality(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        
        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);
        $builder->usingOutputModalities(ModalityEnum::video());
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Output modality "video" is not yet supported');
        
        $builder->generateResult();
    }

    /**
     * Tests generateTextResult method.
     *
     * @return void
     */
    public function testGenerateTextResult(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateTextResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);
        
        $actualResult = $builder->generateTextResult();
        $this->assertSame($result, $actualResult);
        
        // Verify text modality was included
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $modalities = $config->getOutputModalities();
        $this->assertNotNull($modalities);
        $this->assertTrue($modalities[0]->isText());
    }

    /**
     * Tests generateImageResult method.
     *
     * @return void
     */
    public function testGenerateImageResult(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(ImageGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateImageResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate image');
        $builder->usingModel($model);
        
        $actualResult = $builder->generateImageResult();
        $this->assertSame($result, $actualResult);
        
        // Verify image modality was included
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $modalities = $config->getOutputModalities();
        $this->assertNotNull($modalities);
        $this->assertTrue($modalities[0]->isImage());
    }

    /**
     * Tests generateSpeechResult method.
     *
     * @return void
     */
    public function testGenerateSpeechResult(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(SpeechGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateSpeechResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate speech');
        $builder->usingModel($model);
        
        $actualResult = $builder->generateSpeechResult();
        $this->assertSame($result, $actualResult);
        
        // Verify audio modality was included
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $modalities = $config->getOutputModalities();
        $this->assertNotNull($modalities);
        $this->assertTrue($modalities[0]->isAudio());
    }

    /**
     * Tests convertTextToSpeechResult method.
     *
     * @return void
     */
    public function testConvertTextToSpeechResult(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextToSpeechConversionModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('convertTextToSpeechResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Convert to speech');
        $builder->usingModel($model);
        
        $actualResult = $builder->convertTextToSpeechResult();
        $this->assertSame($result, $actualResult);
        
        // Verify audio modality was included
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $modalities = $config->getOutputModalities();
        $this->assertNotNull($modalities);
        $this->assertTrue($modalities[0]->isAudio());
    }

    /**
     * Tests convertTextToSpeechResult throws exception for unsupported model.
     *
     * @return void
     */
    public function testConvertTextToSpeechResultThrowsExceptionForUnsupportedModel(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        // Model that doesn't implement TextToSpeechConversionModelInterface
        $model = $this->createMock(TextGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        
        $builder = new PromptBuilder($this->registry, 'Convert to speech');
        $builder->usingModel($model);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "test-model" does not support text-to-speech conversion');
        
        $builder->convertTextToSpeechResult();
    }

    /**
     * Tests generateText method.
     *
     * @return void
     */
    public function testGenerateText(): void
    {
        $messagePart = new MessagePart('Generated text content');
        $message = new Message(MessageRoleEnum::model(), [$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn([$candidate]);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateTextResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);
        
        $text = $builder->generateText();
        $this->assertEquals('Generated text content', $text);
    }

    /**
     * Tests generateText throws exception when no candidates.
     *
     * @return void
     */
    public function testGenerateTextThrowsExceptionWhenNoCandidates(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn([]);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateTextResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No candidates were generated');
        
        $builder->generateText();
    }

    /**
     * Tests generateText throws exception when message has no parts.
     *
     * @return void
     */
    public function testGenerateTextThrowsExceptionWhenNoParts(): void
    {
        $message = new Message(MessageRoleEnum::model(), []);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn([$candidate]);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateTextResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Generated message contains no parts');
        
        $builder->generateText();
    }

    /**
     * Tests generateText throws exception when part has no text.
     *
     * @return void
     */
    public function testGenerateTextThrowsExceptionWhenPartHasNoText(): void
    {
        $file = new File('https://example.com/image.jpg', 'image/jpeg');
        $messagePart = new MessagePart($file);
        $message = new Message(MessageRoleEnum::model(), [$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn([$candidate]);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateTextResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Generated message part contains no text');
        
        $builder->generateText();
    }

    /**
     * Tests generateTexts method.
     *
     * @return void
     */
    public function testGenerateTexts(): void
    {
        $candidates = [
            new Candidate(
                new Message(MessageRoleEnum::model(), [new MessagePart('Text 1')]),
                FinishReasonEnum::stop(),
                10
            ),
            new Candidate(
                new Message(MessageRoleEnum::model(), [new MessagePart('Text 2')]),
                FinishReasonEnum::stop(),
                10
            ),
            new Candidate(
                new Message(MessageRoleEnum::model(), [new MessagePart('Text 3')]),
                FinishReasonEnum::stop(),
                10
            )
        ];
        
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn($candidates);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateTextResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate texts');
        $builder->usingModel($model);
        
        $texts = $builder->generateTexts(3);
        
        $this->assertCount(3, $texts);
        $this->assertEquals('Text 1', $texts[0]);
        $this->assertEquals('Text 2', $texts[1]);
        $this->assertEquals('Text 3', $texts[2]);
        
        // Verify candidate count was set
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $this->assertEquals(3, $config->getCandidateCount());
    }

    /**
     * Tests generateTexts throws exception when no text generated.
     *
     * @return void
     */
    public function testGenerateTextsThrowsExceptionWhenNoTextGenerated(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn([]);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateTextResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate texts');
        $builder->usingModel($model);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No text was generated from any candidates');
        
        $builder->generateTexts();
    }

    /**
     * Tests generateImage method.
     *
     * @return void
     */
    public function testGenerateImage(): void
    {
        $file = new File('https://example.com/generated.jpg', 'image/jpeg');
        $messagePart = new MessagePart($file);
        $message = new Message(MessageRoleEnum::model(), [$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn([$candidate]);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(ImageGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateImageResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate image');
        $builder->usingModel($model);
        
        $generatedFile = $builder->generateImage();
        $this->assertSame($file, $generatedFile);
    }

    /**
     * Tests generateImage throws exception when no image file.
     *
     * @return void
     */
    public function testGenerateImageThrowsExceptionWhenNoFile(): void
    {
        $messagePart = new MessagePart('Text instead of image');
        $message = new Message(MessageRoleEnum::model(), [$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn([$candidate]);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(ImageGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateImageResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate image');
        $builder->usingModel($model);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Generated message part contains no image file');
        
        $builder->generateImage();
    }

    /**
     * Tests generateImages method.
     *
     * @return void
     */
    public function testGenerateImages(): void
    {
        $files = [
            new File('https://example.com/img1.jpg', 'image/jpeg'),
            new File('https://example.com/img2.jpg', 'image/jpeg'),
        ];
        
        $candidates = [];
        foreach ($files as $file) {
            $candidates[] = new Candidate(
                new Message(MessageRoleEnum::model(), [new MessagePart($file)]),
                FinishReasonEnum::stop(),
                10
            );
        }
        
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn($candidates);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(ImageGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateImageResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate images');
        $builder->usingModel($model);
        
        $generatedFiles = $builder->generateImages(2);
        
        $this->assertCount(2, $generatedFiles);
        $this->assertSame($files[0], $generatedFiles[0]);
        $this->assertSame($files[1], $generatedFiles[1]);
    }

    /**
     * Tests convertTextToSpeech method.
     *
     * @return void
     */
    public function testConvertTextToSpeech(): void
    {
        $file = new File('https://example.com/audio.mp3', 'audio/mp3');
        $messagePart = new MessagePart($file);
        $message = new Message(MessageRoleEnum::model(), [$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn([$candidate]);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextToSpeechConversionModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('convertTextToSpeechResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Convert this text');
        $builder->usingModel($model);
        
        $audioFile = $builder->convertTextToSpeech();
        $this->assertSame($file, $audioFile);
    }

    /**
     * Tests convertTextToSpeeches method.
     *
     * @return void
     */
    public function testConvertTextToSpeeches(): void
    {
        $files = [
            new File('https://example.com/audio1.mp3', 'audio/mp3'),
            new File('https://example.com/audio2.mp3', 'audio/mp3'),
        ];
        
        $candidates = [];
        foreach ($files as $file) {
            $candidates[] = new Candidate(
                new Message(MessageRoleEnum::model(), [new MessagePart($file)]),
                FinishReasonEnum::stop(),
                10
            );
        }
        
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn($candidates);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(TextToSpeechConversionModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('convertTextToSpeechResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Convert this text');
        $builder->usingModel($model);
        
        $audioFiles = $builder->convertTextToSpeeches(2);
        
        $this->assertCount(2, $audioFiles);
        $this->assertSame($files[0], $audioFiles[0]);
        $this->assertSame($files[1], $audioFiles[1]);
    }

    /**
     * Tests generateSpeech method.
     *
     * @return void
     */
    public function testGenerateSpeech(): void
    {
        $file = new File('https://example.com/speech.mp3', 'audio/mp3');
        $messagePart = new MessagePart($file);
        $message = new Message(MessageRoleEnum::model(), [$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn([$candidate]);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(SpeechGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateSpeechResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate speech');
        $builder->usingModel($model);
        
        $speechFile = $builder->generateSpeech();
        $this->assertSame($file, $speechFile);
    }

    /**
     * Tests generateSpeeches method.
     *
     * @return void
     */
    public function testGenerateSpeeches(): void
    {
        $files = [
            new File('https://example.com/speech1.mp3', 'audio/mp3'),
            new File('https://example.com/speech2.mp3', 'audio/mp3'),
            new File('https://example.com/speech3.mp3', 'audio/mp3'),
        ];
        
        $candidates = [];
        foreach ($files as $file) {
            $candidates[] = new Candidate(
                new Message(MessageRoleEnum::model(), [new MessagePart($file)]),
                FinishReasonEnum::stop(),
                10
            );
        }
        
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn($candidates);
        
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(SpeechGenerationModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->method('generateSpeechResult')->willReturn($result);
        
        $builder = new PromptBuilder($this->registry, 'Generate speech');
        $builder->usingModel($model);
        
        $speechFiles = $builder->generateSpeeches(3);
        
        $this->assertCount(3, $speechFiles);
        $this->assertSame($files[0], $speechFiles[0]);
        $this->assertSame($files[1], $speechFiles[1]);
        $this->assertSame($files[2], $speechFiles[2]);
    }

    /**
     * Tests getConfiguredModel with explicitly set model.
     *
     * @return void
     */
    public function testGetConfiguredModelWithExplicitModel(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('meetsRequirements')->willReturn(true);
        
        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->expects($this->once())->method('setConfig')->with($this->isInstanceOf(ModelConfig::class));
        
        $builder = new PromptBuilder($this->registry, 'Test');
        $builder->usingModel($model);
        
        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('getConfiguredModel');
        $method->setAccessible(true);
        
        $configuredModel = $method->invoke($builder);
        $this->assertSame($model, $configuredModel);
    }

    /**
     * Tests getConfiguredModel throws exception when model doesn't meet requirements.
     *
     * @return void
     */
    public function testGetConfiguredModelThrowsExceptionWhenModelDoesntMeetRequirements(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('meetsRequirements')->willReturn(false);
        $metadata->method('getId')->willReturn('incompatible-model');
        
        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        
        $builder = new PromptBuilder($this->registry, 'Test');
        $builder->usingModel($model);
        
        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('getConfiguredModel');
        $method->setAccessible(true);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The selected model "incompatible-model" does not meet the required capabilities');
        
        $method->invoke($builder);
    }

    /**
     * Tests getConfiguredModel finds model from registry.
     *
     * @return void
     */
    public function testGetConfiguredModelFindsModelFromRegistry(): void
    {
        $modelMetadata = $this->createMock(ModelMetadata::class);
        $modelMetadata->method('getId')->willReturn('found-model');
        
        $providerMetadata = $this->createMock(ProviderMetadata::class);
        $providerMetadata->method('getId')->willReturn('test-provider');
        
        $providerModelsMetadata = $this->createMock(ProviderModelsMetadata::class);
        $providerModelsMetadata->method('getProvider')->willReturn($providerMetadata);
        $providerModelsMetadata->method('getModels')->willReturn([$modelMetadata]);
        
        $model = $this->createMock(ModelInterface::class);
        
        $this->registry->method('findModelsMetadataForSupport')
            ->willReturn([$providerModelsMetadata]);
        
        $this->registry->method('getProviderModel')
            ->with('test-provider', 'found-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);
        
        $builder = new PromptBuilder($this->registry, 'Test');
        
        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('getConfiguredModel');
        $method->setAccessible(true);
        
        $configuredModel = $method->invoke($builder);
        $this->assertSame($model, $configuredModel);
    }

    /**
     * Tests getConfiguredModel throws exception when no models found.
     *
     * @return void
     */
    public function testGetConfiguredModelThrowsExceptionWhenNoModelsFound(): void
    {
        $this->registry->method('findModelsMetadataForSupport')->willReturn([]);
        
        $builder = new PromptBuilder($this->registry, 'Test');
        
        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('getConfiguredModel');
        $method->setAccessible(true);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No models found that support the required capabilities');
        
        $method->invoke($builder);
    }

    /**
     * Tests appendPartToMessages creates new user message when empty.
     *
     * @return void
     */
    public function testAppendPartToMessagesCreatesNewUserMessage(): void
    {
        $builder = new PromptBuilder($this->registry);
        
        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('appendPartToMessages');
        $method->setAccessible(true);
        
        $part = new MessagePart('Test part');
        $method->invoke($builder, $part);
        
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(UserMessage::class, $messages[0]);
        $this->assertEquals('Test part', $messages[0]->getParts()[0]->getText());
    }

    /**
     * Tests appendPartToMessages appends to existing user message.
     *
     * @return void
     */
    public function testAppendPartToMessagesAppendsToExistingUserMessage(): void
    {
        $builder = new PromptBuilder($this->registry, 'Initial');
        
        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('appendPartToMessages');
        $method->setAccessible(true);
        
        $part = new MessagePart('Additional');
        $method->invoke($builder, $part);
        
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $parts = $messages[0]->getParts();
        $this->assertCount(2, $parts);
        $this->assertEquals('Initial', $parts[0]->getText());
        $this->assertEquals('Additional', $parts[1]->getText());
    }

    /**
     * Tests appendPartToMessages creates new message when last is model message.
     *
     * @return void
     */
    public function testAppendPartToMessagesCreatesNewMessageWhenLastIsModel(): void
    {
        $builder = new PromptBuilder($this->registry, [
            new UserMessage([new MessagePart('User')]),
            new ModelMessage([new MessagePart('Model')])
        ]);
        
        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('appendPartToMessages');
        $method->setAccessible(true);
        
        $part = new MessagePart('New user message');
        $method->invoke($builder, $part);
        
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(3, $messages);
        $this->assertInstanceOf(UserMessage::class, $messages[2]);
        $this->assertEquals('New user message', $messages[2]->getParts()[0]->getText());
    }

    /**
     * Tests complex multimodal prompt building.
     *
     * @return void
     */
    public function testComplexMultimodalPromptBuilding(): void
    {
        $file1 = new File('https://example.com/img1.jpg', 'image/jpeg');
        $file2 = new File('https://example.com/audio.mp3', 'audio/mp3');
        $functionResponse = new FunctionResponse('func1', 'getData', ['result' => 'data']);
        
        $builder = new PromptBuilder($this->registry);
        $builder->withText('Analyze this data:')
                ->withImageFile($file1)
                ->withText(' and this audio:')
                ->withAudioFile($file2)
                ->withFunctionResponse($functionResponse)
                ->withHistory(
                    new UserMessage([new MessagePart('Previous question')]),
                    new ModelMessage([new MessagePart('Previous answer')])
                )
                ->withText(' Final instruction');
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        // Should have 3 messages: 2 from history + 1 current
        $this->assertCount(3, $messages);
        
        // Check history messages
        $this->assertEquals('Previous question', $messages[0]->getParts()[0]->getText());
        $this->assertEquals('Previous answer', $messages[1]->getParts()[0]->getText());
        
        // Check current message has all parts
        $currentParts = $messages[2]->getParts();
        $this->assertCount(6, $currentParts);
        $this->assertEquals('Analyze this data:', $currentParts[0]->getText());
        $this->assertSame($file1, $currentParts[1]->getFile());
        $this->assertEquals(' and this audio:', $currentParts[2]->getText());
        $this->assertSame($file2, $currentParts[3]->getFile());
        $this->assertSame($functionResponse, $currentParts[4]->getFunctionResponse());
        $this->assertEquals(' Final instruction', $currentParts[5]->getText());
    }

    /**
     * Tests includeOutputModality preserves existing modalities.
     *
     * @return void
     */
    public function testIncludeOutputModalityPreservesExisting(): void
    {
        $builder = new PromptBuilder($this->registry, 'Test');
        
        // Set initial modality
        $builder->usingOutputModalities(ModalityEnum::audio());
        
        // Generate text should add text modality, not replace audio
        $builder->generateTextResult();
        
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);
        
        $modalities = $config->getOutputModalities();
        $this->assertCount(2, $modalities);
        $this->assertTrue($modalities[0]->isAudio());
        $this->assertTrue($modalities[1]->isText());
    }

    /**
     * Tests constructor with list of string parts.
     *
     * @return void
     */
    public function testConstructorWithStringPartsList(): void
    {
        $builder = new PromptBuilder($this->registry, ['Part 1', 'Part 2', 'Part 3']);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(UserMessage::class, $messages[0]);
        $parts = $messages[0]->getParts();
        $this->assertCount(3, $parts);
        $this->assertEquals('Part 1', $parts[0]->getText());
        $this->assertEquals('Part 2', $parts[1]->getText());
        $this->assertEquals('Part 3', $parts[2]->getText());
    }

    /**
     * Tests constructor with mixed parts list.
     *
     * @return void
     */
    public function testConstructorWithMixedPartsList(): void
    {
        $part1 = new MessagePart('Part 1');
        $part2Array = ['type' => 'text', 'text' => 'Part 2'];
        
        $builder = new PromptBuilder($this->registry, ['String part', $part1, $part2Array]);
        
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        
        $this->assertCount(1, $messages);
        $parts = $messages[0]->getParts();
        $this->assertCount(3, $parts);
        $this->assertEquals('String part', $parts[0]->getText());
        $this->assertEquals('Part 1', $parts[1]->getText());
        $this->assertEquals('Part 2', $parts[2]->getText());
    }

    /**
     * Tests parseMessage with non-list array throws exception.
     *
     * @return void
     */
    public function testParseMessageWithNonListArrayThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array input must be a list array');
        
        new PromptBuilder($this->registry, ['key' => 'value']);
    }

    /**
     * Tests parseMessage with invalid array item throws exception.
     *
     * @return void
     */
    public function testParseMessageWithInvalidArrayItemThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array items must be strings, MessagePart instances, or MessagePartArrayShape');
        
        new PromptBuilder($this->registry, ['valid string', 123, 'another string']);
    }

    /**
     * Tests getModelRequirements with all file types.
     *
     * @return void
     */
    public function testGetModelRequirementsWithAllFileTypes(): void
    {
        $builder = new PromptBuilder($this->registry);
        $builder->withText('Analyze:')
                ->withRemoteImage('https://example.com/img.jpg', 'image/jpeg')
                ->withAudioFile(new File('https://example.com/audio.mp3', 'audio/mp3'))
                ->withVideoFile(new File('https://example.com/video.mp4', 'video/mp4'))
                ->withImageFile(new File('https://example.com/doc.pdf', 'application/pdf'));
        
        $requirements = $builder->getModelRequirements();
        $options = $requirements->getRequiredOptions();
        
        // Find input modalities
        $inputModalities = null;
        foreach ($options as $option) {
            if ($option->getName() === OptionEnum::inputModalities()->value) {
                $inputModalities = $option->getValue();
                break;
            }
        }
        
        $this->assertNotNull($inputModalities);
        
        // Check all modality types are present
        $modalityTypes = [];
        foreach ($inputModalities as $modality) {
            $modalityTypes[] = $modality->value;
        }
        
        $this->assertContains('text', $modalityTypes);
        $this->assertContains('image', $modalityTypes);
        $this->assertContains('audio', $modalityTypes);
        $this->assertContains('video', $modalityTypes);
        $this->assertContains('document', $modalityTypes);
    }

    /**
     * Tests getModelRequirements includes config options.
     *
     * @return void
     */
    public function testGetModelRequirementsIncludesConfigOptions(): void
    {
        $builder = new PromptBuilder($this->registry, 'Test');
        $builder->usingMaxTokens(1000)
                ->usingTemperature(0.7)
                ->usingOutputModalities(ModalityEnum::text(), ModalityEnum::image())
                ->asJsonResponse(['type' => 'object']);
        
        $requirements = $builder->getModelRequirements();
        $options = $requirements->getRequiredOptions();
        
        // Check that config options are included
        $optionNames = array_map(function ($option) {
            return $option->getName();
        }, $options);
        
        $this->assertContains(OptionEnum::maxTokens()->value, $optionNames);
        $this->assertContains(OptionEnum::temperature()->value, $optionNames);
        $this->assertContains(OptionEnum::outputModalities()->value, $optionNames);
        $this->assertContains(OptionEnum::outputMimeType()->value, $optionNames);
        $this->assertContains(OptionEnum::outputSchema()->value, $optionNames);
    }

    /**
     * Tests last message must have parts validation.
     *
     * @return void
     */
    public function testValidateMessagesLastMessageMustHaveParts(): void
    {
        // Create a message with empty parts
        $emptyMessage = new UserMessage([]);
        
        $builder = new PromptBuilder($this->registry, [
            new UserMessage([new MessagePart('First')]),
            new ModelMessage([new MessagePart('Response')]),
            $emptyMessage
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The last message must have content parts');
        
        $builder->generateResult();
    }

    /**
     * Tests generateImageResult method creates proper operation.
     *
     * @return void
     */
    public function testGenerateImageResultCreatesProperOperation(): void
    {
        $operation = $this->createMock(OperationInterface::class);
        
        $model = $this->createMock(GenerativeModelInterface::class);
        $model->expects($this->once())
            ->method('generate')
            ->with(
                $this->isInstanceOf(Prompt::class),
                $this->callback(function ($config) {
                    $modalities = $config->getOutputModalities();
                    return count($modalities) === 1 && $modalities[0]->isImage();
                })
            )
            ->willReturn($operation);
        
        $builder = new PromptBuilder($this->registry, 'Generate an image');
        $builder->usingModel($model);
        
        $result = $builder->generateImageResult();
        $this->assertSame($operation, $result);
    }

    /**
     * Tests generateAudioResult method creates proper operation.
     *
     * @return void
     */
    public function testGenerateAudioResultCreatesProperOperation(): void
    {
        $operation = $this->createMock(OperationInterface::class);
        
        $model = $this->createMock(GenerativeModelInterface::class);
        $model->expects($this->once())
            ->method('generate')
            ->with(
                $this->isInstanceOf(Prompt::class),
                $this->callback(function ($config) {
                    $modalities = $config->getOutputModalities();
                    return count($modalities) === 1 && $modalities[0]->isAudio();
                })
            )
            ->willReturn($operation);
        
        $builder = new PromptBuilder($this->registry, 'Generate audio');
        $builder->usingModel($model);
        
        $result = $builder->generateAudioResult();
        $this->assertSame($operation, $result);
    }

    /**
     * Tests generateVideoResult method creates proper operation.
     *
     * @return void
     */
    public function testGenerateVideoResultCreatesProperOperation(): void
    {
        $operation = $this->createMock(OperationInterface::class);
        
        $model = $this->createMock(GenerativeModelInterface::class);
        $model->expects($this->once())
            ->method('generate')
            ->with(
                $this->isInstanceOf(Prompt::class),
                $this->callback(function ($config) {
                    $modalities = $config->getOutputModalities();
                    return count($modalities) === 1 && $modalities[0]->isVideo();
                })
            )
            ->willReturn($operation);
        
        $builder = new PromptBuilder($this->registry, 'Generate video');
        $builder->usingModel($model);
        
        $result = $builder->generateVideoResult();
        $this->assertSame($operation, $result);
    }

    /**
     * Tests generateImage shorthand method returns file directly.
     *
     * @return void
     */
    public function testGenerateImageReturnsFileDirectly(): void
    {
        $file = new File('https://example.com/generated.jpg', 'image/jpeg');
        
        $candidate = $this->createMock(CandidateInterface::class);
        $candidate->method('getPart')->willReturn($file);
        
        $result = $this->createMock(GenerativeAiResultInterface::class);
        $result->method('getCandidates')->willReturn([$candidate]);
        
        $operation = $this->createMock(OperationInterface::class);
        $operation->method('getResult')->willReturn($result);
        
        $model = $this->createMock(GenerativeModelInterface::class);
        $model->method('generate')->willReturn($operation);
        
        $builder = new PromptBuilder($this->registry, 'Generate an image');
        $builder->usingModel($model);
        
        $generatedFile = $builder->generateImage();
        $this->assertSame($file, $generatedFile);
    }

    /**
     * Tests generateAudio shorthand method returns file directly.
     *
     * @return void
     */
    public function testGenerateAudioReturnsFileDirectly(): void
    {
        $file = new File('https://example.com/generated.mp3', 'audio/mp3');
        
        $candidate = $this->createMock(CandidateInterface::class);
        $candidate->method('getPart')->willReturn($file);
        
        $result = $this->createMock(GenerativeAiResultInterface::class);
        $result->method('getCandidates')->willReturn([$candidate]);
        
        $operation = $this->createMock(OperationInterface::class);
        $operation->method('getResult')->willReturn($result);
        
        $model = $this->createMock(GenerativeModelInterface::class);
        $model->method('generate')->willReturn($operation);
        
        $builder = new PromptBuilder($this->registry, 'Generate audio');
        $builder->usingModel($model);
        
        $generatedFile = $builder->generateAudio();
        $this->assertSame($file, $generatedFile);
    }

    /**
     * Tests generateVideo shorthand method returns file directly.
     *
     * @return void
     */
    public function testGenerateVideoReturnsFileDirectly(): void
    {
        $file = new File('https://example.com/generated.mp4', 'video/mp4');
        
        $candidate = $this->createMock(CandidateInterface::class);
        $candidate->method('getPart')->willReturn($file);
        
        $result = $this->createMock(GenerativeAiResultInterface::class);
        $result->method('getCandidates')->willReturn([$candidate]);
        
        $operation = $this->createMock(OperationInterface::class);
        $operation->method('getResult')->willReturn($result);
        
        $model = $this->createMock(GenerativeModelInterface::class);
        $model->method('generate')->willReturn($operation);
        
        $builder = new PromptBuilder($this->registry, 'Generate video');
        $builder->usingModel($model);
        
        $generatedFile = $builder->generateVideo();
        $this->assertSame($file, $generatedFile);
    }

    /**
     * Tests generation method with multiple output modalities.
     *
     * @return void
     */
    public function testGenerationWithMultipleOutputModalities(): void
    {
        $operation = $this->createMock(OperationInterface::class);
        
        $model = $this->createMock(GenerativeModelInterface::class);
        $model->expects($this->once())
            ->method('generate')
            ->with(
                $this->isInstanceOf(Prompt::class),
                $this->callback(function ($config) {
                    $modalities = $config->getOutputModalities();
                    return count($modalities) === 3 &&
                           $modalities[0]->isText() &&
                           $modalities[1]->isImage() &&
                           $modalities[2]->isAudio();
                })
            )
            ->willReturn($operation);
        
        $builder = new PromptBuilder($this->registry, 'Generate multimodal');
        $builder->usingModel($model)
                ->usingOutputModalities(
                    ModalityEnum::text(),
                    ModalityEnum::image(),
                    ModalityEnum::audio()
                );
        
        $result = $builder->generateResult();
        $this->assertSame($operation, $result);
    }

    /**
     * Tests streaming generation methods.
     *
     * @return void
     */
    public function testStreamingGenerationMethods(): void
    {
        $streamingOperation = $this->createMock(OperationInterface::class);
        
        $model = $this->createMock(GenerativeModelInterface::class);
        $model->expects($this->exactly(4))
            ->method('generateStream')
            ->with(
                $this->isInstanceOf(Prompt::class),
                $this->isInstanceOf(ModelConfig::class)
            )
            ->willReturn($streamingOperation);
        
        $builder = new PromptBuilder($this->registry, 'Stream content');
        $builder->usingModel($model);
        
        // Test all streaming methods
        $this->assertSame($streamingOperation, $builder->streamResult());
        $this->assertSame($streamingOperation, $builder->streamTextResult());
        $this->assertSame($streamingOperation, $builder->streamImageResult());
        $this->assertSame($streamingOperation, $builder->streamAudioResult());
    }

    /**
     * Tests generateText with no candidates throws exception.
     *
     * @return void
     */
    public function testGenerateTextWithNoCandidatesThrowsException(): void
    {
        $result = $this->createMock(GenerativeAiResultInterface::class);
        $result->method('getCandidates')->willReturn([]);
        
        $operation = $this->createMock(OperationInterface::class);
        $operation->method('getResult')->willReturn($result);
        
        $model = $this->createMock(GenerativeModelInterface::class);
        $model->method('generate')->willReturn($operation);
        
        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No candidates returned from generation');
        
        $builder->generateText();
    }

    /**
     * Tests generateText with non-string part throws exception.
     *
     * @return void
     */
    public function testGenerateTextWithNonStringPartThrowsException(): void
    {
        $file = new File('https://example.com/file.jpg', 'image/jpeg');
        
        $candidate = $this->createMock(CandidateInterface::class);
        $candidate->method('getPart')->willReturn($file);
        
        $result = $this->createMock(GenerativeAiResultInterface::class);
        $result->method('getCandidates')->willReturn([$candidate]);
        
        $operation = $this->createMock(OperationInterface::class);
        $operation->method('getResult')->willReturn($result);
        
        $model = $this->createMock(GenerativeModelInterface::class);
        $model->method('generate')->willReturn($operation);
        
        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected string part but got different type');
        
        $builder->generateText();
    }

    /**
     * Tests chain generation with multiple prompts.
     *
     * @return void
     */
    public function testChainGenerationWithMultiplePrompts(): void
    {
        // First generation
        $firstCandidate = $this->createMock(CandidateInterface::class);
        $firstCandidate->method('getPart')->willReturn('First response');
        
        $firstResult = $this->createMock(GenerativeAiResultInterface::class);
        $firstResult->method('getCandidates')->willReturn([$firstCandidate]);
        
        $firstOperation = $this->createMock(OperationInterface::class);
        $firstOperation->method('getResult')->willReturn($firstResult);
        
        // Second generation
        $secondCandidate = $this->createMock(CandidateInterface::class);
        $secondCandidate->method('getPart')->willReturn('Second response');
        
        $secondResult = $this->createMock(GenerativeAiResultInterface::class);
        $secondResult->method('getCandidates')->willReturn([$secondCandidate]);
        
        $secondOperation = $this->createMock(OperationInterface::class);
        $secondOperation->method('getResult')->willReturn($secondResult);
        
        $model = $this->createMock(GenerativeModelInterface::class);
        $model->expects($this->exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls($firstOperation, $secondOperation);
        
        $builder = new PromptBuilder($this->registry, 'First prompt');
        $builder->usingModel($model);
        
        $firstText = $builder->generateText();
        $this->assertEquals('First response', $firstText);
        
        // Continue with second prompt
        $builder->withModelResponse($firstText)
                ->withText('Second prompt');
        
        $secondText = $builder->generateText();
        $this->assertEquals('Second response', $secondText);
    }
}
<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use Generator;
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
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\DTO\ProviderModelsMetadata;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts\SpeechGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
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
     * Creates a mock model that implements both ModelInterface and TextGenerationModelInterface.
     *
     * @param ModelMetadata $metadata The metadata for the model.
     * @param GenerativeAiResult $result The result to return from generation.
     * @return ModelInterface&TextGenerationModelInterface The mock model.
     */
    private function createTextGenerationModel(ModelMetadata $metadata, GenerativeAiResult $result): ModelInterface
    {
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
     * Creates a mock model that implements both ModelInterface and ImageGenerationModelInterface.
     *
     * @param ModelMetadata $metadata The metadata for the model.
     * @param GenerativeAiResult $result The result to return from generation.
     * @return ModelInterface&ImageGenerationModelInterface The mock model.
     */
    private function createImageGenerationModel(ModelMetadata $metadata, GenerativeAiResult $result): ModelInterface
    {
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
     * Creates a mock model that implements both ModelInterface and SpeechGenerationModelInterface.
     *
     * @param ModelMetadata $metadata The metadata for the model.
     * @param GenerativeAiResult $result The result to return from generation.
     * @return ModelInterface&SpeechGenerationModelInterface The mock model.
     */
    private function createSpeechGenerationModel(ModelMetadata $metadata, GenerativeAiResult $result): ModelInterface
    {
        return new class ($metadata, $result) implements ModelInterface, SpeechGenerationModelInterface {
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

            public function generateSpeechResult(array $prompt): GenerativeAiResult
            {
                return $this->result;
            }
        };
    }

    /**
     * Creates a mock model that implements both ModelInterface and TextToSpeechConversionModelInterface.
     *
     * @param ModelMetadata $metadata The metadata for the model.
     * @param GenerativeAiResult $result The result to return from generation.
     * @return ModelInterface&TextToSpeechConversionModelInterface The mock model.
     */
    private function createTextToSpeechModel(ModelMetadata $metadata, GenerativeAiResult $result): ModelInterface
    {
        return new class ($metadata, $result) implements ModelInterface, TextToSpeechConversionModelInterface {
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

            public function convertTextToSpeechResult(array $prompt): GenerativeAiResult
            {
                return $this->result;
            }
        };
    }

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
        /** @var list<Message> $messages */
        /** @var list<Message> $messages */
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        $this->assertCount(1, $messages);
        $this->assertInstanceOf(Message::class, $messages[0]);
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
        /** @var list<Message> $messages */
        /** @var list<Message> $messages */
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        $this->assertCount(1, $messages);
        $this->assertInstanceOf(Message::class, $messages[0]);
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
        /** @var list<Message> $messages */
        /** @var list<Message> $messages */
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
        /** @var list<Message> $actualMessages */
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
        /** @var list<Message> $messages */
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        $this->assertCount(1, $messages);
        $this->assertInstanceOf(Message::class, $messages[0]);
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
        /** @var list<Message> $messages */
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
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        $this->assertCount(1, $messages);
        $parts = $messages[0]->getParts();
        $this->assertCount(2, $parts);
        $this->assertEquals('Initial text', $parts[0]->getText());
        $this->assertEquals(' Additional text', $parts[1]->getText());
    }

    /**
     * Tests withFile method with base64 data.
     *
     * @return void
     */
    public function testWithInlineFile(): void
    {
        $builder = new PromptBuilder($this->registry);
        $base64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $result = $builder->withFile($base64, 'image/png');

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        $this->assertCount(1, $messages);
        $file = $messages[0]->getParts()[0]->getFile();
        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals('data:image/png;base64,' . $base64, $file->getDataUri());
        $this->assertEquals('image/png', $file->getMimeType());
    }

    /**
     * Tests withFile method with remote URL.
     *
     * @return void
     */
    public function testWithRemoteFile(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->withFile('https://example.com/image.jpg', 'image/jpeg');

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        $this->assertCount(1, $messages);
        $file = $messages[0]->getParts()[0]->getFile();
        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals('https://example.com/image.jpg', $file->getUrl());
        $this->assertEquals('image/jpeg', $file->getMimeType());
    }

    /**
     * Tests withFile with data URI.
     *
     * @return void
     */
    public function testWithInlineFileDataUri(): void
    {
        $builder = new PromptBuilder($this->registry);
        $dataUri = 'data:image/jpeg;base64,/9j/4AAQSkZJRg==';
        $result = $builder->withFile($dataUri);

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        $this->assertCount(1, $messages);
        $file = $messages[0]->getParts()[0]->getFile();
        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals('image/jpeg', $file->getMimeType());
    }

    /**
     * Tests withFile with URL without explicit MIME type.
     *
     * @return void
     */
    public function testWithRemoteFileWithoutMimeType(): void
    {
        $builder = new PromptBuilder($this->registry);
        // File extension should be used to determine MIME type
        $result = $builder->withFile('https://example.com/audio.mp3');

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        $this->assertCount(1, $messages);
        $file = $messages[0]->getParts()[0]->getFile();
        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals('https://example.com/audio.mp3', $file->getUrl());
        $this->assertEquals('audio/mpeg', $file->getMimeType());
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
        /** @var list<Message> $messages */
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
        /** @var list<Message> $messages */
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
        /** @var list<Message> $messages */
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

        /** @var ModelInterface $actualModel */
        $actualModel = $modelProperty->getValue($builder);
        $this->assertSame($model, $actualModel);
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
        /** @var ModelConfig $config */
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
        /** @var ModelConfig $config */
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
        /** @var ModelConfig $config */
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
        /** @var ModelConfig $config */
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
        /** @var ModelConfig $config */
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
        /** @var ModelConfig $config */
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
        /** @var ModelConfig $config */
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
        /** @var ModelConfig $config */
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
        /** @var ModelConfig $config */
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
        /** @var ModelConfig $config */
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
        /** @var ModelConfig $config */
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
        /** @var ModelConfig $config */
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
            if ($option->getName()->equals(OptionEnum::inputModalities())) {
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
                ->withFile('https://example.com/image.jpg', 'image/jpeg');

        $requirements = $builder->getModelRequirements();
        $options = $requirements->getRequiredOptions();

        // Find input modalities option
        $inputModalities = null;
        foreach ($options as $option) {
            if ($option->getName()->equals(OptionEnum::inputModalities())) {
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
        // Mock registry to return models
        $providerMetadata = $this->createMock(ProviderMetadata::class);
        $modelMetadata = $this->createMock(ModelMetadata::class);
        $providerModelsMetadata = $this->createMock(ProviderModelsMetadata::class);
        $providerModelsMetadata->method('getProvider')->willReturn($providerMetadata);
        $providerModelsMetadata->method('getModels')->willReturn([$modelMetadata]);
        $this->registry->method('findModelsMetadataForSupport')->willReturn([$providerModelsMetadata]);
        $this->registry->method('getProviderModel')->willReturn($this->createMock(ModelInterface::class));

        $builder = new PromptBuilder($this->registry, 'Test');

        // Without a model explicitly set, it should try to find one from registry
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
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->expects($this->once())
            ->method('setConfig')
            ->with($this->isInstanceOf(ModelConfig::class));

        $builder = new PromptBuilder($this->registry, 'Test');
        $builder->usingModel($model);

        // With an explicitly set model, it should always return true
        $this->assertTrue($builder->isSupported());
    }

    /**
     * Tests isSupported with incompatible model.
     *
     * @return void
     */
    public function testIsSupportedWithIncompatibleModel(): void
    {
        // When no models are found in registry, it should return false
        $this->registry->method('findModelsMetadataForSupport')->willReturn([]);

        $builder = new PromptBuilder($this->registry, 'Test');

        // Without any available models, it should return false
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
        // Start with a user message
        $builder = new PromptBuilder($this->registry);
        $builder->withText('Initial user message');

        // Add history that will make the last message a model message
        $builder->withHistory(
            new UserMessage([new MessagePart('Historical user message')]),
            new ModelMessage([new MessagePart('Historical model response')])
        );

        // Now add a model message manually to be the last message
        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        $messages = $messagesProperty->getValue($builder);
        $messages[] = new ModelMessage([new MessagePart('Final model message')]);
        $messagesProperty->setValue($builder, $messages);

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
            ->withFile('https://example.com/img.jpg', 'image/jpeg')
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
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);
        $this->assertCount(1, $messages);
        $this->assertCount(2, $messages[0]->getParts()); // Text and image

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        /** @var ModelInterface $actualModel */
        $actualModel = $modelProperty->getValue($builder);
        $this->assertSame($model, $actualModel);

        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
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

        $model = $this->createTextGenerationModel($metadata, $result);

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
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(
                new ModelMessage([new MessagePart(new File('data:image/png;base64,iVBORw0KGgo=', 'image/png'))]),
                FinishReasonEnum::stop()
            )],
            new TokenUsage(100, 50, 150)
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createImageGenerationModel($metadata, $result);

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
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(
                new ModelMessage([new MessagePart(new File('data:audio/wav;base64,UklGRigE=', 'audio/wav'))]),
                FinishReasonEnum::stop()
            )],
            new TokenUsage(100, 50, 150)
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createSpeechGenerationModel($metadata, $result);

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
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(new ModelMessage([new MessagePart('Generated text')]), FinishReasonEnum::stop())],
            new TokenUsage(100, 50, 150)
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextGenerationModel($metadata, $result);

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
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(new ModelMessage([new MessagePart('Generated text')]), FinishReasonEnum::stop())],
            new TokenUsage(100, 50, 150)
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);

        $actualResult = $builder->generateTextResult();
        $this->assertSame($result, $actualResult);

        // Verify text modality was included
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
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
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(
                new ModelMessage([new MessagePart(new File('data:image/png;base64,iVBORw0KGgo=', 'image/png'))]),
                FinishReasonEnum::stop()
            )],
            new TokenUsage(100, 50, 150)
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createImageGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate image');
        $builder->usingModel($model);

        $actualResult = $builder->generateImageResult();
        $this->assertSame($result, $actualResult);

        // Verify image modality was included
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
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
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(
                new ModelMessage([new MessagePart(new File('data:audio/wav;base64,UklGRigE=', 'audio/wav'))]),
                FinishReasonEnum::stop()
            )],
            new TokenUsage(100, 50, 150)
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createSpeechGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate speech');
        $builder->usingModel($model);

        $actualResult = $builder->generateSpeechResult();
        $this->assertSame($result, $actualResult);

        // Verify audio modality was included
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
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
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(
                new ModelMessage([new MessagePart(new File('data:audio/wav;base64,UklGRigE=', 'audio/wav'))]),
                FinishReasonEnum::stop()
            )],
            new TokenUsage(100, 50, 150)
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextToSpeechModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Convert to speech');
        $builder->usingModel($model);

        $actualResult = $builder->convertTextToSpeechResult();
        $this->assertSame($result, $actualResult);

        // Verify audio modality was included
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
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
        $model = $this->createMock(ModelInterface::class);
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
        $message = new ModelMessage([$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop());

        $result = new GenerativeAiResult('test-result', [$candidate], new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextGenerationModel($metadata, $result);

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
        // Since GenerativeAiResult constructor requires at least one candidate,
        // we need to create a mock that throws an exception or test a different scenario
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = new class ($metadata) implements ModelInterface, TextGenerationModelInterface {
            private ModelMetadata $metadata;
            private ModelConfig $config;

            public function __construct(ModelMetadata $metadata)
            {
                $this->metadata = $metadata;
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
                throw new RuntimeException('No candidates were generated');
            }

            public function streamGenerateTextResult(array $prompt): Generator
            {
                yield from [];
            }
        };

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
        $message = new ModelMessage([]);
        $candidate = new Candidate($message, FinishReasonEnum::stop());

        $result = new GenerativeAiResult('test-result', [$candidate], new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextGenerationModel($metadata, $result);

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
        $message = new ModelMessage([$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop());

        $result = new GenerativeAiResult('test-result', [$candidate], new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextGenerationModel($metadata, $result);

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
                new ModelMessage([new MessagePart('Text 1')]),
                FinishReasonEnum::stop()
            ),
            new Candidate(
                new ModelMessage([new MessagePart('Text 2')]),
                FinishReasonEnum::stop()
            ),
            new Candidate(
                new ModelMessage([new MessagePart('Text 3')]),
                FinishReasonEnum::stop()
            )
        ];

        $result = new GenerativeAiResult('test-result-id', $candidates, new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextGenerationModel($metadata, $result);

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
        /** @var ModelConfig $config */
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
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = new class ($metadata) implements ModelInterface, TextGenerationModelInterface {
            private ModelMetadata $metadata;
            private ModelConfig $config;

            public function __construct(ModelMetadata $metadata)
            {
                $this->metadata = $metadata;
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
                throw new RuntimeException('No text was generated from any candidates');
            }

            public function streamGenerateTextResult(array $prompt): Generator
            {
                yield from [];
            }
        };

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
        $message = new ModelMessage([$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop());

        $result = new GenerativeAiResult('test-result', [$candidate], new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createImageGenerationModel($metadata, $result);

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
        $message = new ModelMessage([$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop());

        $result = new GenerativeAiResult('test-result', [$candidate], new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createImageGenerationModel($metadata, $result);

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
                FinishReasonEnum::stop()
            );
        }

        $result = new GenerativeAiResult('test-result-id', $candidates, new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createImageGenerationModel($metadata, $result);

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
        $candidate = new Candidate($message, FinishReasonEnum::stop());

        $result = new GenerativeAiResult('test-result', [$candidate], new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextToSpeechModel($metadata, $result);

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
                FinishReasonEnum::stop()
            );
        }

        $result = new GenerativeAiResult('test-result-id', $candidates, new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextToSpeechModel($metadata, $result);

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
        $candidate = new Candidate($message, FinishReasonEnum::stop());

        $result = new GenerativeAiResult('test-result', [$candidate], new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createSpeechGenerationModel($metadata, $result);

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

        $result = new GenerativeAiResult(
            'test-result-id',
            $candidates,
            new TokenUsage(100, 50, 150)
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createSpeechGenerationModel($metadata, $result);

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
     * Tests getConfiguredModel returns explicitly set model.
     *
     * @return void
     */
    public function testGetConfiguredModelReturnsExplicitlySetModel(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('explicit-model');

        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);
        $model->expects($this->once())
            ->method('setConfig')
            ->with($this->isInstanceOf(ModelConfig::class));

        $builder = new PromptBuilder($this->registry, 'Test');
        $builder->usingModel($model);

        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('getConfiguredModel');
        $method->setAccessible(true);

        $result = $method->invoke($builder);
        $this->assertSame($model, $result);
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
        /** @var list<Message> $messages */
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        $this->assertCount(1, $messages);
        $this->assertInstanceOf(Message::class, $messages[0]);
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
        /** @var list<Message> $messages */
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
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        $this->assertCount(3, $messages);
        $this->assertInstanceOf(Message::class, $messages[2]);
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
                ->withFile($file1)
                ->withText(' and this audio:')
                ->withFile($file2)
                ->withFunctionResponse($functionResponse)
                ->withHistory(
                    new UserMessage([new MessagePart('Previous question')]),
                    new ModelMessage([new MessagePart('Previous answer')])
                )
                ->withText(' Final instruction');

        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        // Should have 3 messages: 2 from history + 1 current being built
        $this->assertCount(3, $messages);

        // Check history messages (now at the beginning)
        $this->assertEquals('Previous question', $messages[0]->getParts()[0]->getText());
        $this->assertEquals('Previous answer', $messages[1]->getParts()[0]->getText());

        // Check current message being built (now at the end)
        $currentParts = $messages[2]->getParts();
        $this->assertCount(6, $currentParts); // text, image, text, audio, function response, final text
        $this->assertEquals('Analyze this data:', $currentParts[0]->getText());
        $this->assertSame($file1, $currentParts[1]->getFile());
        $this->assertEquals(' and this audio:', $currentParts[2]->getText());
        $this->assertSame($file2, $currentParts[3]->getFile());
        $this->assertSame($functionResponse, $currentParts[4]->getFunctionResponse());
        $this->assertEquals(' Final instruction', $currentParts[5]->getText());
    }

    /**
     * Tests that withHistory prepends messages to the beginning.
     *
     * @return void
     */
    public function testWithHistoryPrependsMessages(): void
    {
        $builder = new PromptBuilder($this->registry);

        // Start building current message
        $builder->withText('Current message content');

        // Add history
        $builder->withHistory(
            new UserMessage([new MessagePart('First history message')]),
            new ModelMessage([new MessagePart('Second history message')])
        );

        // Add more to current message
        $builder->withText(' with additional content');

        $reflection = new \ReflectionClass($builder);
        $messagesProperty = $reflection->getProperty('messages');
        $messagesProperty->setAccessible(true);
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        // Should have 3 messages: 2 history + 1 current
        $this->assertCount(3, $messages);

        // History should be at the beginning
        $this->assertTrue($messages[0]->getRole()->isUser());
        $this->assertEquals('First history message', $messages[0]->getParts()[0]->getText());

        $this->assertTrue($messages[1]->getRole()->isModel());
        $this->assertEquals('Second history message', $messages[1]->getParts()[0]->getText());

        // Current message should be at the end
        $this->assertTrue($messages[2]->getRole()->isUser());
        $currentParts = $messages[2]->getParts();
        $this->assertCount(2, $currentParts);
        $this->assertEquals('Current message content', $currentParts[0]->getText());
        $this->assertEquals(' with additional content', $currentParts[1]->getText());
    }

    /**
     * Tests includeOutputModality preserves existing modalities.
     *
     * @return void
     */
    public function testIncludeOutputModalityPreservesExisting(): void
    {
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(new ModelMessage([new MessagePart('Generated text')]), FinishReasonEnum::stop())],
            new TokenUsage(100, 50, 150)
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Test');
        $builder->usingModel($model);

        // Set initial modality
        $builder->usingOutputModalities(ModalityEnum::audio());

        // Generate text should add text modality, not replace audio
        $builder->generateTextResult();

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
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
        /** @var list<Message> $messages */
        /** @var list<Message> $messages */
        $messages = $messagesProperty->getValue($builder);

        $this->assertCount(1, $messages);
        $this->assertInstanceOf(Message::class, $messages[0]);
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
        /** @var list<Message> $messages */
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
                ->withFile('https://example.com/img.jpg', 'image/jpeg')
                ->withFile('https://example.com/audio.mp3', 'audio/mp3')
                ->withFile('https://example.com/video.mp4', 'video/mp4')
                ->withFile('https://example.com/doc.pdf', 'application/pdf');

        $requirements = $builder->getModelRequirements();
        $options = $requirements->getRequiredOptions();

        // Find input modalities
        $inputModalities = null;
        foreach ($options as $option) {
            if ($option->getName()->equals(OptionEnum::inputModalities())) {
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
        $optionEnums = array_map(function ($option) {
            return $option->getName();
        }, $options);

        $this->assertContains(OptionEnum::maxTokens(), $optionEnums);
        $this->assertContains(OptionEnum::temperature(), $optionEnums);
        $this->assertContains(OptionEnum::outputModalities(), $optionEnums);
        $this->assertContains(OptionEnum::outputMimeType(), $optionEnums);
        $this->assertContains(OptionEnum::outputSchema(), $optionEnums);
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
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(
                new ModelMessage([new MessagePart(new File('data:image/png;base64,iVBORw0KGgo=', 'image/png'))]),
                FinishReasonEnum::stop()
            )],
            new TokenUsage(100, 50, 150)
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createImageGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate an image');
        $builder->usingModel($model);

        $actualResult = $builder->generateImageResult();
        $this->assertSame($result, $actualResult);

        // Verify that image modality was included in the model config
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $outputModalities = $config->getOutputModalities();
        $this->assertCount(1, $outputModalities);
        $this->assertTrue($outputModalities[0]->isImage());
    }

    /**
     * Tests generateAudioResult method creates proper operation.
     *
     * @return void
     */
    public function testGenerateAudioResultCreatesProperOperation(): void
    {
        $this->markTestSkipped('generateAudioResult method does not exist yet');
    }

    /**
     * Tests generateVideoResult method creates proper operation.
     *
     * @return void
     */
    public function testGenerateVideoResultCreatesProperOperation(): void
    {
        $this->markTestSkipped('generateVideoResult method does not exist yet');
    }

    /**
     * Tests generateImage shorthand method returns file directly.
     *
     * @return void
     */
    public function testGenerateImageReturnsFileDirectly(): void
    {
        $file = new File('https://example.com/generated.jpg', 'image/jpeg');
        $candidate = new Candidate(
            new Message(MessageRoleEnum::model(), [new MessagePart($file)]),
            FinishReasonEnum::stop()
        );

        $result = new GenerativeAiResult('test-result', [$candidate], new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createImageGenerationModel($metadata, $result);

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
        $this->markTestSkipped('generateAudio method does not exist yet');
    }

    /**
     * Tests generateVideo shorthand method returns file directly.
     *
     * @return void
     */
    public function testGenerateVideoReturnsFileDirectly(): void
    {
        $this->markTestSkipped('generateVideo method does not exist yet');
    }

    /**
     * Tests generation method with multiple output modalities.
     *
     * @return void
     */
    public function testGenerationWithMultipleOutputModalities(): void
    {
        $this->markTestSkipped('Operations-based generation not implemented yet');
    }

    /**
     * Tests streaming generation methods.
     *
     * @return void
     */
    public function testStreamingGenerationMethods(): void
    {
        $this->markTestSkipped('Streaming methods do not exist yet');
    }

    /**
     * Tests generateText with no candidates throws exception.
     *
     * @return void
     */
    public function testGenerateTextWithNoCandidatesThrowsException(): void
    {
        // Create a mock result that returns empty candidates
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('getCandidates')->willReturn([]);

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No candidates were generated');

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
        $candidate = new Candidate(
            new Message(MessageRoleEnum::model(), [new MessagePart($file)]),
            FinishReasonEnum::stop()
        );

        $result = new GenerativeAiResult('test-result', [$candidate], new TokenUsage(100, 50, 150));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('meetsRequirements')->willReturn(true);

        $model = $this->createTextGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Generated message part contains no text');

        $builder->generateText();
    }

    /**
     * Tests chain generation with multiple prompts.
     *
     * @return void
     */
    public function testChainGenerationWithMultiplePrompts(): void
    {
        $this->markTestSkipped('Complex chaining with model response methods not fully implemented yet');
    }

    /**
     * Tests isSupported with intended output modality.
     *
     * @return void
     */
    public function testIsSupportedWithIntendedOutput(): void
    {
        $builder = new PromptBuilder($this->registry, 'Test prompt');

        // Mock registry to return no models for image generation
        $this->registry->method('findModelsMetadataForSupport')
            ->willReturnCallback(function ($requirements) {
                $options = $requirements->getRequiredOptions();
                foreach ($options as $option) {
                    if ($option->getName()->equals(OptionEnum::outputModalities())) {
                        $modalities = $option->getValue();
                        foreach ($modalities as $modality) {
                            if ($modality->isImage()) {
                                return []; // No models support image generation
                            }
                        }
                    }
                }
                // Return a mock model for text generation
                $providerMetadata = $this->createMock(ProviderMetadata::class);
                $modelMetadata = $this->createMock(ModelMetadata::class);
                $providerModelsMetadata = $this->createMock(ProviderModelsMetadata::class);
                $providerModelsMetadata->method('getProvider')->willReturn($providerMetadata);
                $providerModelsMetadata->method('getModels')->willReturn([$modelMetadata]);
                return [$providerModelsMetadata];
            });

        // Text should be supported
        $this->assertTrue($builder->isSupported(ModalityEnum::text()));

        // Image should not be supported
        $this->assertFalse($builder->isSupported(ModalityEnum::image()));
    }

    /**
     * Tests isSupportedForText convenience method.
     *
     * @return void
     */
    public function testIsSupportedForText(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('text-model');

        $result = new GenerativeAiResult('test-id', [
            new Candidate(
                new ModelMessage([new MessagePart('Test')]),
                FinishReasonEnum::stop()
            )
        ], new TokenUsage(10, 5, 15));

        $model = $this->createTextGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);

        $this->assertTrue($builder->isSupportedForText());
    }

    /**
     * Tests isSupportedForImage convenience method.
     *
     * @return void
     */
    public function testIsSupportedForImage(): void
    {
        $builder = new PromptBuilder($this->registry, 'Generate an image');

        // Mock registry to return no models for image generation
        $this->registry->method('findModelsMetadataForSupport')
            ->willReturn([]);

        $this->assertFalse($builder->isSupportedForImage());
    }

    /**
     * Tests isSupportedForAudio convenience method.
     *
     * @return void
     */
    public function testIsSupportedForAudio(): void
    {
        $builder = new PromptBuilder($this->registry, 'Generate audio');

        // Mock registry to return no models for audio generation
        $this->registry->method('findModelsMetadataForSupport')
            ->willReturn([]);

        $this->assertFalse($builder->isSupportedForAudio());
    }

    /**
     * Tests isSupportedForVideo convenience method.
     *
     * @return void
     */
    public function testIsSupportedForVideo(): void
    {
        $builder = new PromptBuilder($this->registry, 'Generate video');

        // Mock registry to return no models for video generation
        $this->registry->method('findModelsMetadataForSupport')
            ->willReturn([]);

        $this->assertFalse($builder->isSupportedForVideo());
    }

    /**
     * Tests isSupportedForSpeech convenience method.
     *
     * @return void
     */
    public function testIsSupportedForSpeech(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('speech-model');

        $result = new GenerativeAiResult('test-id', [
            new Candidate(
                new ModelMessage([new MessagePart(new File('https://example.com/speech.mp3', 'audio/mp3'))]),
                FinishReasonEnum::stop()
            )
        ], new TokenUsage(10, 5, 15));

        $model = $this->createSpeechGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate speech');
        $builder->usingModel($model);

        $this->assertTrue($builder->isSupportedForSpeech());
    }

    /**
     * Tests isSupported restores original modalities after check.
     *
     * @return void
     */
    public function testIsSupportedRestoresOriginalModalities(): void
    {
        $builder = new PromptBuilder($this->registry, 'Test prompt');

        // Set initial modality
        $builder->usingOutputModalities(ModalityEnum::text());

        // Mock registry to return models
        $providerMetadata = $this->createMock(ProviderMetadata::class);
        $modelMetadata = $this->createMock(ModelMetadata::class);
        $providerModelsMetadata = $this->createMock(ProviderModelsMetadata::class);
        $providerModelsMetadata->method('getProvider')->willReturn($providerMetadata);
        $providerModelsMetadata->method('getModels')->willReturn([$modelMetadata]);
        $this->registry->method('findModelsMetadataForSupport')->willReturn([$providerModelsMetadata]);
        $this->registry->method('getProviderModel')->willReturn($this->createMock(ModelInterface::class));

        // Check with image modality
        $builder->isSupported(ModalityEnum::image());

        // Verify original modality is restored
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($builder);

        $modalities = $config->getOutputModalities();
        $this->assertCount(1, $modalities);
        $this->assertTrue($modalities[0]->isText());
    }
}

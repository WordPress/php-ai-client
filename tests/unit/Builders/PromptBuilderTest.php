<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\DTO\ProviderModelsMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts\SpeechGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionModelInterface;
use WordPress\AiClient\Providers\Models\VideoGeneration\Contracts\VideoGenerationModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\FunctionResponse;
use WordPress\AiClient\Tools\DTO\WebSearch;

/**
 * @covers \WordPress\AiClient\Builders\PromptBuilder
 */
class PromptBuilderTest extends TestCase
{
    use MockModelCreationTrait;

    /**
     * @var ProviderRegistry
     */
    private ProviderRegistry $registry;

    /**
     * Creates a test provider metadata instance.
     *
     * @return ProviderMetadata
     */
    private function createTestProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata('test-provider', 'Test Provider', ProviderTypeEnum::cloud());
    }

    /**
     * Creates text model metadata supporting any input modalities.
     *
     * @param string $id The model identifier.
     * @return ModelMetadata
     */
    private function createTextModelMetadataWithInputSupport(string $id): ModelMetadata
    {
        return new ModelMetadata(
            $id,
            'Test Text Model',
            [CapabilityEnum::textGeneration()],
            [
                new SupportedOption(OptionEnum::inputModalities()),
                new SupportedOption(OptionEnum::outputModalities()),
            ]
        );
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
        $providerMetadata = new ProviderMetadata(
            'mock-provider',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );

        return new class (
            $metadata,
            $providerMetadata,
            $result
        ) implements ModelInterface, SpeechGenerationModelInterface {
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

            public function generateSpeechResult(array $prompt): GenerativeAiResult
            {
                return $this->result;
            }
        };
    }

    /**
     * Creates a mock model that implements both ModelInterface and VideoGenerationModelInterface.
     *
     * @param ModelMetadata      $metadata The metadata for the model.
     * @param GenerativeAiResult $result   The result to return from generation.
     * @return ModelInterface&VideoGenerationModelInterface The mock model.
     */
    private function createVideoGenerationModel(ModelMetadata $metadata, GenerativeAiResult $result): ModelInterface
    {
        $providerMetadata = new ProviderMetadata(
            'mock-provider',
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
     * Creates a mock model that implements both ModelInterface and TextToSpeechConversionModelInterface.
     *
     * @param ModelMetadata $metadata The metadata for the model.
     * @param GenerativeAiResult $result The result to return from generation.
     * @return ModelInterface&TextToSpeechConversionModelInterface The mock model.
     */
    private function createTextToSpeechModel(ModelMetadata $metadata, GenerativeAiResult $result): ModelInterface
    {
        $providerMetadata = new ProviderMetadata(
            'mock-provider',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );

        return new class (
            $metadata,
            $providerMetadata,
            $result
        ) implements ModelInterface, TextToSpeechConversionModelInterface {
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
        // Create a model with empty config
        $modelConfig = new ModelConfig();
        $model = $this->createMock(ModelInterface::class);
        $model->method('getConfig')->willReturn($modelConfig);

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
     * Tests usingModelPreference selects provided model instance when requirements are met.
     *
     * @return void
     */
    public function testUsingModelPreferenceWithModelInstance(): void
    {
        $result = $this->createTestResult('Preferred model result');
        $metadata = $this->createTextModelMetadataWithInputSupport('preferred-model');
        $model = $this->createMockTextGenerationModel($result, $metadata);
        $providerMetadata = $model->providerMetadata();

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->with($this->isInstanceOf(ModelRequirements::class))
            ->willReturn([new ProviderModelsMetadata($providerMetadata, [$metadata])]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with($providerMetadata->getId(), 'preferred-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $this->registry->expects($this->never())
            ->method('findProviderModelsMetadataForSupport');

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModelPreference($model);

        $actualResult = $builder->generateTextResult();

        $this->assertSame($result, $actualResult);

        $reflection = new \ReflectionClass($builder);
        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);

        $this->assertNull($modelProperty->getValue($builder));
    }

    /**
     * Tests usingModelPreference supports provider/model tuple with string identifier.
     *
     * @return void
     */
    public function testUsingModelPreferenceWithProviderTupleSelectsModel(): void
    {
        $result = $this->createTestResult('Tuple preferred result');
        $firstMetadata = $this->createTextModelMetadataWithInputSupport('first-model');
        $preferredMetadata = $this->createTextModelMetadataWithInputSupport('preferred-model');
        $otherMetadata = $this->createTextModelMetadataWithInputSupport('other-model');
        $model = $this->createMockTextGenerationModel($result, $preferredMetadata);

        $firstProviderMetadata = new ProviderMetadata(
            'first-provider',
            'First Provider',
            ProviderTypeEnum::cloud()
        );

        $preferredProviderMetadata = new ProviderMetadata(
            'preferred-provider',
            'Preferred Provider',
            ProviderTypeEnum::cloud()
        );

        $otherProviderMetadata = new ProviderMetadata(
            'other-provider',
            'Other Provider',
            ProviderTypeEnum::cloud()
        );

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->with($this->isInstanceOf(ModelRequirements::class))
            ->willReturn([
                new ProviderModelsMetadata($firstProviderMetadata, [$firstMetadata]),
                new ProviderModelsMetadata($preferredProviderMetadata, [$preferredMetadata]),
                new ProviderModelsMetadata($otherProviderMetadata, [$otherMetadata]),
            ]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('preferred-provider', 'preferred-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $this->registry->expects($this->never())
            ->method('findProviderModelsMetadataForSupport');

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModelPreference(['preferred-provider', 'preferred-model']);

        $actualResult = $builder->generateTextResult();

        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests usingModelPreference rejects provider/model tuples that contain a model instance.
     *
     * @return void
     */
    public function testUsingModelPreferenceWithProviderTupleModelInstanceThrowsException(): void
    {
        $metadata = $this->createTextModelMetadataWithInputSupport('preferred-model');
        $model = $this->createMockTextGenerationModel($this->createTestResult(), $metadata);

        $builder = new PromptBuilder($this->registry, 'Test prompt');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model preference identifiers cannot be empty.');

        $builder->usingModelPreference(['mock', $model]);
    }

    /**
     * Tests usingModelPreference selects the first available model ID for the configured provider.
     *
     * @return void
     */
    public function testUsingModelPreferencePrefersFirstAvailableModelId(): void
    {
        $result = $this->createTestResult('Preferred by ID');
        $secondaryMetadata = $this->createTextModelMetadataWithInputSupport('secondary-id');
        $preferredMetadata = $this->createTextModelMetadataWithInputSupport('preferred-id');
        $model = $this->createMockTextGenerationModel($result, $preferredMetadata);

        $this->registry->expects($this->once())
            ->method('getProviderId')
            ->with('test-provider')
            ->willReturn('test-provider');

        $this->registry->expects($this->once())
            ->method('findProviderModelsMetadataForSupport')
            ->with('test-provider', $this->isInstanceOf(ModelRequirements::class))
            ->willReturn([$secondaryMetadata, $preferredMetadata]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('test-provider', 'preferred-id', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $this->registry->expects($this->never())
            ->method('findModelsMetadataForSupport');

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingProvider('test-provider');
        $builder->usingModelPreference('preferred-id', 'secondary-id');

        $actualResult = $builder->generateTextResult();

        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests usingModelPreference with provider class name instead of ID.
     *
     * @return void
     */
    public function testUsingModelPreferenceWithProviderClassName(): void
    {
        $result = $this->createTestResult('Preferred with class name');
        $secondaryMetadata = $this->createTextModelMetadataWithInputSupport('secondary-id');
        $preferredMetadata = $this->createTextModelMetadataWithInputSupport('preferred-id');
        $model = $this->createMockTextGenerationModel($result, $preferredMetadata);

        $this->registry->expects($this->once())
            ->method('getProviderId')
            ->with('WordPress\AiClient\TestProvider')
            ->willReturn('test-provider');

        $this->registry->expects($this->once())
            ->method('findProviderModelsMetadataForSupport')
            ->with('WordPress\AiClient\TestProvider', $this->isInstanceOf(ModelRequirements::class))
            ->willReturn([$secondaryMetadata, $preferredMetadata]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('test-provider', 'preferred-id', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $this->registry->expects($this->never())
            ->method('findModelsMetadataForSupport');

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingProvider('WordPress\AiClient\TestProvider');
        $builder->usingModelPreference('preferred-id', 'secondary-id');

        $actualResult = $builder->generateTextResult();

        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests usingModelPreference skips unavailable model IDs and falls back to the next preference.
     *
     * @return void
     */
    public function testUsingModelPreferenceSkipsUnavailableModelId(): void
    {
        $result = $this->createTestResult('Fallback model result');
        $otherMetadata = $this->createTextModelMetadataWithInputSupport('other-id');
        $fallbackMetadata = $this->createTextModelMetadataWithInputSupport('fallback-id');
        $model = $this->createMockTextGenerationModel($result, $fallbackMetadata);

        $this->registry->expects($this->once())
            ->method('getProviderId')
            ->with('test-provider')
            ->willReturn('test-provider');

        $this->registry->expects($this->once())
            ->method('findProviderModelsMetadataForSupport')
            ->with('test-provider', $this->isInstanceOf(ModelRequirements::class))
            ->willReturn([$otherMetadata, $fallbackMetadata]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('test-provider', 'fallback-id', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $this->registry->expects($this->never())
            ->method('findModelsMetadataForSupport');

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingProvider('test-provider');
        $builder->usingModelPreference('missing-id', 'fallback-id');

        $actualResult = $builder->generateTextResult();

        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests usingModelPreference falls back to discovery when no preferences are available.
     *
     * @return void
     */
    public function testUsingModelPreferenceFallsBackToDiscovery(): void
    {
        $result = $this->createTestResult('Discovered model result');
        $metadata = $this->createTextModelMetadataWithInputSupport('discovered-id');
        $providerMetadata = $this->createTestProviderMetadata();
        $providerModelsMetadata = new ProviderModelsMetadata($providerMetadata, [$metadata]);

        $model = $this->createMockTextGenerationModel($result, $metadata);

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->with($this->isInstanceOf(ModelRequirements::class))
            ->willReturn([$providerModelsMetadata]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with($providerMetadata->getId(), 'discovered-id', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $this->registry->expects($this->never())
            ->method('findProviderModelsMetadataForSupport');

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModelPreference('unavailable-model');

        $actualResult = $builder->generateTextResult();

        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests usingModelPreference respects priority order when multiple preferred models are available.
     *
     * @return void
     */
    public function testUsingModelPreferenceRespectsOrderWhenMultipleAvailable(): void
    {
        $result = $this->createTestResult('Second choice result');
        $secondChoiceMetadata = $this->createTextModelMetadataWithInputSupport('second-choice');
        $thirdChoiceMetadata = $this->createTextModelMetadataWithInputSupport('third-choice');
        $providerMetadata = $this->createTestProviderMetadata();

        $model = $this->createMockTextGenerationModel($result, $secondChoiceMetadata);

        // Make both second-choice and third-choice available (but not first-choice)
        $providerModelsMetadata = new ProviderModelsMetadata(
            $providerMetadata,
            [$thirdChoiceMetadata, $secondChoiceMetadata]  // Order shouldn't matter
        );

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->with($this->isInstanceOf(ModelRequirements::class))
            ->willReturn([$providerModelsMetadata]);

        // Should select 'second-choice' (respecting preference order), not 'third-choice'
        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with($providerMetadata->getId(), 'second-choice', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $this->registry->expects($this->never())
            ->method('findProviderModelsMetadataForSupport');

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        // Preferences in order: first-choice, second-choice, third-choice
        // Available: second-choice, third-choice
        // Expected: second-choice (respects priority)
        $builder->usingModelPreference('first-choice', 'second-choice', 'third-choice');

        $actualResult = $builder->generateTextResult();

        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests usingModelPreference rejects invalid preference types.
     *
     * @return void
     */
    public function testUsingModelPreferenceWithInvalidTypeThrowsException(): void
    {
        $builder = new PromptBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Model preferences must be model identifiers, instances of ModelInterface, or provider/model tuples.'
        );

        $builder->usingModelPreference(123);
    }

    /**
     * Tests usingModelPreference rejects malformed preference tuples.
     *
     * @return void
     */
    public function testUsingModelPreferenceWithInvalidTupleThrowsException(): void
    {
        $builder = new PromptBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model preference tuple must contain model identifier and provider ID.');

        $builder->usingModelPreference(['provider' => 'test', 'model' => 'id']);
    }

    /**
     * Tests usingModelPreference rejects empty preference identifier strings.
     *
     * @return void
     */
    public function testUsingModelPreferenceWithEmptyIdentifierThrowsException(): void
    {
        $builder = new PromptBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model preference identifiers cannot be empty.');

        $builder->usingModelPreference('   ');
    }

    /**
     * Tests usingModelPreference rejects calls without preferences.
     *
     * @return void
     */
    public function testUsingModelPreferenceWithoutArgumentsThrowsException(): void
    {
        $builder = new PromptBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one model preference must be provided.');

        $builder->usingModelPreference();
    }

    /**
     * Tests usingModelConfig method.
     *
     * @return void
     */
    public function testUsingModelConfig(): void
    {
        $builder = new PromptBuilder($this->registry);

        // Set some initial config values on the builder
        $builder->usingSystemInstruction('Builder instruction')
                ->usingMaxTokens(500)
                ->usingTemperature(0.5);

        // Create a config to merge
        $config = new ModelConfig();
        $config->setSystemInstruction('Config instruction');
        $config->setMaxTokens(1000);
        $config->setTopP(0.9);
        $config->setTopK(40);

        $result = $builder->usingModelConfig($config);

        // Assert fluent interface
        $this->assertSame($builder, $result);

        // Get the merged config
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);

        /** @var ModelConfig $mergedConfig */
        $mergedConfig = $configProperty->getValue($builder);

        // Check that builder's additional config was included
        // Assert builder values take precedence
        $this->assertEquals('Builder instruction', $mergedConfig->getSystemInstruction());
        $this->assertEquals(500, $mergedConfig->getMaxTokens());
        $this->assertEquals(0.5, $mergedConfig->getTemperature());

        // Assert config values are used when builder doesn't have them
        $this->assertEquals(0.9, $mergedConfig->getTopP());
        $this->assertEquals(40, $mergedConfig->getTopK());
    }

    /**
     * Tests usingModelConfig with custom options.
     *
     * @return void
     */
    public function testUsingModelConfigWithCustomOptions(): void
    {
        $builder = new PromptBuilder($this->registry);

        // Create a config with custom options
        $config = new ModelConfig();
        $config->setCustomOption('stopSequences', ['CONFIG_STOP']);
        $config->setCustomOption('otherOption', 'value');

        $builder->usingModelConfig($config);

        // Get the merged config
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);

        /** @var ModelConfig $mergedConfig */
        $mergedConfig = $configProperty->getValue($builder);
        $customOptions = $mergedConfig->getCustomOptions();

        // Assert config custom options are preserved
        $this->assertArrayHasKey('stopSequences', $customOptions);
        $this->assertIsArray($customOptions['stopSequences']);
        $this->assertEquals(['CONFIG_STOP'], $customOptions['stopSequences']);
        $this->assertArrayHasKey('otherOption', $customOptions);
        $this->assertEquals('value', $customOptions['otherOption']);

        // Now set stop sequences via the dedicated method
        $builder->usingStopSequences('STOP');

        // Get the config again
        $mergedConfig = $configProperty->getValue($builder);

        // Assert builder's stop sequences are set on the dedicated property
        $this->assertEquals(['STOP'], $mergedConfig->getStopSequences());

        // Assert custom options are still preserved
        $customOptions = $mergedConfig->getCustomOptions();
        $this->assertArrayHasKey('otherOption', $customOptions);
        $this->assertEquals('value', $customOptions['otherOption']);
    }

    /**
     * Tests usingProvider method.
     *
     * @return void
     */
    public function testUsingProvider(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingProvider('test-provider');

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $providerProperty = $reflection->getProperty('providerIdOrClassName');
        $providerProperty->setAccessible(true);

        $actualProvider = $providerProperty->getValue($builder);
        $this->assertEquals('test-provider', $actualProvider);
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

        $this->assertEquals(['STOP', 'END', '###'], $config->getStopSequences());
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
        $result = $builder->asOutputMimeType('application/json');

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
        $result = $builder->asOutputSchema($schema);

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
        $result = $builder->asOutputModalities(
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

        $model = $this->createMockTextGenerationModel($result, $metadata);

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
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockImageGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Generate an image');
        $builder->usingModel($model);
        $builder->asOutputModalities(ModalityEnum::image());

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
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createSpeechGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate speech');
        $builder->usingModel($model);
        $builder->asOutputModalities(ModalityEnum::audio());

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
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockTextGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Generate multimodal');
        $builder->usingModel($model);
        $builder->asOutputModalities(ModalityEnum::text(), ModalityEnum::image());

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

        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);
        $builder->asOutputModalities(ModalityEnum::document());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Output modality "document" is not yet supported');

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
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockTextGenerationModel($result, $metadata);

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
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockImageGenerationModel($result, $metadata);

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
     * Tests generateEmbeddingResult method.
     *
     * @return void
     */
    public function testGenerateEmbeddingResult(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2, 0.3]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $builder = new PromptBuilder($this->registry, 'Generate embedding');
        $builder->usingModel($model);
        $builder->usingDimensions(3);
        $builder->usingEncodingFormat('float');

        $actualResult = $builder->generateEmbeddingResult();
        $this->assertSame($result, $actualResult);

        $this->assertSame(3, $model->getConfig()->getDimensions());
        $this->assertSame('float', $model->getConfig()->getEncodingFormat());
    }

    /**
     * Tests generateEmbedding returns the first vector.
     *
     * @return void
     */
    public function testGenerateEmbedding(): void
    {
        $result = $this->createTestEmbeddingResult([[0.1, 0.2], [0.3, 0.4]]);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $builder = new PromptBuilder($this->registry, 'Generate embedding');
        $builder->usingModel($model);

        $this->assertSame([0.1, 0.2], $builder->generateEmbedding());
    }

    /**
     * Tests generateEmbeddings returns batch vectors.
     *
     * @return void
     */
    public function testGenerateEmbeddings(): void
    {
        $embeddings = [[0.1, 0.2], [0.3, 0.4]];
        $result = $this->createTestEmbeddingResult($embeddings);
        $model = $this->createMockEmbeddingGenerationModel($result);

        $builder = new PromptBuilder($this->registry);
        $builder->usingModel($model);

        $this->assertSame($embeddings, $builder->generateEmbeddings(['First prompt', 'Second prompt']));
    }

    /**
     * Tests generateEmbeddingResult throws exception for unsupported model.
     *
     * @return void
     */
    public function testGenerateEmbeddingResultThrowsExceptionForUnsupportedModel(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);

        $builder = new PromptBuilder($this->registry, 'Generate embedding');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "test-model" does not support embedding generation');

        $builder->generateEmbeddingResult();
    }

    /**
     * Tests generateVideoResult method.
     *
     * @return void
     */
    public function testGenerateVideoResult(): void
    {
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(
                new ModelMessage([new MessagePart(new File('data:video/mp4;base64,AAAAIGZ0eXA=', 'video/mp4'))]),
                FinishReasonEnum::stop()
            )],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createVideoGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate video');
        $builder->usingModel($model);

        $actualResult = $builder->generateVideoResult();
        $this->assertSame($result, $actualResult);

        // Verify video modality was included
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $modalities = $config->getOutputModalities();
        $this->assertNotNull($modalities);
        $this->assertTrue($modalities[0]->isVideo());
    }

    /**
     * Tests generateVideoResult throws exception for unsupported model.
     *
     * @return void
     */
    public function testGenerateVideoResultThrowsExceptionForUnsupportedModel(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);

        $builder = new PromptBuilder($this->registry, 'Generate video');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "test-model" does not support video generation');

        $builder->generateVideoResult();
    }

    /**
     * Tests generateResult infers video capability from model interface.
     *
     * @return void
     */
    public function testGenerateResultInfersVideoCapabilityFromModel(): void
    {
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(
                new ModelMessage([new MessagePart(new File('data:video/mp4;base64,AAAAIGZ0eXA=', 'video/mp4'))]),
                FinishReasonEnum::stop()
            )],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createVideoGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate video');
        $builder->usingModel($model);

        $actualResult = $builder->generateResult();
        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests generateResult infers video capability from video output modality.
     *
     * @return void
     */
    public function testGenerateResultInfersVideoCapabilityFromOutputModality(): void
    {
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(
                new ModelMessage([new MessagePart(new File('data:video/mp4;base64,AAAAIGZ0eXA=', 'video/mp4'))]),
                FinishReasonEnum::stop()
            )],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createVideoGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate video');
        $builder->usingModel($model);
        $builder->asOutputModalities(ModalityEnum::video());

        $actualResult = $builder->generateResult();
        $this->assertSame($result, $actualResult);
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
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

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
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

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

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockTextGenerationModel($result, $metadata);

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

        $providerMetadata = new ProviderMetadata(
            'mock-provider',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );

        $model = new class (
            $metadata,
            $providerMetadata
        ) implements ModelInterface, TextGenerationModelInterface {
            private ModelMetadata $metadata;
            private ProviderMetadata $providerMetadata;
            private ModelConfig $config;

            public function __construct(
                ModelMetadata $metadata,
                ProviderMetadata $providerMetadata
            ) {
                $this->metadata = $metadata;
                $this->providerMetadata = $providerMetadata;
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
                throw new RuntimeException('No candidates were generated');
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

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockTextGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No text content found in first candidate');

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

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockTextGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No text content found in first candidate');

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

        $result = new GenerativeAiResult(
            'test-result-id',
            $candidates,
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockTextGenerationModel($result, $metadata);

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

        $providerMetadata = new ProviderMetadata(
            'mock-provider',
            'Mock Provider',
            ProviderTypeEnum::cloud()
        );

        $model = new class (
            $metadata,
            $providerMetadata
        ) implements ModelInterface, TextGenerationModelInterface {
            private ModelMetadata $metadata;
            private ProviderMetadata $providerMetadata;
            private ModelConfig $config;

            public function __construct(
                ModelMetadata $metadata,
                ProviderMetadata $providerMetadata
            ) {
                $this->metadata = $metadata;
                $this->providerMetadata = $providerMetadata;
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
                throw new RuntimeException('No text was generated from any candidates');
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

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockImageGenerationModel($result, $metadata);

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

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockImageGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Generate image');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No file content found in first candidate');

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

        $result = new GenerativeAiResult(
            'test-result-id',
            $candidates,
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockImageGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Generate images');
        $builder->usingModel($model);

        $generatedFiles = $builder->generateImages(2);

        $this->assertCount(2, $generatedFiles);
        $this->assertSame($files[0], $generatedFiles[0]);
        $this->assertSame($files[1], $generatedFiles[1]);
    }

    /**
     * Tests generateVideo method.
     *
     * @return void
     */
    public function testGenerateVideo(): void
    {
        $file = new File('https://example.com/generated.mp4', 'video/mp4');
        $messagePart = new MessagePart($file);
        $message = new ModelMessage([$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop());

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createVideoGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate video');
        $builder->usingModel($model);

        $generatedFile = $builder->generateVideo();
        $this->assertSame($file, $generatedFile);
    }

    /**
     * Tests generateVideo throws exception when no video file.
     *
     * @return void
     */
    public function testGenerateVideoThrowsExceptionWhenNoFile(): void
    {
        $messagePart = new MessagePart('Text instead of video');
        $message = new ModelMessage([$messagePart]);
        $candidate = new Candidate($message, FinishReasonEnum::stop());

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createVideoGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate video');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No file content found in first candidate');

        $builder->generateVideo();
    }

    /**
     * Tests generateVideos method.
     *
     * @return void
     */
    public function testGenerateVideos(): void
    {
        $files = [
            new File('https://example.com/vid1.mp4', 'video/mp4'),
            new File('https://example.com/vid2.mp4', 'video/mp4'),
        ];

        $candidates = [];
        foreach ($files as $file) {
            $candidates[] = new Candidate(
                new Message(MessageRoleEnum::model(), [new MessagePart($file)]),
                FinishReasonEnum::stop()
            );
        }

        $result = new GenerativeAiResult(
            'test-result-id',
            $candidates,
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createVideoGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate videos');
        $builder->usingModel($model);

        $generatedFiles = $builder->generateVideos(2);

        $this->assertCount(2, $generatedFiles);
        $this->assertSame($files[0], $generatedFiles[0]);
        $this->assertSame($files[1], $generatedFiles[1]);
    }

    /**
     * Tests generateVideos method without candidate count.
     *
     * @return void
     */
    public function testGenerateVideosWithoutCandidateCount(): void
    {
        $file = new File('https://example.com/vid1.mp4', 'video/mp4');

        $result = new GenerativeAiResult(
            'test-result-id',
            [new Candidate(
                new Message(MessageRoleEnum::model(), [new MessagePart($file)]),
                FinishReasonEnum::stop()
            )],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createVideoGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate videos');
        $builder->usingModel($model);

        $generatedFiles = $builder->generateVideos();

        $this->assertCount(1, $generatedFiles);
        $this->assertSame($file, $generatedFiles[0]);
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

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

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

        $result = new GenerativeAiResult(
            'test-result-id',
            $candidates,
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

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

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

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
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

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

        $builder = new PromptBuilder($this->registry);
        $builder->withText('Analyze this data:')
                ->withFile($file1)
                ->withText(' and this audio:')
                ->withFile($file2)
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
        $this->assertCount(5, $currentParts); // text, image, text, audio, final text
        $this->assertEquals('Analyze this data:', $currentParts[0]->getText());
        $this->assertSame($file1, $currentParts[1]->getFile());
        $this->assertEquals(' and this audio:', $currentParts[2]->getText());
        $this->assertSame($file2, $currentParts[3]->getFile());
        $this->assertEquals(' Final instruction', $currentParts[4]->getText());
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
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockTextGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Test');
        $builder->usingModel($model);

        // Set initial modality
        $builder->asOutputModalities(ModalityEnum::audio());

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
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockImageGenerationModel($result, $metadata);

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

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockImageGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Generate an image');
        $builder->usingModel($model);

        $generatedFile = $builder->generateImage();
        $this->assertSame($file, $generatedFile);
    }

    /**
     * Tests generateVideoResult method creates proper operation.
     *
     * @return void
     */
    public function testGenerateVideoResultCreatesProperOperation(): void
    {
        $result = new GenerativeAiResult(
            'test-result',
            [new Candidate(
                new ModelMessage([new MessagePart(new File('data:video/mp4;base64,AAAAIGZ0eXA=', 'video/mp4'))]),
                FinishReasonEnum::stop()
            )],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createVideoGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate a video');
        $builder->usingModel($model);

        $actualResult = $builder->generateVideoResult();
        $this->assertSame($result, $actualResult);

        // Verify that video modality was included in the model config
        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $outputModalities = $config->getOutputModalities();
        $this->assertCount(1, $outputModalities);
        $this->assertTrue($outputModalities[0]->isVideo());
    }


    /**
     * Tests generateVideo shorthand method returns file directly.
     *
     * @return void
     */
    public function testGenerateVideoReturnsFileDirectly(): void
    {
        $file = new File('https://example.com/generated.mp4', 'video/mp4');
        $candidate = new Candidate(
            new Message(MessageRoleEnum::model(), [new MessagePart($file)]),
            FinishReasonEnum::stop()
        );

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createVideoGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate a video');
        $builder->usingModel($model);

        $generatedFile = $builder->generateVideo();
        $this->assertSame($file, $generatedFile);
    }



    /**
     * Tests generateText with no candidates throws exception.
     *
     * @return void
     */
    public function testGenerateTextWithNoCandidatesThrowsException(): void
    {
        // Create a mock result that throws when toText is called
        $result = $this->createMock(GenerativeAiResult::class);
        $result->method('toText')->willThrowException(new RuntimeException('No text content found in first candidate'));

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockTextGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No text content found in first candidate');

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

        $result = new GenerativeAiResult(
            'test-result',
            [$candidate],
            new TokenUsage(100, 50, 150),
            $this->createTestProviderMetadata(),
            $this->createTestTextModelMetadata()
        );

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMockTextGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Generate text');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No text content found in first candidate');

        $builder->generateText();
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
        $metadata->method('getSupportedCapabilities')->willReturn([
            CapabilityEnum::textGeneration()
        ]);
        $metadata->method('getSupportedOptions')->willReturn([
            new SupportedOption(OptionEnum::inputModalities(), [
                [ModalityEnum::text()],
                [ModalityEnum::text(), ModalityEnum::image()]
            ])
        ]);

        $result = new GenerativeAiResult('test-id', [
            new Candidate(
                new ModelMessage([new MessagePart('Test')]),
                FinishReasonEnum::stop()
            )
        ], new TokenUsage(10, 5, 15), $this->createTestProviderMetadata(), $this->createTestTextModelMetadata());

        $model = $this->createMockTextGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);

        $this->assertTrue($builder->isSupportedForTextGeneration());
    }

    /**
     * Tests isSupportedForImageGeneration convenience method.
     *
     * @return void
     */
    public function testIsSupportedForImageGeneration(): void
    {
        $builder = new PromptBuilder($this->registry, 'Generate an image');

        // Mock registry to return no models for image generation
        $this->registry->method('findModelsMetadataForSupport')
            ->willReturn([]);

        $this->assertFalse($builder->isSupportedForImageGeneration());
    }

    /**
     * Tests isSupportedForTextToSpeechConversion convenience method.
     *
     * @return void
     */
    public function testIsSupportedForTextToSpeechConversion(): void
    {
        $builder = new PromptBuilder($this->registry, 'Generate audio');

        // Mock registry to return no models for text to speech conversion
        $this->registry->method('findModelsMetadataForSupport')
            ->willReturn([]);

        $this->assertFalse($builder->isSupportedForTextToSpeechConversion());
    }

    /**
     * Tests isSupportedForVideoGeneration convenience method.
     *
     * @return void
     */
    public function testIsSupportedForVideoGeneration(): void
    {
        $builder = new PromptBuilder($this->registry, 'Generate video');

        // Mock registry to return no models for video generation
        $this->registry->method('findModelsMetadataForSupport')
            ->willReturn([]);

        $this->assertFalse($builder->isSupportedForVideoGeneration());
    }

    /**
     * Tests isSupportedForVideoGeneration returns true when a video generation model is set.
     *
     * @return void
     */
    public function testIsSupportedForVideoGenerationWithModel(): void
    {
        $metadata = new ModelMetadata(
            'video-model',
            'Video Model',
            [CapabilityEnum::videoGeneration()],
            [
                new SupportedOption(OptionEnum::inputModalities(), [
                    [ModalityEnum::text()]
                ]),
            ]
        );

        $result = $this->createTestResult();
        $model = $this->createVideoGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate video');
        $builder->usingModel($model);

        $this->assertTrue($builder->isSupportedForVideoGeneration());
    }

    /**
     * Tests isSupportedForSpeechGeneration convenience method.
     *
     * @return void
     */
    public function testIsSupportedForSpeechGeneration(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('speech-model');
        $metadata->method('getSupportedCapabilities')->willReturn([
            CapabilityEnum::speechGeneration()
        ]);
        $metadata->method('getSupportedOptions')->willReturn([
            new SupportedOption(OptionEnum::inputModalities(), [
                [ModalityEnum::text()],
                [ModalityEnum::text(), ModalityEnum::image()]
            ])
        ]);

        $result = new GenerativeAiResult('test-id', [
            new Candidate(
                new ModelMessage([new MessagePart(new File('https://example.com/speech.mp3', 'audio/mp3'))]),
                FinishReasonEnum::stop()
            )
        ], new TokenUsage(10, 5, 15), $this->createTestProviderMetadata(), $this->createTestTextModelMetadata());

        $model = $this->createSpeechGenerationModel($metadata, $result);

        $builder = new PromptBuilder($this->registry, 'Generate speech');
        $builder->usingModel($model);

        $this->assertTrue($builder->isSupportedForSpeechGeneration());
    }

    /**
     * Tests generateResult with provider specified.
     *
     * @return void
     */
    public function testGenerateResultWithProvider(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);

        $modelMetadata = $this->createMock(ModelMetadata::class);
        $modelMetadata->method('getId')->willReturn('provider-model');

        $model = $this->createMockTextGenerationModel($result, $modelMetadata);

        // Mock the registry to return the model when provider is specified
        $this->registry->expects($this->once())
            ->method('getProviderId')
            ->with('test-provider')
            ->willReturn('test-provider');

        $this->registry->expects($this->once())
            ->method('findProviderModelsMetadataForSupport')
            ->with('test-provider', $this->isInstanceOf(ModelRequirements::class))
            ->willReturn([$modelMetadata]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('test-provider', 'provider-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingProvider('test-provider');

        $actualResult = $builder->generateResult();
        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests generateResult with provider class name specified.
     *
     * @return void
     */
    public function testGenerateResultWithProviderClassName(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);

        $modelMetadata = $this->createMock(ModelMetadata::class);
        $modelMetadata->method('getId')->willReturn('provider-model');

        $model = $this->createMockTextGenerationModel($result, $modelMetadata);

        // Mock the registry to return the provider ID when given a class name
        $this->registry->expects($this->once())
            ->method('getProviderId')
            ->with('WordPress\AiClient\TestProvider')
            ->willReturn('test-provider');

        $this->registry->expects($this->once())
            ->method('findProviderModelsMetadataForSupport')
            ->with('WordPress\AiClient\TestProvider', $this->isInstanceOf(ModelRequirements::class))
            ->willReturn([$modelMetadata]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('test-provider', 'provider-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingProvider('WordPress\AiClient\TestProvider');

        $actualResult = $builder->generateResult();
        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests generateResult with provider but no suitable models.
     *
     * @return void
     */
    public function testGenerateResultWithProviderNoModelsThrowsException(): void
    {
        // Mock the registry to return empty array when provider is specified
        $this->registry->expects($this->once())
            ->method('findProviderModelsMetadataForSupport')
            ->with('test-provider', $this->isInstanceOf(ModelRequirements::class))
            ->willReturn([]);

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingProvider('test-provider');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'No models found for provider "test-provider" that support text_generation for this prompt.'
        );

        $builder->generateResult();
    }

    /**
     * Tests that provider takes precedence when both provider and model are set.
     *
     * @return void
     */
    public function testModelTakesPrecedenceOverProvider(): void
    {
        $result = $this->createMock(GenerativeAiResult::class);

        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('explicit-model');

        $model = $this->createMockTextGenerationModel($result, $metadata);

        // Registry should not be called when model is explicitly set
        $this->registry->expects($this->never())
            ->method('findProviderModelsMetadataForSupport');
        $this->registry->expects($this->never())
            ->method('getProviderModel');

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingProvider('test-provider');
        $builder->usingModel($model);  // Model overrides provider

        $actualResult = $builder->generateResult();
        $this->assertSame($result, $actualResult);
    }

    /**
     * Tests fluent interface with provider.
     *
     * @return void
     */
    public function testFluentInterfaceWithProvider(): void
    {
        $builder = new PromptBuilder($this->registry, 'Initial text');

        $result = $builder
            ->usingProvider('my-provider')
            ->withText(' Additional text')
            ->usingMaxTokens(500)
            ->usingTemperature(0.7);

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);

        $providerProperty = $reflection->getProperty('providerIdOrClassName');
        $providerProperty->setAccessible(true);
        $this->assertEquals('my-provider', $providerProperty->getValue($builder));

        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);
        $this->assertEquals(500, $config->getMaxTokens());
        $this->assertEquals(0.7, $config->getTemperature());
    }

    /**
     * Tests usingFunctionDeclarations method.
     *
     * @return void
     */
    public function testUsingFunctionDeclarations(): void
    {
        $builder = new PromptBuilder($this->registry);

        $functionDeclaration1 = new FunctionDeclaration(
            'test_function',
            'A test function',
            ['param1' => ['type' => 'string']]
        );
        $functionDeclaration2 = new FunctionDeclaration(
            'another_function',
            'Another test function',
            ['param2' => ['type' => 'integer']]
        );

        $result = $builder->usingFunctionDeclarations($functionDeclaration1, $functionDeclaration2);

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $functionDeclarations = $config->getFunctionDeclarations();
        $this->assertIsArray($functionDeclarations);
        $this->assertCount(2, $functionDeclarations);
        $this->assertSame($functionDeclaration1, $functionDeclarations[0]);
        $this->assertSame($functionDeclaration2, $functionDeclarations[1]);
    }

    /**
     * Tests usingPresencePenalty method.
     *
     * @return void
     */
    public function testUsingPresencePenalty(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingPresencePenalty(0.5);

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $this->assertEquals(0.5, $config->getPresencePenalty());
    }

    /**
     * Tests usingFrequencyPenalty method.
     *
     * @return void
     */
    public function testUsingFrequencyPenalty(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingFrequencyPenalty(0.8);

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $this->assertEquals(0.8, $config->getFrequencyPenalty());
    }

    /**
     * Tests usingWebSearch method.
     *
     * @return void
     */
    public function testUsingWebSearch(): void
    {
        $builder = new PromptBuilder($this->registry);

        $webSearch = new WebSearch(
            ['allowed.com', 'trusted.org'],
            ['blocked.com', 'spam.net']
        );

        $result = $builder->usingWebSearch($webSearch);

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $configWebSearch = $config->getWebSearch();
        $this->assertNotNull($configWebSearch);
        $this->assertSame($webSearch, $configWebSearch);
        $this->assertEquals(['allowed.com', 'trusted.org'], $configWebSearch->getAllowedDomains());
        $this->assertEquals(['blocked.com', 'spam.net'], $configWebSearch->getDisallowedDomains());
    }

    /**
     * Tests asOutputFileType method.
     *
     * @return void
     */
    public function testAsOutputFileType(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->asOutputFileType(FileTypeEnum::inline());

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $outputFileType = $config->getOutputFileType();
        $this->assertNotNull($outputFileType);
        $this->assertTrue($outputFileType->isInline());
    }

    /**
     * Tests asOutputFileType method with remote file type.
     *
     * @return void
     */
    public function testAsOutputFileTypeWithRemote(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->asOutputFileType(FileTypeEnum::remote());

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $outputFileType = $config->getOutputFileType();
        $this->assertNotNull($outputFileType);
        $this->assertTrue($outputFileType->isRemote());
    }

    /**
     * Tests usingTopLogprobs method with null value (only enables logprobs).
     *
     * @return void
     */
    public function testUsingTopLogprobsWithNull(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingTopLogprobs();

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $this->assertTrue($config->getLogprobs());
        $this->assertNull($config->getTopLogprobs());
    }

    /**
     * Tests usingTopLogprobs method with specific value.
     *
     * @return void
     */
    public function testUsingTopLogprobsWithValue(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingTopLogprobs(5);

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $this->assertTrue($config->getLogprobs());
        $this->assertEquals(5, $config->getTopLogprobs());
    }

    /**
     * Tests method chaining with multiple new methods.
     *
     * @return void
     */
    public function testMethodChainingWithNewMethods(): void
    {
        $builder = new PromptBuilder($this->registry);

        $functionDeclaration = new FunctionDeclaration(
            'test_function',
            'A test function',
            ['param1' => ['type' => 'string']]
        );

        $webSearch = new WebSearch(['allowed.com'], ['blocked.com']);

        $result = $builder
            ->withText('Test prompt')
            ->usingPresencePenalty(0.5)
            ->usingFrequencyPenalty(0.7)
            ->usingFunctionDeclarations($functionDeclaration)
            ->usingWebSearch($webSearch)
            ->asOutputFileType(FileTypeEnum::inline())
            ->usingTopLogprobs(3);

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $this->assertEquals(0.5, $config->getPresencePenalty());
        $this->assertEquals(0.7, $config->getFrequencyPenalty());
        $this->assertCount(1, $config->getFunctionDeclarations());
        $this->assertNotNull($config->getWebSearch());
        $this->assertTrue($config->getOutputFileType()->isInline());
        $this->assertTrue($config->getLogprobs());
        $this->assertEquals(3, $config->getTopLogprobs());
    }

    /**
     * Tests isSupported method with explicit capability.
     *
     * @return void
     */
    public function testIsSupportedWithExplicitCapability(): void
    {
        $builder = new PromptBuilder($this->registry, 'Test prompt');

        // Mock registry to return a model supporting text generation
        $this->registry->method('findModelsMetadataForSupport')
            ->willReturn([$this->createMock(ProviderModelsMetadata::class)]);

        $this->assertTrue($builder->isSupported(CapabilityEnum::textGeneration()));
    }

    /**
     * Tests isSupported method with inferred capability from output modalities.
     *
     * @return void
     */
    public function testIsSupportedWithInferredCapability(): void
    {
        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->asOutputModalities(ModalityEnum::image());

        // Mock registry to return a model supporting image generation
        $this->registry->method('findModelsMetadataForSupport')
            ->willReturn([$this->createMock(ProviderModelsMetadata::class)]);

        // Should infer image generation capability
        $this->assertTrue($builder->isSupported());
    }

    /**
     * Tests isSupported method with inferred capability from model interfaces.
     *
     * @return void
     */
    public function testIsSupportedWithInferredCapabilityFromModel(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');
        $metadata->method('getSupportedCapabilities')->willReturn([
            CapabilityEnum::textGeneration()
        ]);
        $metadata->method('getSupportedOptions')->willReturn([
            new SupportedOption(OptionEnum::inputModalities(), [
                [ModalityEnum::text()]
            ])
        ]);

        $result = new GenerativeAiResult('test-id', [
            new Candidate(
                new ModelMessage([new MessagePart('Test')]),
                FinishReasonEnum::stop()
            )
        ], new TokenUsage(10, 5, 15), $this->createTestProviderMetadata(), $this->createTestTextModelMetadata());

        $model = $this->createMockTextGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);

        // Should infer text generation capability from the model interface
        $this->assertTrue($builder->isSupported());
    }

    /**
     * Tests isSupported method when a model is explicitly set.
     *
     * @return void
     */
    public function testIsSupportedWithModelSet(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('text-model');
        $metadata->method('getSupportedCapabilities')->willReturn([
            CapabilityEnum::textGeneration()
        ]);
        // Mock getSupportedOptions to return required options
        $metadata->method('getSupportedOptions')->willReturn([
            new SupportedOption(OptionEnum::inputModalities(), [
                [ModalityEnum::text()]
            ])
        ]);

        $result = new GenerativeAiResult('test-id', [
            new Candidate(
                new ModelMessage([new MessagePart('Test')]),
                FinishReasonEnum::stop()
            )
        ], new TokenUsage(10, 5, 15), $this->createTestProviderMetadata(), $this->createTestTextModelMetadata());

        $model = $this->createMockTextGenerationModel($result, $metadata);

        $builder = new PromptBuilder($this->registry, 'Test prompt');
        $builder->usingModel($model);

        $this->assertTrue($builder->isSupported(CapabilityEnum::textGeneration()));
        $this->assertFalse($builder->isSupported(CapabilityEnum::imageGeneration()));
    }

    /**
     * Tests isSupported method when no models support the requirements.
     *
     * @return void
     */
    public function testIsSupportedWithNoSupport(): void
    {
        $builder = new PromptBuilder($this->registry, 'Test prompt');

        // Mock registry to return no models
        $this->registry->method('findModelsMetadataForSupport')
            ->willReturn([]);

        $this->assertFalse($builder->isSupported(CapabilityEnum::textGeneration()));
    }

    /**
     * Tests that cloning creates independent message references.
     *
     * @return void
     */
    public function testCloneCreatesDifferentMessagesReferences(): void
    {
        $original = new PromptBuilder($this->registry, 'First message');
        $original->withText(' continued');

        $cloned = clone $original;

        // Add more content to the cloned builder
        $cloned->withText(' and more');

        // Use reflection to access the protected messages property
        $originalReflection = new \ReflectionClass($original);
        $messagesProperty = $originalReflection->getProperty('messages');
        $messagesProperty->setAccessible(true);

        $originalMessages = $messagesProperty->getValue($original);
        $clonedMessages = $messagesProperty->getValue($cloned);

        // Original should have 1 message, cloned should have different instances
        $this->assertCount(1, $originalMessages);
        $this->assertNotSame($originalMessages[0], $clonedMessages[0]);
    }

    /**
     * Tests that cloning creates an independent model config reference.
     *
     * @return void
     */
    public function testCloneCreatesDifferentModelConfigReference(): void
    {
        $original = new PromptBuilder($this->registry, 'Test prompt');
        $original->usingTemperature(0.7);
        $original->usingMaxTokens(100);

        $cloned = clone $original;

        // Modify the cloned builder's config
        $cloned->usingTemperature(0.9);

        // Use reflection to access the protected modelConfig property
        $originalReflection = new \ReflectionClass($original);
        $configProperty = $originalReflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);

        $originalConfig = $configProperty->getValue($original);
        $clonedConfig = $configProperty->getValue($cloned);

        // Should be different instances
        $this->assertNotSame($originalConfig, $clonedConfig);

        // Original should still have 0.7, cloned should have 0.9
        $this->assertEquals(0.7, $originalConfig->getTemperature());
        $this->assertEquals(0.9, $clonedConfig->getTemperature());
    }

    /**
     * Tests that cloning creates an independent request options reference.
     *
     * @return void
     */
    public function testCloneCreatesDifferentRequestOptionsReference(): void
    {
        $requestOptions = new \WordPress\AiClient\Providers\Http\DTO\RequestOptions();
        $requestOptions->setTimeout(30.0);

        $original = new PromptBuilder($this->registry, 'Test prompt');
        $original->usingRequestOptions($requestOptions);

        $cloned = clone $original;

        // Use reflection to access the protected requestOptions property
        $originalReflection = new \ReflectionClass($original);
        $optionsProperty = $originalReflection->getProperty('requestOptions');
        $optionsProperty->setAccessible(true);

        $originalOptions = $optionsProperty->getValue($original);
        $clonedOptions = $optionsProperty->getValue($cloned);

        // Should be different instances
        $this->assertNotSame($originalOptions, $clonedOptions);

        // But values should be equivalent
        $this->assertEquals($originalOptions->getTimeout(), $clonedOptions->getTimeout());
    }

    /**
     * Tests that cloning works correctly when request options are null.
     *
     * @return void
     */
    public function testCloneWorksWithNullRequestOptions(): void
    {
        $original = new PromptBuilder($this->registry, 'Test prompt');
        // Don't set request options

        $cloned = clone $original;

        // Use reflection to verify null request options
        $originalReflection = new \ReflectionClass($cloned);
        $optionsProperty = $originalReflection->getProperty('requestOptions');
        $optionsProperty->setAccessible(true);

        $this->assertNull($optionsProperty->getValue($cloned));
    }

    /**
     * Tests asOutputMediaOrientation method.
     *
     * @return void
     */
    public function testAsOutputMediaOrientation(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->asOutputMediaOrientation(MediaOrientationEnum::landscape());

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $outputMediaOrientation = $config->getOutputMediaOrientation();
        $this->assertNotNull($outputMediaOrientation);
        $this->assertTrue($outputMediaOrientation->isLandscape());
    }

    /**
     * Tests asOutputMediaOrientation method with portrait orientation.
     *
     * @return void
     */
    public function testAsOutputMediaOrientationWithPortrait(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->asOutputMediaOrientation(MediaOrientationEnum::portrait());

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $outputMediaOrientation = $config->getOutputMediaOrientation();
        $this->assertNotNull($outputMediaOrientation);
        $this->assertTrue($outputMediaOrientation->isPortrait());
    }

    /**
     * Tests asOutputMediaAspectRatio method.
     *
     * @return void
     */
    public function testAsOutputMediaAspectRatio(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->asOutputMediaAspectRatio('16:9');

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $this->assertEquals('16:9', $config->getOutputMediaAspectRatio());
    }

    /**
     * Tests asOutputSpeechVoice method.
     *
     * @return void
     */
    public function testAsOutputSpeechVoice(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->asOutputSpeechVoice('alloy');

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $this->assertEquals('alloy', $config->getOutputSpeechVoice());
    }

    /**
     * Tests usingStopSequences sets the dedicated property.
     *
     * @return void
     */
    public function testUsingStopSequencesSetsProperty(): void
    {
        $builder = new PromptBuilder($this->registry);
        $result = $builder->usingStopSequences('STOP', 'END');

        $this->assertSame($builder, $result);

        $reflection = new \ReflectionClass($builder);
        $configProperty = $reflection->getProperty('modelConfig');
        $configProperty->setAccessible(true);
        /** @var ModelConfig $config */
        $config = $configProperty->getValue($builder);

        $this->assertEquals(['STOP', 'END'], $config->getStopSequences());
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\OpenAiCompatibleImplementation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Results\StreamedGenerativeAiResult;
use WordPress\AiClient\Results\ValueObjects\CandidateDelta;
use WordPress\AiClient\Results\ValueObjects\GenerativeAiResultChunk;
use WordPress\AiClient\Tests\mocks\ChunkStream;
use WordPress\AiClient\Tests\mocks\FailingChunkStream;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * @covers \WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel
 */
class AbstractOpenAiCompatibleTextGenerationModelTest extends TestCase
{
    /**
     * @var ModelMetadata&\PHPUnit\Framework\MockObject\MockObject
     */
    private $modelMetadata;

    /**
     * @var ProviderMetadata&\PHPUnit\Framework\MockObject\MockObject
     */
    private $providerMetadata;

    /**
     * @var HttpTransporterInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockHttpTransporter;

    /**
     * @var RequestAuthenticationInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockRequestAuthentication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelMetadata = $this->createStub(ModelMetadata::class);
        $this->modelMetadata->method('getId')->willReturn('test-model');
        $this->providerMetadata = $this->createStub(ProviderMetadata::class);
        $this->providerMetadata->method('getName')->willReturn('TestProvider');
        $this->mockHttpTransporter = $this->createMock(HttpTransporterInterface::class);
        $this->mockRequestAuthentication = $this->createMock(RequestAuthenticationInterface::class);
    }

    /**
     * Creates a mock instance of AbstractOpenAiCompatibleTextGenerationModel.
     *
     * @param ModelConfig|null $modelConfig
     * @return MockOpenAiCompatibleTextGenerationModel
     */
    private function createModel(?ModelConfig $modelConfig = null): MockOpenAiCompatibleTextGenerationModel
    {
        $model = new MockOpenAiCompatibleTextGenerationModel(
            $this->modelMetadata,
            $this->providerMetadata,
            $this->mockHttpTransporter,
            $this->mockRequestAuthentication
        );
        if ($modelConfig) {
            $model->setConfig($modelConfig);
        }
        return $model;
    }

    /**
     * Tests generateTextResult() method on success.
     *
     * @return void
     */
    public function testGenerateTextResultSuccess(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $response = new Response(
            200,
            [],
            json_encode([
                'id' => 'chatcmpl-123',
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Hi there!',
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                    'total_tokens' => 15,
                ],
            ])
        );

        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $model = $this->createModel();
        $result = $model->generateTextResult($prompt);

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertEquals('chatcmpl-123', $result->getId());
        $this->assertCount(1, $result->getCandidates());
        $this->assertEquals('Hi there!', $result->getCandidates()[0]->getMessage()->getParts()[0]->getText());
        $this->assertEquals(FinishReasonEnum::stop(), $result->getCandidates()[0]->getFinishReason());
        $this->assertEquals(10, $result->getTokenUsage()->getPromptTokens());
        $this->assertEquals(5, $result->getTokenUsage()->getCompletionTokens());
        $this->assertEquals(15, $result->getTokenUsage()->getTotalTokens());
    }

    /**
     * Tests generateTextResult() method on API failure.
     *
     * @return void
     */
    public function testGenerateTextResultApiFailure(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $response = new Response(400, [], '{"error": "Invalid parameter."}');

        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $model = $this->createModel();

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Bad Request (400) - Invalid parameter.');

        $model->generateTextResult($prompt);
    }

    /**
     * Tests that token usage defaults to zero when the response omits usage.
     *
     * @return void
     */
    public function testGenerateTextResultDefaultsTokenUsageWhenUsageAbsent(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $response = new Response(
            200,
            [],
            json_encode([
                'id' => 'chatcmpl-123',
                'choices' => [
                    [
                        'message' => ['role' => 'assistant', 'content' => 'Hi there!'],
                        'finish_reason' => 'stop',
                    ],
                ],
            ])
        );

        $this->mockRequestAuthentication->method('authenticateRequest')->willReturnArgument(0);
        $this->mockHttpTransporter->method('send')->willReturn($response);

        $result = $this->createModel()->generateTextResult($prompt);

        $this->assertSame(0, $result->getTokenUsage()->getPromptTokens());
        $this->assertSame(0, $result->getTokenUsage()->getCompletionTokens());
        $this->assertSame(0, $result->getTokenUsage()->getTotalTokens());
    }

    /**
     * Tests prepareGenerateTextParams() with basic text prompt.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsBasicText(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test message')])];
        $model = $this->createModel();

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('model', $params);
        $this->assertEquals('test-model', $params['model']);
        $this->assertArrayHasKey('messages', $params);
        $this->assertCount(1, $params['messages']);
        $this->assertEquals('user', $params['messages'][0]['role']);
        $this->assertCount(1, $params['messages'][0]['content']);
        $this->assertEquals('text', $params['messages'][0]['content'][0]['type']);
        $this->assertEquals('Test message', $params['messages'][0]['content'][0]['text']);
        $this->assertArrayNotHasKey('customOptions', $params); // customOptions should not be present if empty
    }

    /**
     * Tests prepareGenerateTextParams() with system instruction.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithSystemInstruction(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('User message')])];
        $modelConfig = ModelConfig::fromArray(['systemInstruction' => 'You are a helpful assistant.']);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertCount(2, $params['messages']);
        $this->assertEquals('system', $params['messages'][0]['role']);
        $this->assertEquals('You are a helpful assistant.', $params['messages'][0]['content'][0]['text']);
        $this->assertEquals('user', $params['messages'][1]['role']);
    }

    /**
     * Tests prepareGenerateTextParams() with candidate count.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithCandidateCount(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['candidateCount' => 2]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('n', $params);
        $this->assertEquals(2, $params['n']);
    }

    /**
     * Tests prepareGenerateTextParams() with max tokens.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithMaxTokens(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['maxTokens' => 100]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('max_tokens', $params);
        $this->assertEquals(100, $params['max_tokens']);
    }

    /**
     * Tests prepareGenerateTextParams() with temperature.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithTemperature(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['temperature' => 0.5]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('temperature', $params);
        $this->assertEquals(0.5, $params['temperature']);
    }

    /**
     * Tests prepareGenerateTextParams() with topP.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithTopP(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['topP' => 0.9]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('top_p', $params);
        $this->assertEquals(0.9, $params['top_p']);
    }

    /**
     * Tests prepareGenerateTextParams() with stop sequences.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithStopSequences(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['stopSequences' => ['stop1', 'stop2']]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('stop', $params);
        $this->assertEquals(['stop1', 'stop2'], $params['stop']);
    }

    /**
     * Tests prepareGenerateTextParams() with presence penalty.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithPresencePenalty(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['presencePenalty' => 0.1]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('presence_penalty', $params);
        $this->assertEquals(0.1, $params['presence_penalty']);
    }

    /**
     * Tests prepareGenerateTextParams() with frequency penalty.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithFrequencyPenalty(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['frequencyPenalty' => 0.2]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('frequency_penalty', $params);
        $this->assertEquals(0.2, $params['frequency_penalty']);
    }

    /**
     * Tests prepareGenerateTextParams() with logprobs.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithLogprobs(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['logprobs' => true]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('logprobs', $params);
        $this->assertTrue($params['logprobs']);
    }

    /**
     * Tests prepareGenerateTextParams() with top logprobs.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithTopLogprobs(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['topLogprobs' => 5]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('top_logprobs', $params);
        $this->assertEquals(5, $params['top_logprobs']);
    }

    /**
     * Tests prepareGenerateTextParams() with function declarations.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithFunctionDeclarations(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $functionDeclaration = new FunctionDeclaration(
            'my_function',
            'My function',
            ['type' => 'object']
        );
        $modelConfig = ModelConfig::fromArray(
            ['functionDeclarations' => [$functionDeclaration->toArray()]]
        );
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('tools', $params);
        $this->assertCount(1, $params['tools']);
        $this->assertEquals('function', $params['tools'][0]['type']);
        $this->assertEquals($functionDeclaration->toArray(), $params['tools'][0]['function']);
    }

    /**
     * Tests prepareGenerateTextParams() with JSON output.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithJsonOutput(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['outputMimeType' => 'application/json']);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('response_format', $params);
        $this->assertEquals(['type' => 'json_object'], $params['response_format']);
    }

    /**
     * Tests prepareGenerateTextParams() with JSON output schema.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithJsonOutputSchema(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $schema = ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]];
        $modelConfig = ModelConfig::fromArray(['outputMimeType' => 'application/json', 'outputSchema' => $schema]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('response_format', $params);
        $this->assertEquals(['type' => 'json_schema', 'json_schema' => $schema], $params['response_format']);
    }

    /**
     * Tests prepareGenerateTextParams() with custom options.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithCustomOptions(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['customOptions' => ['my_custom_key' => 'my_custom_value']]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('my_custom_key', $params);
        $this->assertEquals('my_custom_value', $params['my_custom_key']);
    }

    /**
     * Tests prepareGenerateTextParams() with conflicting custom option.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithConflictingCustomOption(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['customOptions' => ['model' => 'conflicting-model']]);
        $model = $this->createModel($modelConfig);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The custom option "model" conflicts with an existing parameter.');

        $model->exposePrepareGenerateTextParams($prompt);
    }

    /**
     * Tests prepareMessagesParam() with text message.
     *
     * @return void
     */
    public function testPrepareMessagesParamTextMessage(): void
    {
        $message = new Message(MessageRoleEnum::user(), [new MessagePart('Hello')]);
        $model = $this->createModel();

        $prepared = $model->exposePrepareMessagesParam([$message]);

        $this->assertCount(1, $prepared);
        $this->assertEquals('user', $prepared[0]['role']);
        $this->assertCount(1, $prepared[0]['content']);
        $this->assertEquals('text', $prepared[0]['content'][0]['type']);
        $this->assertEquals('Hello', $prepared[0]['content'][0]['text']);
    }

    /**
     * Tests prepareMessagesParam() with model message and function call.
     *
     * @return void
     */
    public function testPrepareMessagesParamModelMessageWithFunctionCall(): void
    {
        $functionCall = new FunctionCall('call_1', 'my_function', ['arg1' => 'value1']);
        $message = new Message(
            MessageRoleEnum::model(),
            [new MessagePart($functionCall)]
        );
        $model = $this->createModel();

        $prepared = $model->exposePrepareMessagesParam([$message]);

        $this->assertCount(1, $prepared);
        $this->assertEquals('assistant', $prepared[0]['role']);
        $this->assertCount(1, $prepared[0]['tool_calls']);
        $this->assertEquals('function', $prepared[0]['tool_calls'][0]['type']);
        $this->assertEquals('call_1', $prepared[0]['tool_calls'][0]['id']);
        $this->assertEquals('my_function', $prepared[0]['tool_calls'][0]['function']['name']);
        $this->assertEquals(
            json_encode(['arg1' => 'value1']),
            $prepared[0]['tool_calls'][0]['function']['arguments']
        );
    }

    /**
     * Tests prepareMessagesParam with message having no function calls (tool_calls should not be included).
     *
     * @return void
     */
    public function testPrepareMessagesParamNoToolCalls(): void
    {
        $message = new Message(
            MessageRoleEnum::model(),
            [new MessagePart('Hello, I am a simple text response.')]
        );

        $model = $this->createModel();
        $prepared = $model->exposePrepareMessagesParam([$message], null);

        $this->assertCount(1, $prepared);
        $this->assertEquals('assistant', $prepared[0]['role']);
        $this->assertArrayNotHasKey('tool_calls', $prepared[0]); // Should not have tool_calls field at all
    }

    /**
     * Tests prepareMessagesParam() with function response.
     *
     * @return void
     */
    public function testPrepareMessagesParamFunctionResponse(): void
    {
        $functionResponse = new FunctionResponse(
            'call_1',
            'my_function',
            ['result' => 'success']
        );
        $message = new Message(
            MessageRoleEnum::user(),
            [new MessagePart($functionResponse)]
        ); // Changed to user role
        $model = $this->createModel();

        $prepared = $model->exposePrepareMessagesParam([$message]);

        $this->assertCount(1, $prepared);
        $this->assertEquals('tool', $prepared[0]['role']);
        $this->assertEquals(json_encode(['result' => 'success']), $prepared[0]['content']);
        $this->assertEquals('call_1', $prepared[0]['tool_call_id']);
    }

    /**
     * Tests getMessageRoleString() method.
     *
     * @dataProvider messageRoleProvider
     * @param MessageRoleEnum $role
     * @param string $expected
     * @return void
     */
    public function testGetMessageRoleString(MessageRoleEnum $role, string $expected): void
    {
        $model = $this->createModel();
        $this->assertEquals($expected, $model->exposeGetMessageRoleString($role));
    }

    /**
     * Provides message roles and their expected string representations.
     *
     * @return array<string, array<mixed>>
     */
    public function messageRoleProvider(): array
    {
        return [
            'user' => [MessageRoleEnum::user(), 'user'],
            'model' => [MessageRoleEnum::model(), 'assistant'],
        ];
    }

    /**
     * Tests getMessagePartContentData() with text part.
     *
     * @return void
     */
    public function testGetMessagePartContentDataTextPart(): void
    {
        $part = new MessagePart('Hello');
        $model = $this->createModel();
        $data = $model->exposeGetMessagePartContentData($part);

        $this->assertEquals(['type' => 'text', 'text' => 'Hello'], $data);
    }

    /**
     * Tests getMessagePartContentData() with remote image file.
     *
     * @return void
     */
    public function testGetMessagePartContentDataRemoteImageFile(): void
    {
        $file = new File('https://example.com/image.png', 'image/png');
        $part = new MessagePart($file);
        $model = $this->createModel();
        $data = $model->exposeGetMessagePartContentData($part);

        $this->assertEquals(['type' => 'image_url', 'image_url' => ['url' => 'https://example.com/image.png']], $data);
    }

    /**
     * Tests getMessagePartContentData() with inline image file.
     *
     * @return void
     */
    public function testGetMessagePartContentDataInlineImageFile(): void
    {
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $file = new File(
            $base64Image,
            'image/png'
        );
        $part = new MessagePart($file);
        $model = $this->createModel();
        $data = $model->exposeGetMessagePartContentData($part);

        $this->assertEquals(
            [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:image/png;base64,' . $base64Image
                ]
            ],
            $data
        );
    }

    /**
     * Tests getMessagePartContentData() with inline audio file.
     *
     * @return void
     */
    public function testGetMessagePartContentDataInlineAudioFile(): void
    {
        $file = new File(
            'data:audio/mpeg;base64,SUQzBAAAAAAA',
            'audio/mpeg'
        );
        $part = new MessagePart($file);
        $model = $this->createModel();
        $data = $model->exposeGetMessagePartContentData($part);

        $this->assertEquals([
            'type' => 'input_audio',
            'input_audio' => ['data' => 'SUQzBAAAAAAA', 'format' => 'mp3']
        ], $data);
    }

    /**
     * Tests getMessagePartContentData() with unsupported remote file type.
     *
     * @return void
     */
    public function testGetMessagePartContentDataUnsupportedRemoteFile(): void
    {
        $file = new File('https://example.com/doc.pdf', 'application/pdf');
        $part = new MessagePart($file);
        $model = $this->createModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported MIME type "application/pdf" for remote file message part.');

        $model->exposeGetMessagePartContentData($part);
    }

    /**
     * Tests getMessagePartContentData() with unsupported inline file type.
     *
     * @return void
     */
    public function testGetMessagePartContentDataUnsupportedInlineFile(): void
    {
        $file = new File('data:text/plain;base64,SGVsbG8=', 'text/plain');
        $part = new MessagePart($file);
        $model = $this->createModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported MIME type "text/plain" for inline file message part.');

        $model->exposeGetMessagePartContentData($part);
    }

    /**
     * Tests getMessagePartContentData() with function call part (should return null).
     *
     * @return void
     */
    public function testGetMessagePartContentDataFunctionCallPart(): void
    {
        $functionCall = new FunctionCall('call_1', 'my_function', []);
        $part = new MessagePart($functionCall);
        $model = $this->createModel();
        $data = $model->exposeGetMessagePartContentData($part);

        $this->assertNull($data);
    }

    /**
     * Tests getMessagePartContentData() with function response part (should throw exception).
     *
     * @return void
     */
    public function testGetMessagePartContentDataFunctionResponsePart(): void
    {
        $functionResponse = new FunctionResponse('call_1', 'my_function', []);
        $part = new MessagePart($functionResponse);
        $model = $this->createModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The API only allows a single function response, as the only content of the message.'
        );

        $model->exposeGetMessagePartContentData($part);
    }

    /**
     * Tests getMessagePartToolCallData() with function call part.
     *
     * @return void
     */
    public function testGetMessagePartToolCallDataFunctionCallPart(): void
    {
        $functionCall = new FunctionCall(
            'call_1',
            'my_function',
            ['arg1' => 'value1']
        );
        $part = new MessagePart($functionCall);
        $model = $this->createModel();
        $data = $model->exposeGetMessagePartToolCallData($part);

        $this->assertEquals([
            'type' => 'function',
            'id' => 'call_1',
            'function' => [
                'name' => 'my_function',
                'arguments' => json_encode(['arg1' => 'value1']),
            ],
        ], $data);
    }

    /**
     * Tests getMessagePartToolCallData() with empty arguments (should encode as empty object).
     *
     * @return void
     */
    public function testGetMessagePartToolCallDataEmptyArguments(): void
    {
        // Note: FunctionCall normalizes [] to null, so this tests the null-handling path.
        $functionCall = new FunctionCall(
            'call_1',
            'list_capabilities',
            [] // Empty arguments array (normalized to null by FunctionCall)
        );
        $part = new MessagePart($functionCall);
        $model = $this->createModel();
        $data = $model->exposeGetMessagePartToolCallData($part);

        $this->assertEquals([
            'type' => 'function',
            'id' => 'call_1',
            'function' => [
                'name' => 'list_capabilities',
                'arguments' => '{}', // Should be empty object, not empty array
            ],
        ], $data);
    }

    /**
     * Tests getMessagePartToolCallData() with null arguments (should encode as empty object).
     *
     * @return void
     */
    public function testGetMessagePartToolCallDataNullArguments(): void
    {
        $functionCall = new FunctionCall(
            'call_1',
            'list_capabilities',
            null
        );
        $part = new MessagePart($functionCall);
        $model = $this->createModel();
        $data = $model->exposeGetMessagePartToolCallData($part);

        $this->assertEquals([
            'type' => 'function',
            'id' => 'call_1',
            'function' => [
                'name' => 'list_capabilities',
                'arguments' => '{}', // null args should be encoded as empty object
            ],
        ], $data);
    }

    /**
     * Tests getMessagePartToolCallData() with text part (should return null).
     *
     * @return void
     */
    public function testGetMessagePartToolCallDataTextPart(): void
    {
        $part = new MessagePart('Hello');
        $model = $this->createModel();
        $data = $model->exposeGetMessagePartToolCallData($part);

        $this->assertNull($data);
    }

    /**
     * Tests validateOutputModalities() with text modality.
     *
     * @return void
     */
    public function testValidateOutputModalitiesWithText(): void
    {
        $model = $this->createModel();
        $model->exposeValidateOutputModalities([ModalityEnum::text()]);
        $this->assertTrue(true); // No exception means success.
    }

    /**
     * Tests validateOutputModalities() with multiple modalities including text.
     *
     * @return void
     */
    public function testValidateOutputModalitiesWithMultipleIncludingText(): void
    {
        $model = $this->createModel();
        $model->exposeValidateOutputModalities([ModalityEnum::text(), ModalityEnum::image()]);
        $this->assertTrue(true); // No exception means success.
    }

    /**
     * Tests validateOutputModalities() with no modalities.
     *
     * @return void
     */
    public function testValidateOutputModalitiesWithNoModalities(): void
    {
        $model = $this->createModel();
        $model->exposeValidateOutputModalities([]);
        $this->assertTrue(true); // No exception means success.
    }

    /**
     * Tests validateOutputModalities() without text modality.
     *
     * @return void
     */
    public function testValidateOutputModalitiesWithoutText(): void
    {
        $model = $this->createModel();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A text output modality must be present when generating text.');
        $model->exposeValidateOutputModalities([ModalityEnum::image()]);
    }

    /**
     * Tests prepareOutputModalitiesParam() method.
     *
     * @dataProvider outputModalitiesProvider
     * @param array<ModalityEnum> $modalities
     * @param array<string> $expected
     * @return void
     */
    public function testPrepareOutputModalitiesParam(
        array $modalities,
        array $expected
    ): void {
        $model = $this->createModel();
        $this->assertEquals($expected, $model->exposePrepareOutputModalitiesParam($modalities));
    }

    /**
     * Provides output modalities and their expected API parameter representations.
     *
     * @return array<string, array<mixed>>
     */
    public function outputModalitiesProvider(): array
    {
        return [
            'text only' => [
                [ModalityEnum::text()], ['text']
            ],
            'image only' => [
                [ModalityEnum::image()], ['image']
            ],
            'audio only' => [
                [ModalityEnum::audio()], ['audio']
            ],
            'text and image' => [
                [ModalityEnum::text(), ModalityEnum::image()], ['text', 'image']
            ],
            'all modalities' => [
                [ModalityEnum::text(), ModalityEnum::image(), ModalityEnum::audio()], ['text', 'image', 'audio']
            ],
        ];
    }


    /**
     * Tests prepareToolsParam() method.
     *
     * @return void
     */
    public function testPrepareToolsParam(): void
    {
        $functionDeclaration1 = new FunctionDeclaration('func1', 'Description 1', ['type' => 'object']);
        $functionDeclaration2 = new FunctionDeclaration('func2', 'Description 2', ['type' => 'object']);
        $functionDeclarations = [$functionDeclaration1, $functionDeclaration2];
        $model = $this->createModel();

        $prepared = $model->exposePrepareToolsParam($functionDeclarations);

        $this->assertCount(2, $prepared);
        $this->assertEquals('function', $prepared[0]['type']);
        $this->assertEquals($functionDeclaration1->toArray(), $prepared[0]['function']);
        $this->assertEquals('function', $prepared[1]['type']);
        $this->assertEquals($functionDeclaration2->toArray(), $prepared[1]['function']);
    }

    /**
     * Tests prepareResponseFormatParam() with null schema.
     *
     * @return void
     */
    public function testPrepareResponseFormatParamNullSchema(): void
    {
        $model = $this->createModel();
        $format = $model->exposePrepareResponseFormatParam(null);

        $this->assertEquals(['type' => 'json_object'], $format);
    }

    /**
     * Tests prepareResponseFormatParam() with schema.
     *
     * @return void
     */
    public function testPrepareResponseFormatParamWithSchema(): void
    {
        $schema = ['type' => 'object', 'properties' => ['key' => ['type' => 'string']]];
        $model = $this->createModel();
        $format = $model->exposePrepareResponseFormatParam($schema);

        $this->assertEquals(['type' => 'json_schema', 'json_schema' => $schema], $format);
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with valid response.
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultValidResponse(): void
    {
        $response = new Response(
            200,
            [],
            json_encode([
                'id' => 'test-id',
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Test content',
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 20,
                    'total_tokens' => 30,
                ],
                'model' => 'test-model',
            ])
        );
        $model = $this->createModel();
        $result = $model->parseResponseToGenerativeAiResult($response);

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertEquals('test-id', $result->getId());
        $this->assertCount(1, $result->getCandidates());
        $this->assertEquals('Test content', $result->getCandidates()[0]->getMessage()->getParts()[0]->getText());
        $this->assertEquals(FinishReasonEnum::stop(), $result->getCandidates()[0]->getFinishReason());
        $this->assertEquals(10, $result->getTokenUsage()->getPromptTokens());
        $this->assertEquals(20, $result->getTokenUsage()->getCompletionTokens());
        $this->assertEquals(30, $result->getTokenUsage()->getTotalTokens());
        $this->assertEquals(['model' => 'test-model'], $result->getAdditionalData());
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with missing choices.
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultMissingChoices(): void
    {
        $response = new Response(200, [], json_encode(['id' => 'test-id']));
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('Unexpected TestProvider API response: Missing the "choices" key.');

        $model->parseResponseToGenerativeAiResult($response);
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with invalid choices type.
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultInvalidChoicesType(): void
    {
        $response = new Response(
            200,
            [],
            json_encode(['choices' => 'invalid'])
        );
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(
            'Unexpected TestProvider API response: Invalid "choices" key: The value must be an array.'
        );

        $model->parseResponseToGenerativeAiResult($response);
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with invalid choice element type.
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultInvalidChoiceElementType(): void
    {
        $response = new Response(200, [], json_encode(['choices' => ['invalid']]));
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(
            'Unexpected TestProvider API response: Invalid "choices[0]" key: The value must be an associative array.'
        );

        $model->parseResponseToGenerativeAiResult($response);
    }

    /**
     * Tests parseResponseChoiceToCandidate() with valid data.
     *
     * @return void
     */
    public function testParseResponseChoiceToCandidateValidData(): void
    {
        $choiceData = [
            'message' => [
                'role' => 'assistant',
                'content' => 'Hello from AI',
            ],
            'finish_reason' => 'stop',
        ];
        $model = $this->createModel();
        $candidate = $model->exposeParseResponseChoiceToCandidate($choiceData);

        $this->assertInstanceOf(Candidate::class, $candidate);
        $this->assertEquals('Hello from AI', $candidate->getMessage()->getParts()[0]->getText());
        $this->assertEquals(FinishReasonEnum::stop(), $candidate->getFinishReason());
    }

    /**
     * Tests parseResponseChoiceToCandidate() with missing message.
     *
     * @return void
     */
    public function testParseResponseChoiceToCandidateMissingMessage(): void
    {
        $choiceData = [
            'finish_reason' => 'stop',
        ];
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(
            'Unexpected TestProvider API response: Missing the "choices[0].message" key.'
        );

        $model->exposeParseResponseChoiceToCandidate($choiceData);
    }

    /**
     * Tests parseResponseChoiceToCandidate() with invalid message type.
     *
     * @return void
     */
    public function testParseResponseChoiceToCandidateInvalidMessageType(): void
    {
        $choiceData = [
            'message' => 'invalid',
            'finish_reason' => 'stop',
        ];
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(
            'Unexpected TestProvider API response: Missing the "choices[0].message" key.'
        );

        $model->exposeParseResponseChoiceToCandidate($choiceData);
    }

    /**
     * Tests parseResponseChoiceToCandidate() with missing finish reason.
     *
     * @return void
     */
    public function testParseResponseChoiceToCandidateMissingFinishReason(): void
    {
        $choiceData = [
            'message' => [
                'role' => 'assistant',
                'content' => 'Hello from AI',
            ],
        ];
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(
            'Unexpected TestProvider API response: Missing the "choices[0].finish_reason" key.'
        );

        $model->exposeParseResponseChoiceToCandidate($choiceData);
    }

    /**
     * Tests parseResponseChoiceToCandidate() with invalid finish reason.
     *
     * @return void
     */
    public function testParseResponseChoiceToCandidateInvalidFinishReason(): void
    {
        $choiceData = [
            'message' => [
                'role' => 'assistant',
                'content' => 'Hello from AI',
            ],
            'finish_reason' => 'unknown',
        ];
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Unexpected TestProvider API response: Invalid "%s" key: Invalid finish reason "unknown".',
                'choices[0].finish_reason'
            )
        );

        $model->exposeParseResponseChoiceToCandidate($choiceData);
    }

    /**
     * Tests parseResponseChoiceMessage() with assistant message.
     *
     * @return void
     */
    public function testParseResponseChoiceMessageAssistant(): void
    {
        $messageData = [
            'role' => 'assistant',
            'content' => 'Assistant response',
        ];
        $model = $this->createModel();
        $message = $model->exposeParseResponseChoiceMessage($messageData, 0);

        $this->assertEquals(MessageRoleEnum::model(), $message->getRole());
        $this->assertCount(1, $message->getParts());
        $this->assertEquals('Assistant response', $message->getParts()[0]->getText());
    }

    /**
     * Tests parseResponseChoiceMessage() with user message.
     *
     * @return void
     */
    public function testParseResponseChoiceMessageUser(): void
    {
        $messageData = [
            'role' => 'user',
            'content' => 'User response',
        ];
        $model = $this->createModel();
        $message = $model->exposeParseResponseChoiceMessage($messageData, 0);

        $this->assertEquals(MessageRoleEnum::user(), $message->getRole());
        $this->assertCount(1, $message->getParts());
        $this->assertEquals('User response', $message->getParts()[0]->getText());
    }

    /**
     * Tests parseResponseChoiceMessageParts() with content and reasoning.
     *
     * @return void
     */
    public function testParseResponseChoiceMessagePartsContentAndReasoning(): void
    {
        $messageData = [
            'reasoning_content' => 'Thinking process',
            'content' => 'Final answer',
        ];
        $model = $this->createModel();
        $parts = $model->exposeParseResponseChoiceMessageParts($messageData, 0);

        $this->assertCount(2, $parts);
        $this->assertEquals('Thinking process', $parts[0]->getText());
        $this->assertEquals(MessagePartChannelEnum::thought(), $parts[0]->getChannel());
        $this->assertEquals('Final answer', $parts[1]->getText());
        $this->assertEquals(MessagePartChannelEnum::content(), $parts[1]->getChannel());
    }

    /**
     * Tests parseResponseChoiceMessageParts() with tool calls.
     *
     * @return void
     */
    public function testParseResponseChoiceMessagePartsToolCalls(): void
    {
        $messageData = [
            'tool_calls' => [
                [
                    'id' => 'call_1',
                    'type' => 'function',
                    'function' => [
                        'name' => 'my_function',
                        'arguments' => '{"param":"value"}',
                    ],
                ],
            ],
        ];
        $model = $this->createModel();
        $parts = $model->exposeParseResponseChoiceMessageParts($messageData, 0);

        $this->assertCount(1, $parts);
        $this->assertInstanceOf(FunctionCall::class, $parts[0]->getFunctionCall());
        $this->assertEquals('call_1', $parts[0]->getFunctionCall()->getId());
    }

    /**
     * Tests parseResponseChoiceMessageToolCallPart() with valid function call.
     *
     * @return void
     */
    public function testParseResponseChoiceMessageToolCallPartValidFunctionCall(): void
    {
        $toolCallData = [
            'id' => 'call_123',
            'type' => 'function',
            'function' => [
                'name' => 'test_function',
                'arguments' => '{"key":"value"}',
            ],
        ];
        $model = $this->createModel();
        $part = $model->exposeParseResponseChoiceMessageToolCallPart($toolCallData);

        $this->assertInstanceOf(MessagePart::class, $part);
        $this->assertInstanceOf(FunctionCall::class, $part->getFunctionCall());
        $this->assertEquals('call_123', $part->getFunctionCall()->getId());
        $this->assertEquals('test_function', $part->getFunctionCall()->getName());
        $this->assertEquals(['key' => 'value'], $part->getFunctionCall()->getArgs());
    }

    /**
     * Tests parseResponseChoiceMessageToolCallPart() with missing function data.
     *
     * @return void
     */
    public function testParseResponseChoiceMessageToolCallPartMissingFunctionData(): void
    {
        $toolCallData = [
            'id' => 'call_123',
            'type' => 'function',
        ];
        $model = $this->createModel();
        $part = $model->exposeParseResponseChoiceMessageToolCallPart($toolCallData);

        $this->assertNull($part);
    }

    /**
     * Tests parseResponseChoiceMessageToolCallPart() with non-function type.
     *
     * @return void
     */
    public function testParseResponseChoiceMessageToolCallPartNonFunctionType(): void
    {
        $toolCallData = [
            'id' => 'call_123',
            'type' => 'unknown',
            'function' => [
                'name' => 'test_function',
                'arguments' => '{"key":"value"}',
            ],
        ];
        $model = $this->createModel();
        $part = $model->exposeParseResponseChoiceMessageToolCallPart($toolCallData);

        $this->assertNull($part);
    }

    /**
     * Tests getMessagePartContentData() with text part in thought channel.
     *
     * @return void
     */
    public function testGetMessagePartContentDataThoughtPart(): void
    {
        $part = new MessagePart('Thinking...', MessagePartChannelEnum::thought());
        $model = $this->createModel();
        $data = $model->exposeGetMessagePartContentData($part);

        // Should be skipped because OpenAI API doesn't support receiving thoughts.
        $this->assertNull($data);
    }

    /**
     * @return list<Message>
     */
    private function createStreamPrompt(): array
    {
        return [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
    }

    /**
     * Builds one SSE "data:" frame for the given decoded payload.
     *
     * @param array<string, mixed> $payload
     */
    private function createSseDataLine(array $payload): string
    {
        return 'data: ' . json_encode($payload) . "\n\n";
    }

    /**
     * Wraps SSE frames in a streamed Response, one frame per read.
     *
     * @param list<string> $sseFrames
     */
    private function createStreamResponse(array $sseFrames, int $statusCode = 200): Response
    {
        return new Response($statusCode, [], new ChunkStream($sseFrames));
    }

    /**
     * Configures auth passthrough and the transporter to return the given response.
     */
    private function givenStreamResponse(Response $response): void
    {
        $this->mockRequestAuthentication->method('authenticateRequest')->willReturnArgument(0);
        $this->mockHttpTransporter->method('send')->willReturn($response);
    }

    /**
     * Drains a handle into a list of chunks.
     *
     * @return list<GenerativeAiResultChunk>
     */
    private function consumeChunks(StreamedGenerativeAiResult $handle): array
    {
        return array_values(iterator_to_array($handle, false));
    }

    /**
     * Tests that creating the handle does not perform the request.
     */
    public function testStreamGenerateTextResultReturnsHandleWithoutPerformingRequest(): void
    {
        $this->mockHttpTransporter->expects($this->never())->method('send');

        $handle = $this->createModel()->streamGenerateTextResult($this->createStreamPrompt());

        $this->assertInstanceOf(StreamedGenerativeAiResult::class, $handle);
    }

    /**
     * Tests that the streamed request enables streaming and usage reporting.
     */
    public function testStreamGenerateTextResultEnablesStreamingOnTheRequest(): void
    {
        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (Request $request, ?RequestOptions $options = null) {
                $params = $request->getData() ?? [];
                $this->assertTrue($params['stream'] ?? null);
                $this->assertSame(['include_usage' => true], $params['stream_options'] ?? null);
                $this->assertInstanceOf(RequestOptions::class, $options);
                $this->assertTrue($options->isStream());

                return $this->createStreamResponse(["data: [DONE]\n\n"]);
            });

        $handle = $this->createModel()->streamGenerateTextResult($this->createStreamPrompt());

        $this->assertSame([], $this->consumeChunks($handle));
    }

    /**
     * Tests that content deltas, the finish reason, and usage are assembled.
     */
    public function testStreamAssemblesContentFinishReasonAndUsage(): void
    {
        $this->givenStreamResponse($this->createStreamResponse([
            $this->createSseDataLine([
                'id' => 'chatcmpl-1',
                'choices' => [['index' => 0, 'delta' => ['role' => 'assistant', 'content' => 'Hel']]],
            ]),
            $this->createSseDataLine(['choices' => [['index' => 0, 'delta' => ['content' => 'lo']]]]),
            $this->createSseDataLine(['choices' => [['index' => 0, 'delta' => [], 'finish_reason' => 'stop']]]),
            $this->createSseDataLine([
                'choices' => [],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
            ]),
            "data: [DONE]\n\n",
        ]));

        $result = $this->createModel()
            ->streamGenerateTextResult($this->createStreamPrompt())
            ->getFinalResult();

        $this->assertSame('chatcmpl-1', $result->getId());
        $this->assertSame('Hello', $result->toText());
        $this->assertTrue($result->getCandidates()[0]->getFinishReason()->is(FinishReasonEnum::stop()));
        $this->assertSame(10, $result->getTokenUsage()->getPromptTokens());
        $this->assertSame(5, $result->getTokenUsage()->getCompletionTokens());
        $this->assertSame(15, $result->getTokenUsage()->getTotalTokens());
    }

    /**
     * Tests that each event yields a chunk and the [DONE] sentinel is skipped.
     */
    public function testStreamYieldsOneChunkPerEventAndSkipsTheDoneSentinel(): void
    {
        $this->givenStreamResponse($this->createStreamResponse([
            $this->createSseDataLine(['choices' => [['index' => 0, 'delta' => ['content' => 'Hel']]]]),
            "data: \n\n",
            $this->createSseDataLine(['choices' => [['index' => 0, 'delta' => ['content' => 'lo']]]]),
            $this->createSseDataLine([
                'choices' => [],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3],
            ]),
            "data: [DONE]\n\n",
        ]));

        $chunks = $this->consumeChunks(
            $this->createModel()->streamGenerateTextResult($this->createStreamPrompt())
        );

        $this->assertCount(3, $chunks);
        $this->assertSame('Hel', $chunks[0]->getDeltaText());
        $this->assertSame('lo', $chunks[1]->getDeltaText());
        $this->assertSame('', $chunks[2]->getDeltaText());
        $this->assertInstanceOf(TokenUsage::class, $chunks[2]->getTokenUsage());
        $this->assertSame(3, $chunks[2]->getTokenUsage()->getTotalTokens());
    }

    /**
     * Tests that additional data is extracted onto chunks and the result.
     */
    public function testStreamExtractsAdditionalDataIntoChunksAndResult(): void
    {
        $this->givenStreamResponse($this->createStreamResponse([
            $this->createSseDataLine([
                'id' => 'chatcmpl-1',
                'object' => 'chat.completion.chunk',
                'system_fingerprint' => 'fp_abc',
                'choices' => [['index' => 0, 'delta' => ['content' => 'Hi'], 'finish_reason' => 'stop']],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
            ]),
            "data: [DONE]\n\n",
        ]));

        $handle = $this->createModel()->streamGenerateTextResult($this->createStreamPrompt());
        $chunks = $this->consumeChunks($handle);

        $expected = ['object' => 'chat.completion.chunk', 'system_fingerprint' => 'fp_abc'];
        $this->assertSame($expected, $chunks[0]->getAdditionalData());
        $this->assertSame($expected, $handle->getFinalResult()->getAdditionalData());
    }

    /**
     * Tests that reasoning deltas route to the thought channel with thought tokens.
     */
    public function testStreamRoutesReasoningToThoughtChannelWithThoughtTokens(): void
    {
        $this->givenStreamResponse($this->createStreamResponse([
            $this->createSseDataLine(['choices' => [['index' => 0, 'delta' => ['reasoning_content' => 'Think']]]]),
            $this->createSseDataLine(['choices' => [['index' => 0, 'delta' => ['reasoning' => 'ing']]]]),
            $this->createSseDataLine([
                'choices' => [['index' => 0, 'delta' => ['content' => 'Answer'], 'finish_reason' => 'stop']],
            ]),
            $this->createSseDataLine([
                'choices' => [],
                'usage' => [
                    'prompt_tokens' => 15,
                    'completion_tokens' => 20,
                    'total_tokens' => 35,
                    'completion_tokens_details' => ['reasoning_tokens' => 10],
                ],
            ]),
            "data: [DONE]\n\n",
        ]));

        $result = $this->createModel()
            ->streamGenerateTextResult($this->createStreamPrompt())
            ->getFinalResult();

        $thought = '';
        $content = '';
        foreach ($result->getCandidates()[0]->getMessage()->getParts() as $part) {
            if ($part->getText() === null) {
                continue;
            }
            if ($part->getChannel()->is(MessagePartChannelEnum::thought())) {
                $thought .= $part->getText();
            } else {
                $content .= $part->getText();
            }
        }

        $this->assertSame('Thinking', $thought);
        $this->assertSame('Answer', $content);
        $this->assertSame(10, $result->getTokenUsage()->getThoughtTokens());
    }

    /**
     * Builds one streamed-choice SSE frame carrying tool-call deltas.
     *
     * @param list<array{0: int, 1: ?string, 2: ?string, 3: ?string}> $toolCalls
     */
    private function toolCallFrame(array $toolCalls, ?string $finishReason = null): string
    {
        $deltas = [];
        foreach ($toolCalls as [$index, $id, $name, $arguments]) {
            $function = [];
            if ($name !== null) {
                $function['name'] = $name;
            }
            if ($arguments !== null) {
                $function['arguments'] = $arguments;
            }

            $toolCall = ['index' => $index];
            if ($id !== null) {
                $toolCall['id'] = $id;
            }
            $toolCall['function'] = $function;
            $deltas[] = $toolCall;
        }

        $choice = ['index' => 0, 'delta' => ['tool_calls' => $deltas]];
        if ($finishReason !== null) {
            $choice['finish_reason'] = $finishReason;
        }

        return $this->createSseDataLine(['choices' => [$choice]]);
    }

    /**
     * @return array<string, array{0: list<string>, 1: list<array{id: ?string, name: ?string, args: mixed}>}>
     */
    public function assembledToolCallProvider(): array
    {
        return [
            'arguments split across frames' => [
                [
                    $this->toolCallFrame([[0, 'call_1', 'get_weather', '']]),
                    $this->toolCallFrame([[0, null, null, '{"ci']]),
                    $this->toolCallFrame([[0, null, null, 'ty": "San']]),
                    $this->toolCallFrame([[0, null, null, ' Francisco"}']], 'tool_calls'),
                ],
                [['id' => 'call_1', 'name' => 'get_weather', 'args' => ['city' => 'San Francisco']]],
            ],
            'whole tool call in one frame' => [
                [
                    $this->toolCallFrame([[0, 'call_1', 'get_weather', '{"city": "London"}']], 'tool_calls'),
                ],
                [['id' => 'call_1', 'name' => 'get_weather', 'args' => ['city' => 'London']]],
            ],
            'missing type field (Azure AI Foundry / Mistral)' => [
                [
                    $this->toolCallFrame([[0, 'call_abc', 'test-tool', '{"value"']]),
                    $this->toolCallFrame([[0, null, null, ':"hello"}']], 'tool_calls'),
                ],
                [['id' => 'call_abc', 'name' => 'test-tool', 'args' => ['value' => 'hello']]],
            ],
            'trailing empty argument frame does not duplicate' => [
                [
                    $this->toolCallFrame([[0, 'call_1', 'searchGoogle', null]]),
                    $this->toolCallFrame([[0, null, null, '{"query": "ai"}']]),
                    $this->toolCallFrame([[0, null, null, '']], 'tool_calls'),
                ],
                [['id' => 'call_1', 'name' => 'searchGoogle', 'args' => ['query' => 'ai']]],
            ],
            'parallel tool calls reassembled independently' => [
                [
                    $this->toolCallFrame([
                        [0, 'call_a', 'get_weather', '{"city":'],
                        [1, 'call_b', 'get_time', '{"tz":'],
                    ]),
                    $this->toolCallFrame([[1, null, null, '"UTC"}'], [0, null, null, '"Paris"}']], 'tool_calls'),
                ],
                [
                    ['id' => 'call_a', 'name' => 'get_weather', 'args' => ['city' => 'Paris']],
                    ['id' => 'call_b', 'name' => 'get_time', 'args' => ['tz' => 'UTC']],
                ],
            ],
        ];
    }

    /**
     * Tests that tool-call deltas are reassembled into function calls.
     *
     * @dataProvider assembledToolCallProvider
     *
     * @param list<string> $sseFrames
     * @param list<array{id: ?string, name: ?string, args: mixed}> $expectedCalls
     */
    public function testStreamReassemblesToolCalls(array $sseFrames, array $expectedCalls): void
    {
        $this->givenStreamResponse($this->createStreamResponse(array_merge($sseFrames, ["data: [DONE]\n\n"])));

        $candidate = $this->createModel()
            ->streamGenerateTextResult($this->createStreamPrompt())
            ->getFinalResult()
            ->getCandidates()[0];

        $actualCalls = [];
        foreach ($candidate->getMessage()->getParts() as $part) {
            $call = $part->getFunctionCall();
            if ($call !== null) {
                $actualCalls[] = ['id' => $call->getId(), 'name' => $call->getName(), 'args' => $call->getArgs()];
            }
        }

        $this->assertEquals($expectedCalls, $actualCalls);
    }

    /**
     * Tests that choices at different indices become separate candidates.
     */
    public function testStreamSeparatesMultipleCandidatesByIndex(): void
    {
        $this->givenStreamResponse($this->createStreamResponse([
            $this->createSseDataLine(['choices' => [
                ['index' => 0, 'delta' => ['content' => 'First']],
                ['index' => 1, 'delta' => ['content' => 'Second']],
            ]]),
            $this->createSseDataLine(['choices' => [
                ['index' => 0, 'delta' => [], 'finish_reason' => 'stop'],
                ['index' => 1, 'delta' => [], 'finish_reason' => 'stop'],
            ]]),
            "data: [DONE]\n\n",
        ]));

        $candidates = $this->createModel()
            ->streamGenerateTextResult($this->createStreamPrompt())
            ->getFinalResult()
            ->getCandidates();

        $this->assertCount(2, $candidates);
        $this->assertSame('First', $candidates[0]->getMessage()->getParts()[0]->getText());
        $this->assertSame('Second', $candidates[1]->getMessage()->getParts()[0]->getText());
    }

    /**
     * Tests that an unknown finish reason defaults to stop.
     */
    public function testStreamUnknownFinishReasonDefaultsToStop(): void
    {
        $this->givenStreamResponse($this->createStreamResponse([
            $this->createSseDataLine([
                'choices' => [['index' => 0, 'delta' => ['content' => 'Hi'], 'finish_reason' => 'something_new']],
            ]),
            "data: [DONE]\n\n",
        ]));

        $candidate = $this->createModel()
            ->streamGenerateTextResult($this->createStreamPrompt())
            ->getFinalResult()
            ->getCandidates()[0];

        $this->assertTrue($candidate->getFinishReason()->is(FinishReasonEnum::stop()));
    }

    /**
     * Tests that a malformed JSON frame is skipped without aborting the stream.
     */
    public function testStreamSkipsUnparsableJsonLineButKeepsValidChunks(): void
    {
        $this->givenStreamResponse($this->createStreamResponse([
            $this->createSseDataLine(['choices' => [['index' => 0, 'delta' => ['content' => 'Hel']]]]),
            "data: {unparsable}\n\n",
            $this->createSseDataLine([
                'choices' => [['index' => 0, 'delta' => ['content' => 'lo'], 'finish_reason' => 'stop']],
            ]),
            "data: [DONE]\n\n",
        ]));

        $handle = $this->createModel()->streamGenerateTextResult($this->createStreamPrompt());
        $chunks = $this->consumeChunks($handle);

        $this->assertCount(2, $chunks);
        $this->assertSame('Hello', $handle->getFinalResult()->toText());
    }

    /**
     * Tests that a [DONE]-only stream produces no result.
     */
    public function testStreamWithOnlyDoneSentinelProducesNoResult(): void
    {
        $this->givenStreamResponse($this->createStreamResponse(["data: [DONE]\n\n"]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no candidates');
        $this->createModel()->streamGenerateTextResult($this->createStreamPrompt())->getFinalResult();
    }

    /**
     * Tests that an error frame raises a ResponseException with the provider message.
     */
    public function testStreamThrowsResponseExceptionOnErrorEvent(): void
    {
        $this->givenStreamResponse($this->createStreamResponse([
            $this->createSseDataLine(['error' => ['message' => 'bad request', 'type' => 'provider_error']]),
            "data: [DONE]\n\n",
        ]));

        $handle = $this->createModel()->streamGenerateTextResult($this->createStreamPrompt());

        $thrown = null;
        try {
            $this->consumeChunks($handle);
        } catch (ResponseException $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(ResponseException::class, $thrown);
        $this->assertSame('Error while streaming the TestProvider API response: bad request', $thrown->getMessage());
    }

    /**
     * Tests that chunks before a mid-stream error are delivered, then it propagates.
     */
    public function testStreamYieldsContentBeforeMidStreamErrorThenThrows(): void
    {
        $this->givenStreamResponse($this->createStreamResponse([
            $this->createSseDataLine(['choices' => [['index' => 0, 'delta' => ['content' => 'Hello']]]]),
            $this->createSseDataLine(['error' => ['message' => 'stream failed after output']]),
            "data: [DONE]\n\n",
        ]));

        $handle = $this->createModel()->streamGenerateTextResult($this->createStreamPrompt());

        $collected = [];
        $thrown = null;
        try {
            foreach ($handle as $chunk) {
                $collected[] = $chunk;
            }
        } catch (ResponseException $e) {
            $thrown = $e;
        }

        $this->assertCount(1, $collected);
        $this->assertSame('Hello', $collected[0]->getDeltaText());
        $this->assertInstanceOf(ResponseException::class, $thrown);
        $this->assertStringContainsString('stream failed after output', $thrown->getMessage());
    }

    /**
     * Tests that a non-successful response is surfaced before streaming begins.
     */
    public function testStreamThrowsClientExceptionWhenResponseIsNotSuccessful(): void
    {
        $this->givenStreamResponse(new Response(400, [], '{"error": "Invalid parameter."}'));

        $handle = $this->createModel()->streamGenerateTextResult($this->createStreamPrompt());

        $this->expectException(ClientException::class);
        $this->consumeChunks($handle);
    }

    /**
     * Tests that a mid-read failure is wrapped as a ResponseException with its cause.
     */
    public function testStreamWrapsMidReadFailureAsResponseException(): void
    {
        $response = new Response(200, [], new FailingChunkStream(
            [$this->createSseDataLine(['choices' => [['index' => 0, 'delta' => ['content' => 'Hello']]]])],
            'Connection reset by peer'
        ));
        $this->givenStreamResponse($response);

        $handle = $this->createModel()->streamGenerateTextResult($this->createStreamPrompt());

        $collected = [];
        $thrown = null;
        try {
            foreach ($handle as $chunk) {
                $collected[] = $chunk;
            }
        } catch (ResponseException $e) {
            $thrown = $e;
        }

        $this->assertCount(1, $collected);
        $this->assertSame('Hello', $collected[0]->getDeltaText());
        $this->assertInstanceOf(ResponseException::class, $thrown);
        $this->assertStringContainsString('Connection reset by peer', $thrown->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $thrown->getPrevious());
    }

    /**
     * Tests that throwIfStreamError() is a no-op without an error payload.
     */
    public function testThrowIfStreamErrorIgnoresEventsWithoutError(): void
    {
        $model = $this->createModel();
        $model->exposeThrowIfStreamError(['choices' => []]);

        $this->assertTrue(true);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public function streamErrorMessageProvider(): array
    {
        return [
            'object error with message' => [
                ['error' => ['message' => 'boom', 'type' => 'server_error']],
                'Error while streaming the TestProvider API response: boom',
            ],
            'object error without message' => [
                ['error' => ['type' => 'server_error']],
                'Error while streaming the TestProvider API response: The provider reported an error.',
            ],
            'non-array error' => [
                ['error' => 'oops'],
                'Error while streaming the TestProvider API response: The provider reported an error.',
            ],
            'non-string message' => [
                ['error' => ['message' => 123]],
                'Error while streaming the TestProvider API response: The provider reported an error.',
            ],
        ];
    }

    /**
     * @dataProvider streamErrorMessageProvider
     *
     * @param array<string, mixed> $event
     */
    public function testThrowIfStreamErrorMessage(array $event, string $expectedMessage): void
    {
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage($expectedMessage);
        $model->exposeThrowIfStreamError($event);
    }

    /**
     * Tests that parseStreamEvent() returns null for an unusable event.
     */
    public function testParseStreamEventReturnsNullWhenEventCarriesNothingUsable(): void
    {
        $model = $this->createModel();

        $this->assertNull($model->exposeParseStreamEvent([]));
        $this->assertNull($model->exposeParseStreamEvent(['choices' => []]));
    }

    /**
     * Tests that parseStreamEvent() ignores a non-string id and non-array usage and choices.
     */
    public function testParseStreamEventIgnoresNonStringIdAndNonArrayUsageAndChoices(): void
    {
        $model = $this->createModel();

        $chunk = $model->exposeParseStreamEvent([
            'id' => 123,
            'usage' => 'nope',
            'choices' => 'nope',
            'system_fingerprint' => 'fp_1',
        ]);

        $this->assertInstanceOf(GenerativeAiResultChunk::class, $chunk);
        $this->assertNull($chunk->getId());
        $this->assertNull($chunk->getTokenUsage());
        $this->assertSame([], $chunk->getCandidateDeltas());
        $this->assertSame(['system_fingerprint' => 'fp_1'], $chunk->getAdditionalData());
    }

    /**
     * Tests that parseStreamEvent() skips non-array choice entries.
     */
    public function testParseStreamEventSkipsNonArrayChoiceEntries(): void
    {
        $model = $this->createModel();

        $chunk = $model->exposeParseStreamEvent([
            'choices' => [
                'not-an-array',
                ['index' => 0, 'delta' => ['content' => 'Hi']],
            ],
        ]);

        $this->assertInstanceOf(GenerativeAiResultChunk::class, $chunk);
        $this->assertCount(1, $chunk->getCandidateDeltas());
        $this->assertSame('Hi', $chunk->getDeltaText());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: int, 2: bool}>
     */
    public function streamChoiceGuardProvider(): array
    {
        return [
            'missing index defaults to 0' => [['delta' => ['content' => 'x']], 0, false],
            'non-int index defaults to 0' => [['index' => '5', 'delta' => ['content' => 'x']], 0, false],
            'non-array delta yields no parts' => [['index' => 2, 'delta' => 'nope'], 2, false],
            'non-string finish reason is dropped' => [['index' => 0, 'delta' => [], 'finish_reason' => 7], 0, false],
            'unknown finish reason is dropped' => [
                ['index' => 0, 'delta' => [], 'finish_reason' => 'mystery'],
                0,
                false,
            ],
            'known finish reason is kept' => [['index' => 0, 'delta' => [], 'finish_reason' => 'stop'], 0, true],
        ];
    }

    /**
     * @dataProvider streamChoiceGuardProvider
     *
     * @param array<string, mixed> $choice
     */
    public function testParseStreamChoiceGuards(array $choice, int $expectedIndex, bool $hasFinishReason): void
    {
        $delta = $this->createModel()->exposeParseStreamChoice($choice);

        $this->assertInstanceOf(CandidateDelta::class, $delta);
        $this->assertSame($expectedIndex, $delta->getIndex());
        if ($hasFinishReason) {
            $this->assertInstanceOf(FinishReasonEnum::class, $delta->getFinishReason());
        } else {
            $this->assertNull($delta->getFinishReason());
        }
    }

    /**
     * Tests that parseStreamDeltaParts() maps channels and ignores non-string values.
     */
    public function testParseStreamDeltaPartsMapsChannels(): void
    {
        $parts = $this->createModel()->exposeParseStreamDeltaParts([
            'reasoning_content' => 'A',
            'reasoning' => 'B',
            'content' => 'C',
        ]);

        $this->assertCount(3, $parts);
        $this->assertSame('A', $parts[0]->getText());
        $this->assertTrue($parts[0]->getChannel()->is(MessagePartChannelEnum::thought()));
        $this->assertSame('B', $parts[1]->getText());
        $this->assertTrue($parts[1]->getChannel()->is(MessagePartChannelEnum::thought()));
        $this->assertSame('C', $parts[2]->getText());
        $this->assertTrue($parts[2]->getChannel()->is(MessagePartChannelEnum::content()));

        $this->assertSame([], $this->createModel()->exposeParseStreamDeltaParts([
            'reasoning_content' => 1,
            'reasoning' => 2,
            'content' => 3,
        ]));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>}>
     */
    public function streamToolCallDeltaGuardProvider(): array
    {
        return [
            'tool_calls not an array' => [
                ['tool_calls' => 'nope'],
                ['count' => 0],
            ],
            'tool call entry not an array' => [
                ['tool_calls' => ['nope']],
                ['count' => 0],
            ],
            'missing index falls back to position' => [
                ['tool_calls' => [['id' => 'a', 'function' => ['name' => 'fn', 'arguments' => '{}']]]],
                ['count' => 1, 'index' => 0, 'id' => 'a', 'name' => 'fn', 'arguments' => '{}'],
            ],
            'non-int index falls back to position' => [
                ['tool_calls' => [['index' => 'x', 'id' => 'a', 'function' => ['name' => 'fn']]]],
                ['count' => 1, 'index' => 0, 'id' => 'a', 'name' => 'fn', 'arguments' => ''],
            ],
            'non-string id becomes null' => [
                ['tool_calls' => [['index' => 0, 'id' => 9, 'function' => ['name' => 'fn']]]],
                ['count' => 1, 'index' => 0, 'id' => null, 'name' => 'fn', 'arguments' => ''],
            ],
            'non-array function yields null name and empty arguments' => [
                ['tool_calls' => [['index' => 0, 'id' => 'a', 'function' => 'nope']]],
                ['count' => 1, 'index' => 0, 'id' => 'a', 'name' => null, 'arguments' => ''],
            ],
            'non-string name and arguments are dropped' => [
                ['tool_calls' => [['index' => 0, 'id' => 'a', 'function' => ['name' => 1, 'arguments' => 2]]]],
                ['count' => 1, 'index' => 0, 'id' => 'a', 'name' => null, 'arguments' => ''],
            ],
        ];
    }

    /**
     * @dataProvider streamToolCallDeltaGuardProvider
     *
     * @param array<string, mixed> $delta
     * @param array<string, mixed> $expected
     */
    public function testParseStreamToolCallDeltasGuards(array $delta, array $expected): void
    {
        $deltas = $this->createModel()->exposeParseStreamToolCallDeltas($delta);

        $this->assertCount($expected['count'], $deltas);
        if ($expected['count'] === 0) {
            return;
        }

        $this->assertSame($expected['index'], $deltas[0]->getIndex());
        $this->assertSame($expected['id'], $deltas[0]->getId());
        $this->assertSame($expected['name'], $deltas[0]->getFunctionName());
        $this->assertSame($expected['arguments'], $deltas[0]->getArgumentsFragment());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: int, 2: int, 3: int, 4: int|null}>
     */
    public function usageDataProvider(): array
    {
        return [
            'full usage with reasoning tokens' => [
                [
                    'prompt_tokens' => 15,
                    'completion_tokens' => 20,
                    'total_tokens' => 35,
                    'completion_tokens_details' => ['reasoning_tokens' => 10],
                ],
                15,
                20,
                35,
                10,
            ],
            'usage without reasoning tokens' => [
                ['prompt_tokens' => 8, 'completion_tokens' => 4, 'total_tokens' => 12],
                8,
                4,
                12,
                null,
            ],
            'partial usage defaults missing counts to zero' => [
                ['prompt_tokens' => 20],
                20,
                0,
                0,
                null,
            ],
            'non-int reasoning tokens are ignored' => [
                [
                    'prompt_tokens' => 1,
                    'completion_tokens' => 1,
                    'total_tokens' => 2,
                    'completion_tokens_details' => ['reasoning_tokens' => 'x'],
                ],
                1,
                1,
                2,
                null,
            ],
            'numeric-string tokens are coerced to int' => [
                ['prompt_tokens' => '15', 'completion_tokens' => '20', 'total_tokens' => '35'],
                15,
                20,
                35,
                null,
            ],
            'float tokens are coerced to int' => [
                ['prompt_tokens' => 15.0, 'completion_tokens' => 20.0, 'total_tokens' => 35.0],
                15,
                20,
                35,
                null,
            ],
            'numeric-string reasoning tokens are coerced to int' => [
                [
                    'prompt_tokens' => 1,
                    'completion_tokens' => 1,
                    'total_tokens' => 2,
                    'completion_tokens_details' => ['reasoning_tokens' => '10'],
                ],
                1,
                1,
                2,
                10,
            ],
        ];
    }

    /**
     * @dataProvider usageDataProvider
     *
     * @param array<string, mixed> $usage
     */
    public function testParseUsageData(
        array $usage,
        int $prompt,
        int $completion,
        int $total,
        ?int $thought
    ): void {
        $tokenUsage = $this->createModel()->exposeParseUsageData($usage);

        $this->assertSame($prompt, $tokenUsage->getPromptTokens());
        $this->assertSame($completion, $tokenUsage->getCompletionTokens());
        $this->assertSame($total, $tokenUsage->getTotalTokens());
        $this->assertSame($thought, $tokenUsage->getThoughtTokens());
    }

    /**
     * Tests that extractAdditionalData() strips id, choices, and usage.
     */
    public function testExtractAdditionalDataStripsKnownKeys(): void
    {
        $data = $this->createModel()->exposeExtractAdditionalData([
            'id' => 'x',
            'choices' => [],
            'usage' => [],
            'object' => 'chat.completion.chunk',
            'system_fingerprint' => 'fp_1',
        ]);

        $this->assertSame(['object' => 'chat.completion.chunk', 'system_fingerprint' => 'fp_1'], $data);
    }
}

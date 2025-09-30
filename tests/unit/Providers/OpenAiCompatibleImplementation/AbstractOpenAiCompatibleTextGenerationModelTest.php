<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\OpenAiCompatibleImplementation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
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
     * Tests streamGenerateTextResult() method.
     *
     * @return void
     */
    public function testStreamGenerateTextResult(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $model = $this->createModel();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Streaming is not yet implemented.');

        $generator = $model->streamGenerateTextResult($prompt);
        $generator->current(); // Attempt to get the first value to trigger the exception.
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
                    'url' => $base64Image
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
        $this->expectExceptionMessage('Unexpected TestProvider API response: The choices key must contain an array.');

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
            'Unexpected TestProvider API response: Each element in the choices key must be an associative array.'
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
        $this->expectExceptionMessage('Unexpected TestProvider API response: Invalid finish reason "unknown".');

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
        $message = $model->exposeParseResponseChoiceMessage($messageData);

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
        $message = $model->exposeParseResponseChoiceMessage($messageData);

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
        $parts = $model->exposeParseResponseChoiceMessageParts($messageData);

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
        $parts = $model->exposeParseResponseChoiceMessageParts($messageData);

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
}

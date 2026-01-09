<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\ProviderImplementations\OpenAi;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tools\DTO\CodeExecution;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\FunctionResponse;
use WordPress\AiClient\Tools\DTO\WebSearch;

/**
 * @covers \WordPress\AiClient\ProviderImplementations\OpenAi\OpenAiTextGenerationModel
 */
class OpenAiTextGenerationModelTest extends TestCase
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
        $this->modelMetadata->method('getId')->willReturn('gpt-4o');
        $this->providerMetadata = $this->createStub(ProviderMetadata::class);
        $this->providerMetadata->method('getName')->willReturn('OpenAI');
        $this->mockHttpTransporter = $this->createMock(HttpTransporterInterface::class);
        $this->mockRequestAuthentication = $this->createMock(RequestAuthenticationInterface::class);
    }

    /**
     * Creates a mock instance of OpenAiTextGenerationModel.
     *
     * @param ModelConfig|null $modelConfig
     * @return MockOpenAiTextGenerationModel
     */
    private function createModel(?ModelConfig $modelConfig = null): MockOpenAiTextGenerationModel
    {
        $model = new MockOpenAiTextGenerationModel(
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
                'id' => 'resp_123',
                'status' => 'completed',
                'output' => [
                    [
                        'type' => 'message',
                        'role' => 'assistant',
                        'content' => [
                            ['type' => 'output_text', 'text' => 'Hi there!'],
                        ],
                    ],
                ],
                'usage' => [
                    'input_tokens' => 10,
                    'output_tokens' => 5,
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
        $this->assertEquals('resp_123', $result->getId());
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
     * Tests streamGenerateTextResult() method throws RuntimeException.
     *
     * @return void
     */
    public function testStreamGenerateTextResultThrowsException(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $model = $this->createModel();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Streaming is not yet implemented for OpenAI Responses API.');

        $generator = $model->streamGenerateTextResult($prompt);
        $generator->current();
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
        $this->assertEquals('gpt-4o', $params['model']);
        $this->assertArrayHasKey('input', $params);
        $this->assertCount(1, $params['input']);
        $this->assertArrayNotHasKey('type', $params['input'][0]);
        $this->assertEquals('user', $params['input'][0]['role']);
        $this->assertCount(1, $params['input'][0]['content']);
        $this->assertEquals('input_text', $params['input'][0]['content'][0]['type']);
        $this->assertEquals('Test message', $params['input'][0]['content'][0]['text']);
    }

    /**
     * Tests prepareGenerateTextParams() with system instruction.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithSystemInstruction(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $config = new ModelConfig();
        $config->setSystemInstruction('You are a helpful assistant.');
        $model = $this->createModel($config);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('instructions', $params);
        $this->assertEquals('You are a helpful assistant.', $params['instructions']);
    }

    /**
     * Tests prepareGenerateTextParams() with max tokens.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithMaxTokens(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $config = new ModelConfig();
        $config->setMaxTokens(1000);
        $model = $this->createModel($config);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('max_output_tokens', $params);
        $this->assertEquals(1000, $params['max_output_tokens']);
    }

    /**
     * Tests prepareGenerateTextParams() with temperature and topP.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithTemperatureAndTopP(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $config = new ModelConfig();
        $config->setTemperature(0.7);
        $config->setTopP(0.9);
        $model = $this->createModel($config);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('temperature', $params);
        $this->assertEquals(0.7, $params['temperature']);
        $this->assertArrayHasKey('top_p', $params);
        $this->assertEquals(0.9, $params['top_p']);
    }

    /**
     * Tests prepareGenerateTextParams() with function declarations.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithFunctionDeclarations(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('What is the weather?')])];
        $functionDeclaration = new FunctionDeclaration(
            'get_weather',
            'Get the current weather',
            ['type' => 'object', 'properties' => ['location' => ['type' => 'string']]]
        );
        $config = new ModelConfig();
        $config->setFunctionDeclarations([$functionDeclaration]);
        $model = $this->createModel($config);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('tools', $params);
        $this->assertCount(1, $params['tools']);
        $this->assertEquals('function', $params['tools'][0]['type']);
        $this->assertEquals('get_weather', $params['tools'][0]['name']);
        $this->assertEquals('Get the current weather', $params['tools'][0]['description']);
    }

    /**
     * Tests prepareGenerateTextParams() with web search.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithWebSearch(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Search for news')])];
        $webSearch = new WebSearch();
        $config = new ModelConfig();
        $config->setWebSearch($webSearch);
        $model = $this->createModel($config);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('tools', $params);
        $this->assertCount(1, $params['tools']);
        $this->assertEquals('web_search', $params['tools'][0]['type']);
    }

    /**
     * Tests prepareGenerateTextParams() with code interpreter via customOptions.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithCodeExecution(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Run some code')])];
        $config = new ModelConfig();
        $config->setCodeExecution(new CodeExecution());
        $model = $this->createModel($config);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('tools', $params);
        $toolTypes = array_column($params['tools'], 'type');
        $this->assertContains('code_interpreter', $toolTypes);
    }

    /**
     * Tests prepareGenerateTextParams() with previous_response_id for conversation state.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithPreviousResponseId(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Continue the conversation')])];
        $config = new ModelConfig();
        $config->setCustomOptions(['previous_response_id' => 'resp_abc123']);
        $model = $this->createModel($config);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('previous_response_id', $params);
        $this->assertEquals('resp_abc123', $params['previous_response_id']);
    }

    /**
     * Tests prepareGenerateTextParams() with JSON output schema.
     *
     * @return void
     */
    public function testPrepareGenerateTextParamsWithJsonOutput(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Return JSON')])];
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ];
        $config = new ModelConfig();
        $config->setOutputMimeType('application/json');
        $config->setOutputSchema($schema);
        $model = $this->createModel($config);

        $params = $model->exposePrepareGenerateTextParams($prompt);

        $this->assertArrayHasKey('text', $params);
        $this->assertArrayHasKey('format', $params['text']);
        $this->assertEquals('json_schema', $params['text']['format']['type']);
        $this->assertEquals($schema, $params['text']['format']['schema']);
    }

    /**
     * Tests getMessageRoleString() method.
     *
     * @return void
     */
    public function testGetMessageRoleString(): void
    {
        $model = $this->createModel();

        $this->assertEquals('user', $model->exposeGetMessageRoleString(MessageRoleEnum::user()));
        $this->assertEquals('assistant', $model->exposeGetMessageRoleString(MessageRoleEnum::model()));
    }

    /**
     * Tests getMessagePartData() with text part.
     *
     * @return void
     */
    public function testGetMessagePartDataWithText(): void
    {
        $model = $this->createModel();
        $part = new MessagePart('Hello world');

        $data = $model->exposeGetMessagePartData($part);

        $this->assertEquals('input_text', $data['type']);
        $this->assertEquals('Hello world', $data['text']);
    }

    /**
     * Tests getMessagePartData() with remote image.
     *
     * @return void
     */
    public function testGetMessagePartDataWithRemoteImage(): void
    {
        $model = $this->createModel();
        $file = new File('https://example.com/image.png', 'image/png');
        $part = new MessagePart($file);

        $data = $model->exposeGetMessagePartData($part);

        $this->assertEquals('input_image', $data['type']);
        $this->assertEquals('https://example.com/image.png', $data['image_url']);
    }

    /**
     * Tests getMessagePartData() with inline image.
     *
     * @return void
     */
    public function testGetMessagePartDataWithInlineImage(): void
    {
        $model = $this->createModel();
        // A minimal 1x1 pixel PNG image encoded in base64.
        $b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $file = new File($b64, 'image/png');
        $part = new MessagePart($file);

        $data = $model->exposeGetMessagePartData($part);

        $this->assertEquals('input_image', $data['type']);
        $this->assertStringStartsWith('data:image/png;base64,', $data['image_url']);
    }

    /**
     * Tests getMessageInputItem() with function response message.
     *
     * @return void
     */
    public function testGetMessageInputItemWithFunctionResponse(): void
    {
        $model = $this->createModel();
        $functionResponse = new FunctionResponse('call_123', 'get_weather', ['temperature' => 72]);
        $part = new MessagePart($functionResponse);
        $message = new Message(MessageRoleEnum::user(), [$part]);

        $data = $model->exposeGetMessageInputItem($message);

        $this->assertNotNull($data);
        $this->assertEquals('function_call_output', $data['type']);
        $this->assertEquals('call_123', $data['call_id']);
        $this->assertEquals('{"temperature":72}', $data['output']);
    }

    /**
     * Tests getMessageInputItem() with function call message.
     *
     * @return void
     */
    public function testGetMessageInputItemWithFunctionCall(): void
    {
        $model = $this->createModel();
        $functionCall = new FunctionCall('call_456', 'search', ['query' => 'test']);
        $part = new MessagePart($functionCall);
        $message = new Message(MessageRoleEnum::model(), [$part]);

        $data = $model->exposeGetMessageInputItem($message);

        $this->assertNotNull($data);
        $this->assertEquals('function_call', $data['type']);
        $this->assertEquals('call_456', $data['call_id']);
        $this->assertEquals('search', $data['name']);
        $this->assertEquals('{"query":"test"}', $data['arguments']);
    }

    /**
     * Tests getMessageInputItem() throws exception for mixed function call message.
     *
     * @return void
     */
    public function testGetMessageInputItemThrowsForMixedFunctionCallMessage(): void
    {
        $model = $this->createModel();
        $functionCall = new FunctionCall('call_456', 'search', ['query' => 'test']);
        $message = new Message(MessageRoleEnum::model(), [
            new MessagePart('Some text'),
            new MessagePart($functionCall),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A function call message must contain only one part.');

        $model->exposeGetMessageInputItem($message);
    }

    /**
     * Tests prepareToolsParam() with all tool types.
     *
     * @return void
     */
    public function testPrepareToolsParamWithAllTools(): void
    {
        $model = $this->createModel();
        $functionDeclaration = new FunctionDeclaration(
            'test_func',
            'A test function',
            ['type' => 'object']
        );
        $webSearch = new WebSearch();
        $codeExecution = new CodeExecution();

        $tools = $model->exposePrepareToolsParam(
            [$functionDeclaration],
            $webSearch,
            $codeExecution
        );

        $this->assertCount(3, $tools);
        $toolTypes = array_column($tools, 'type');
        $this->assertContains('function', $toolTypes);
        $this->assertContains('web_search', $toolTypes);
        $this->assertContains('code_interpreter', $toolTypes);
    }

    /**
     * Tests parseStatusToFinishReason() method.
     *
     * @return void
     */
    public function testParseStatusToFinishReason(): void
    {
        $model = $this->createModel();

        $this->assertEquals(
            FinishReasonEnum::stop(),
            $model->exposeParseStatusToFinishReason('completed', false)
        );
        $this->assertEquals(
            FinishReasonEnum::toolCalls(),
            $model->exposeParseStatusToFinishReason('completed', true)
        );
        $this->assertEquals(
            FinishReasonEnum::length(),
            $model->exposeParseStatusToFinishReason('incomplete', false)
        );
        $this->assertEquals(
            FinishReasonEnum::error(),
            $model->exposeParseStatusToFinishReason('failed', false)
        );
        $this->assertEquals(
            FinishReasonEnum::error(),
            $model->exposeParseStatusToFinishReason('cancelled', false)
        );
    }

    /**
     * Tests parseOutputContentToPart() with text content.
     *
     * @return void
     */
    public function testParseOutputContentToPartWithText(): void
    {
        $model = $this->createModel();

        $part = $model->exposeParseOutputContentToPart([
            'type' => 'output_text',
            'text' => 'Hello world',
        ]);

        $this->assertNotNull($part);
        $this->assertTrue($part->getType()->isText());
        $this->assertEquals('Hello world', $part->getText());
    }

    /**
     * Tests parseOutputContentToPart() with function call.
     *
     * @return void
     */
    public function testParseOutputContentToPartWithFunctionCall(): void
    {
        $model = $this->createModel();

        $part = $model->exposeParseOutputContentToPart([
            'type' => 'function_call',
            'call_id' => 'call_123',
            'name' => 'get_weather',
            'arguments' => '{"location": "Paris"}',
        ]);

        $this->assertNotNull($part);
        $this->assertTrue($part->getType()->isFunctionCall());
        $functionCall = $part->getFunctionCall();
        $this->assertNotNull($functionCall);
        $this->assertEquals('call_123', $functionCall->getId());
        $this->assertEquals('get_weather', $functionCall->getName());
        $this->assertEquals(['location' => 'Paris'], $functionCall->getArgs());
    }

    /**
     * Tests parseMessageOutputToCandidate() method.
     *
     * @return void
     */
    public function testParseMessageOutputToCandidate(): void
    {
        $model = $this->createModel();

        $candidate = $model->exposeParseMessageOutputToCandidate(
            [
                'type' => 'message',
                'role' => 'assistant',
                'content' => [
                    ['type' => 'output_text', 'text' => 'Hello!'],
                ],
            ],
            0,
            'completed'
        );

        $this->assertNotNull($candidate);
        $this->assertEquals(FinishReasonEnum::stop(), $candidate->getFinishReason());
        $this->assertEquals(MessageRoleEnum::model(), $candidate->getMessage()->getRole());
        $this->assertEquals('Hello!', $candidate->getMessage()->getParts()[0]->getText());
    }

    /**
     * Tests parseFunctionCallOutputToCandidate() method.
     *
     * @return void
     */
    public function testParseFunctionCallOutputToCandidate(): void
    {
        $model = $this->createModel();

        $candidate = $model->exposeParseFunctionCallOutputToCandidate(
            [
                'type' => 'function_call',
                'call_id' => 'call_abc',
                'name' => 'search',
                'arguments' => '{"query": "test"}',
            ],
            0
        );

        $this->assertNotNull($candidate);
        $this->assertEquals(FinishReasonEnum::toolCalls(), $candidate->getFinishReason());
        $parts = $candidate->getMessage()->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isFunctionCall());
    }

    /**
     * Tests generateTextResult() with function call response.
     *
     * @return void
     */
    public function testGenerateTextResultWithFunctionCallResponse(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('What is the weather?')])];
        $response = new Response(
            200,
            [],
            json_encode([
                'id' => 'resp_456',
                'status' => 'completed',
                'output' => [
                    [
                        'type' => 'function_call',
                        'call_id' => 'call_789',
                        'name' => 'get_weather',
                        'arguments' => '{"location": "Paris"}',
                    ],
                ],
                'usage' => [
                    'input_tokens' => 20,
                    'output_tokens' => 10,
                    'total_tokens' => 30,
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
        $this->assertCount(1, $result->getCandidates());
        $candidate = $result->getCandidates()[0];
        $this->assertEquals(FinishReasonEnum::toolCalls(), $candidate->getFinishReason());
        $parts = $candidate->getMessage()->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isFunctionCall());
        $functionCall = $parts[0]->getFunctionCall();
        $this->assertNotNull($functionCall);
        $this->assertEquals('get_weather', $functionCall->getName());
    }
}

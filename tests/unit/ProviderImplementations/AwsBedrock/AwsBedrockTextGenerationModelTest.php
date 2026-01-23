<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\ProviderImplementations\AwsBedrock;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
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
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;

/**
 * @covers \WordPress\AiClient\ProviderImplementations\AwsBedrock\AwsBedrockTextGenerationModel
 */
class AwsBedrockTextGenerationModelTest extends TestCase
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
        $this->modelMetadata->method('getId')->willReturn('anthropic.claude-3-5-sonnet-20241022-v2:0');
        $this->providerMetadata = $this->createStub(ProviderMetadata::class);
        $this->providerMetadata->method('getName')->willReturn('AWS Bedrock');
        $this->mockHttpTransporter = $this->createMock(HttpTransporterInterface::class);
        $this->mockRequestAuthentication = $this->createMock(RequestAuthenticationInterface::class);
    }

    /**
     * Creates a mock instance of AwsBedrockTextGenerationModel.
     *
     * @param ModelConfig|null $modelConfig
     * @return MockAwsBedrockTextGenerationModel
     */
    private function createModel(?ModelConfig $modelConfig = null): MockAwsBedrockTextGenerationModel
    {
        $model = new MockAwsBedrockTextGenerationModel(
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
                'output' => [
                    'message' => [
                        'role' => 'assistant',
                        'content' => [
                            ['text' => 'Hi there!'],
                        ],
                    ],
                ],
                'stopReason' => 'end_turn',
                'usage' => [
                    'inputTokens' => 10,
                    'outputTokens' => 5,
                    'totalTokens' => 15,
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
        $response = new Response(400, [], '{"message": "Invalid request"}');

        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $this->expectException(ClientException::class);

        $model = $this->createModel();
        $model->generateTextResult($prompt);
    }

    /**
     * Tests getRegion() with default region.
     *
     * @return void
     */
    public function testGetRegionDefaultsToUsEast1(): void
    {
        $model = $this->createModel();
        $region = $model->exposeGetRegion();

        $this->assertEquals('us-east-1', $region);
    }

    /**
     * Tests getRegion() with custom region from config.
     *
     * @return void
     */
    public function testGetRegionFromConfig(): void
    {
        $config = new ModelConfig();
        $config->setCustomOption('region', 'us-west-2');

        $model = $this->createModel($config);
        $region = $model->exposeGetRegion();

        $this->assertEquals('us-west-2', $region);
    }

    /**
     * Tests prepareConverseParams() with basic configuration.
     *
     * @return void
     */
    public function testPrepareConverseParamsBasic(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $model = $this->createModel();

        $params = $model->exposePrepareConverseParams($prompt);

        $this->assertArrayHasKey('messages', $params);
        $this->assertCount(1, $params['messages']);
        $this->assertEquals('user', $params['messages'][0]['role']);
        $this->assertEquals('Hello', $params['messages'][0]['content'][0]['text']);
    }

    /**
     * Tests prepareConverseParams() with system instruction.
     *
     * @return void
     */
    public function testPrepareConverseParamsWithSystemInstruction(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $config = new ModelConfig();
        $config->setSystemInstruction('You are a helpful assistant.');

        $model = $this->createModel($config);
        $params = $model->exposePrepareConverseParams($prompt);

        $this->assertArrayHasKey('system', $params);
        $this->assertEquals('You are a helpful assistant.', $params['system'][0]['text']);
    }

    /**
     * Tests prepareConverseParams() with inference config.
     *
     * @return void
     */
    public function testPrepareConverseParamsWithInferenceConfig(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $config = new ModelConfig();
        $config->setMaxTokens(1000);
        $config->setTemperature(0.7);
        $config->setTopP(0.9);
        $config->setStopSequences(['STOP', 'END']);

        $model = $this->createModel($config);
        $params = $model->exposePrepareConverseParams($prompt);

        $this->assertArrayHasKey('inferenceConfig', $params);
        $this->assertEquals(1000, $params['inferenceConfig']['maxTokens']);
        $this->assertEquals(0.7, $params['inferenceConfig']['temperature']);
        $this->assertEquals(0.9, $params['inferenceConfig']['topP']);
        $this->assertEquals(['STOP', 'END'], $params['inferenceConfig']['stopSequences']);
    }

    /**
     * Tests prepareConverseParams() with function declarations.
     *
     * @return void
     */
    public function testPrepareConverseParamsWithFunctionDeclarations(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('What is the weather?')])];
        $config = new ModelConfig();
        $functionDecl = new FunctionDeclaration(
            'get_weather',
            'Gets the weather for a location',
            [
                'type' => 'object',
                'properties' => [
                    'location' => ['type' => 'string'],
                ],
                'required' => ['location'],
            ]
        );
        $config->setFunctionDeclarations([$functionDecl]);

        $model = $this->createModel($config);
        $params = $model->exposePrepareConverseParams($prompt);

        $this->assertArrayHasKey('toolConfig', $params);
        $this->assertArrayHasKey('tools', $params['toolConfig']);
        $this->assertCount(1, $params['toolConfig']['tools']);
        $this->assertEquals('get_weather', $params['toolConfig']['tools'][0]['toolSpec']['name']);
    }

    /**
     * Tests getImageFormat() mapping.
     *
     * @return void
     */
    public function testGetImageFormat(): void
    {
        $model = $this->createModel();

        $this->assertEquals('jpeg', $model->exposeGetImageFormat('image/jpeg'));
        $this->assertEquals('png', $model->exposeGetImageFormat('image/png'));
        $this->assertEquals('gif', $model->exposeGetImageFormat('image/gif'));
        $this->assertEquals('webp', $model->exposeGetImageFormat('image/webp'));
        $this->assertEquals('png', $model->exposeGetImageFormat('image/unknown'));
    }

    /**
     * Tests getDocumentFormat() mapping.
     *
     * @return void
     */
    public function testGetDocumentFormat(): void
    {
        $model = $this->createModel();

        $this->assertEquals('pdf', $model->exposeGetDocumentFormat('application/pdf'));
        $this->assertEquals('txt', $model->exposeGetDocumentFormat('text/plain'));
        $this->assertEquals('html', $model->exposeGetDocumentFormat('text/html'));
        $this->assertEquals('csv', $model->exposeGetDocumentFormat('text/csv'));
        $this->assertEquals('md', $model->exposeGetDocumentFormat('text/markdown'));
        $this->assertEquals('txt', $model->exposeGetDocumentFormat('text/unknown'));
    }

    /**
     * Tests mapStopReason() mapping.
     *
     * @return void
     */
    public function testMapStopReason(): void
    {
        $model = $this->createModel();

        $this->assertEquals(FinishReasonEnum::stop(), $model->exposeMapStopReason('end_turn'));
        $this->assertEquals(FinishReasonEnum::length(), $model->exposeMapStopReason('max_tokens'));
        $this->assertEquals(FinishReasonEnum::stop(), $model->exposeMapStopReason('stop_sequence'));
        $this->assertEquals(FinishReasonEnum::toolCalls(), $model->exposeMapStopReason('tool_use'));
        $this->assertEquals(FinishReasonEnum::contentFilter(), $model->exposeMapStopReason('content_filtered'));
        $this->assertEquals(FinishReasonEnum::error(), $model->exposeMapStopReason('unknown_reason'));
    }

    /**
     * Tests prepareFileContent() throws exception for file without base64 data.
     *
     * @return void
     */
    public function testPrepareFileContentThrowsExceptionForFileWithoutBase64Data(): void
    {
        $model = $this->createModel();
        $file = new File('https://example.com/image.jpg', 'image/jpeg');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File must have base64 data for Bedrock API');

        $model->exposePrepareFileContent($file);
    }

    /**
     * Tests prepareFileContent() throws exception for unsupported MIME type.
     *
     * @return void
     */
    public function testPrepareFileContentThrowsExceptionForUnsupportedMimeType(): void
    {
        $model = $this->createModel();
        // Create a file with base64 data but unsupported MIME type
        $file = new File('data:audio/mp3;base64,AAAA', 'audio/mp3');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported file MIME type');

        $model->exposePrepareFileContent($file);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\OpenAiCompatibleImplementation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
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
use WordPress\AiClient\Tests\mocks\MockOpenAiCompatibleImageGenerationModel;

/**
 * @covers \WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleImageGenerationModel
 */
class AbstractOpenAiCompatibleImageGenerationModelTest extends TestCase
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
        $this->modelMetadata->method('getId')->willReturn('test-image-model');
        $this->providerMetadata = $this->createStub(ProviderMetadata::class);
        $this->providerMetadata->method('getName')->willReturn('TestProvider');
        $this->mockHttpTransporter = $this->createMock(HttpTransporterInterface::class);
        $this->mockRequestAuthentication = $this->createMock(RequestAuthenticationInterface::class);
    }

    /**
     * Creates a mock instance of MockOpenAiCompatibleImageGenerationModel.
     *
     * @param ModelConfig|null $modelConfig
     * @return MockOpenAiCompatibleImageGenerationModel
     */
    private function createModel(?ModelConfig $modelConfig = null): MockOpenAiCompatibleImageGenerationModel
    {
        $model = new MockOpenAiCompatibleImageGenerationModel(
            $this->modelMetadata,
            $this->providerMetadata
        );
        // Explicitly set the transporter and request authentication, as the parent constructor does not set them.
        $model->setHttpTransporter($this->mockHttpTransporter);
        $model->setRequestAuthentication($this->mockRequestAuthentication);
        if ($modelConfig) {
            $model->setConfig($modelConfig);
        }
        return $model;
    }

    /**
     * Tests generateImageResult() method on success with URL output.
     *
     * @return void
     */
    public function testGenerateImageResultSuccessWithUrlOutput(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('A cat')])];
        $response = new Response(
            200,
            [],
            json_encode([
                'id' => 'image-gen-123',
                'data' => [
                    [
                        'url' => 'https://example.com/cat.png',
                    ],
                ],
                'usage' => [
                    'input_tokens' => 10,
                    'output_tokens' => 0,
                    'total_tokens' => 10,
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

        $modelConfig = ModelConfig::fromArray(['outputFileType' => FileTypeEnum::remote()->value]);
        $model = $this->createModel($modelConfig);
        $result = $model->generateImageResult($prompt);

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertEquals('image-gen-123', $result->getId());
        $this->assertCount(1, $result->getCandidates());
        $this->assertEquals(
            'https://example.com/cat.png',
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getUrl()
        );
        $this->assertEquals(
            'image/png',
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getMimeType()
        );
        $this->assertEquals(FinishReasonEnum::stop(), $result->getCandidates()[0]->getFinishReason());
        $this->assertEquals(10, $result->getTokenUsage()->getPromptTokens());
        $this->assertEquals(0, $result->getTokenUsage()->getCompletionTokens());
        $this->assertEquals(10, $result->getTokenUsage()->getTotalTokens());
    }

    /**
     * Tests generateImageResult() method on success with base64 JSON output.
     *
     * @return void
     */
    public function testGenerateImageResultSuccessWithBase64JsonOutput(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('A dog')])];
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $response = new Response(
            200,
            [],
            json_encode([
                'id' => 'image-gen-456',
                'data' => [
                    [
                        'b64_json' => $base64Image,
                    ],
                ],
                'usage' => [
                    'input_tokens' => 12,
                    'output_tokens' => 0,
                    'total_tokens' => 12,
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

        $modelConfig = ModelConfig::fromArray(['outputFileType' => FileTypeEnum::inline()->value]);
        $model = $this->createModel($modelConfig);
        $result = $model->generateImageResult($prompt);

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertEquals('image-gen-456', $result->getId());
        $this->assertCount(1, $result->getCandidates());
        $this->assertEquals(
            $base64Image,
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getBase64Data()
        );
        $this->assertEquals(
            'image/png',
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getMimeType()
        );
        $this->assertEquals(FinishReasonEnum::stop(), $result->getCandidates()[0]->getFinishReason());
        $this->assertEquals(12, $result->getTokenUsage()->getPromptTokens());
        $this->assertEquals(0, $result->getTokenUsage()->getCompletionTokens());
        $this->assertEquals(12, $result->getTokenUsage()->getTotalTokens());
    }

    /**
     * Tests generateImageResult() method on API failure.
     *
     * @return void
     */
    public function testGenerateImageResultApiFailure(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('A tree')])];
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

        $model->generateImageResult($prompt);
    }

    /**
     * Tests prepareGenerateImageParams() with basic text prompt.
     *
     * @return void
     */
    public function testPrepareGenerateImageParamsBasicText(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test image prompt')])];
        $model = $this->createModel();

        $params = $model->exposePrepareGenerateImageParams($prompt);

        $this->assertArrayHasKey('model', $params);
        $this->assertEquals('test-image-model', $params['model']);
        $this->assertArrayHasKey('prompt', $params);
        $this->assertEquals('Test image prompt', $params['prompt']);
        $this->assertArrayHasKey('response_format', $params);
        $this->assertEquals('b64_json', $params['response_format']);
        $this->assertArrayNotHasKey('n', $params);
        $this->assertArrayNotHasKey('output_format', $params);
        $this->assertArrayNotHasKey('size', $params);
    }

    /**
     * Tests prepareGenerateImageParams() with candidate count.
     *
     * @return void
     */
    public function testPrepareGenerateImageParamsWithCandidateCount(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['candidateCount' => 2]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateImageParams($prompt);

        $this->assertArrayHasKey('n', $params);
        $this->assertEquals(2, $params['n']);
    }

    /**
     * Tests prepareGenerateImageParams() with remote output file type.
     *
     * @return void
     */
    public function testPrepareGenerateImageParamsWithRemoteOutputFileType(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['outputFileType' => FileTypeEnum::remote()->value]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateImageParams($prompt);

        $this->assertArrayHasKey('response_format', $params);
        $this->assertEquals('url', $params['response_format']);
    }

    /**
     * Tests prepareGenerateImageParams() with inline output file type.
     *
     * @return void
     */
    public function testPrepareGenerateImageParamsWithInlineOutputFileType(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['outputFileType' => FileTypeEnum::inline()->value]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateImageParams($prompt);

        $this->assertArrayHasKey('response_format', $params);
        $this->assertEquals('b64_json', $params['response_format']);
    }

    /**
     * Tests prepareGenerateImageParams() with output MIME type.
     *
     * @return void
     */
    public function testPrepareGenerateImageParamsWithOutputMimeType(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['outputMimeType' => 'image/jpeg']);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateImageParams($prompt);

        $this->assertArrayHasKey('output_format', $params);
        $this->assertEquals('jpeg', $params['output_format']);
    }

    /**
     * Tests prepareGenerateImageParams() with output media orientation.
     *
     * @return void
     */
    public function testPrepareGenerateImageParamsWithOutputMediaOrientation(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['outputMediaOrientation' => MediaOrientationEnum::landscape()->value]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateImageParams($prompt);

        $this->assertArrayHasKey('size', $params);
        $this->assertEquals('1536x1024', $params['size']);
    }

    /**
     * Tests prepareGenerateImageParams() with output media aspect ratio.
     *
     * @return void
     * @dataProvider aspectRatioProvider
     */
    public function testPrepareGenerateImageParamsWithOutputMediaAspectRatio(
        string $aspectRatio,
        string $expectedSize
    ): void {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['outputMediaAspectRatio' => $aspectRatio]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateImageParams($prompt);

        $this->assertArrayHasKey('size', $params);
        $this->assertEquals($expectedSize, $params['size']);
    }

    /**
     * Provides aspect ratios and their expected sizes.
     *
     * @return array<string, array<string>>
     */
    public function aspectRatioProvider(): array
    {
        return [
            '1:1' => ['1:1', '1024x1024'],
            '3:2' => ['3:2', '1536x1024'],
            '7:4' => ['7:4', '1792x1024'],
            '2:3' => ['2:3', '1024x1536'],
            '4:7' => ['4:7', '1024x1792'],
        ];
    }

    /**
     * Tests prepareGenerateImageParams() with custom options.
     *
     * @return void
     */
    public function testPrepareGenerateImageParamsWithCustomOptions(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['customOptions' => ['my_custom_key' => 'my_custom_value']]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateImageParams($prompt);

        $this->assertArrayHasKey('my_custom_key', $params);
        $this->assertEquals('my_custom_value', $params['my_custom_key']);
    }

    /**
     * Tests prepareGenerateImageParams() with conflicting custom option.
     *
     * @return void
     */
    public function testPrepareGenerateImageParamsWithConflictingCustomOption(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['customOptions' => ['model' => 'conflicting-model']]);
        $model = $this->createModel($modelConfig);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The custom option "model" conflicts with an existing parameter.');

        $model->exposePrepareGenerateImageParams($prompt);
    }

    /**
     * Tests preparePromptParam() with a single user message.
     *
     * @return void
     */
    public function testPreparePromptParamSingleUserMessage(): void
    {
        $message = new Message(MessageRoleEnum::user(), [new MessagePart('Hello image')]);
        $model = $this->createModel();

        $preparedPrompt = $model->exposePreparePromptParam([$message]);

        $this->assertEquals('Hello image', $preparedPrompt);
    }

    /**
     * Tests preparePromptParam() with multiple messages.
     *
     * @return void
     */
    public function testPreparePromptParamMultipleMessages(): void
    {
        $messages = [
            new Message(MessageRoleEnum::user(), [new MessagePart('Hello')]),
            new Message(MessageRoleEnum::model(), [new MessagePart('Hi')]),
        ];
        $model = $this->createModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The API requires a single user message as prompt.');

        $model->exposePreparePromptParam($messages);
    }

    /**
     * Tests preparePromptParam() with a non-user message.
     *
     * @return void
     */
    public function testPreparePromptParamNonUserMessage(): void
    {
        $message = new Message(MessageRoleEnum::model(), [new MessagePart('Hello')]);
        $model = $this->createModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The API requires a user message as prompt.');

        $model->exposePreparePromptParam([$message]);
    }

    /**
     * Tests preparePromptParam() with a message without text part.
     *
     * @return void
     */
    public function testPreparePromptParamMessageWithoutTextPart(): void
    {
        $message = new Message(
            MessageRoleEnum::user(),
            [new MessagePart(new File('https://example.com/image.png', 'image/png'))]
        );
        $model = $this->createModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The API requires a single text message part as prompt.');

        $model->exposePreparePromptParam([$message]);
    }

    /**
     * Tests prepareSizeParam() with square orientation and 1:1 aspect ratio.
     *
     * @return void
     */
    public function testPrepareSizeParamSquare1x1(): void
    {
        $model = $this->createModel();
        $size = $model->exposePrepareSizeParam(MediaOrientationEnum::square(), '1:1');
        $this->assertEquals('1024x1024', $size);
    }

    /**
     * Tests prepareSizeParam() with landscape orientation and compatible aspect ratio.
     *
     * @return void
     */
    public function testPrepareSizeParamLandscape3x2(): void
    {
        $model = $this->createModel();
        $size = $model->exposePrepareSizeParam(MediaOrientationEnum::landscape(), '3:2');
        $this->assertEquals('1536x1024', $size);
    }

    /**
     * Tests prepareSizeParam() with portrait orientation and compatible aspect ratio.
     *
     * @return void
     */
    public function testPrepareSizeParamPortrait2x3(): void
    {
        $model = $this->createModel();
        $size = $model->exposePrepareSizeParam(MediaOrientationEnum::portrait(), '2:3');
        $this->assertEquals('1024x1536', $size);
    }

    /**
     * Tests prepareSizeParam() with unsupported aspect ratio.
     *
     * @return void
     */
    public function testPrepareSizeParamUnsupportedAspectRatio(): void
    {
        $model = $this->createModel();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The aspect ratio "16:9" is not supported.');
        $model->exposePrepareSizeParam(null, '16:9');
    }

    /**
     * Tests prepareSizeParam() with only orientation.
     *
     * @dataProvider orientationOnlyProvider
     * @param MediaOrientationEnum $orientation
     * @param string $expectedSize
     * @return void
     */
    public function testPrepareSizeParamOrientationOnly(MediaOrientationEnum $orientation, string $expectedSize): void
    {
        $model = $this->createModel();
        $size = $model->exposePrepareSizeParam($orientation, null);
        $this->assertEquals($expectedSize, $size);
    }

    /**
     * Provides orientations and their expected sizes.
     *
     * @return array<string, array<mixed>>
     */
    public function orientationOnlyProvider(): array
    {
        return [
            'square' => [MediaOrientationEnum::square(), '1024x1024'],
            'landscape' => [MediaOrientationEnum::landscape(), '1536x1024'],
            'portrait' => [MediaOrientationEnum::portrait(), '1024x1536'],
        ];
    }

    /**
     * Tests throwIfNotSuccessful() with a successful response.
     *
     * @return void
     */
    public function testThrowIfNotSuccessfulSuccess(): void
    {
        $response = new Response(200, [], '{"status":"success"}');
        $model = $this->createModel();
        $model->exposeThrowIfNotSuccessful($response);
        $this->assertTrue(true); // No exception means success.
    }

    /**
     * Tests throwIfNotSuccessful() with an unsuccessful response.
     *
     * @return void
     */
    public function testThrowIfNotSuccessfulFailure(): void
    {
        $response = new Response(404, [], '{"error":"The resource does not exist."}');
        $model = $this->createModel();

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Not Found (404) - The resource does not exist.');

        $model->exposeThrowIfNotSuccessful($response);
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with valid response (URL).
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultValidResponseUrl(): void
    {
        $response = new Response(
            200,
            [],
            json_encode([
                'id' => 'test-id-url',
                'data' => [
                    [
                        'url' => 'https://example.com/img.jpg',
                    ],
                ],
                'usage' => [
                    'input_tokens' => 5,
                    'output_tokens' => 0,
                    'total_tokens' => 5,
                ],
                'created' => 1678886400,
            ])
        );
        $model = $this->createModel();
        $result = $model->exposeParseResponseToGenerativeAiResult($response, 'image/jpeg');

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertEquals('test-id-url', $result->getId());
        $this->assertCount(1, $result->getCandidates());
        $this->assertEquals(
            'https://example.com/img.jpg',
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getUrl()
        );
        $this->assertEquals(
            'image/jpeg',
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getMimeType()
        );
        $this->assertEquals(FinishReasonEnum::stop(), $result->getCandidates()[0]->getFinishReason());
        $this->assertEquals(5, $result->getTokenUsage()->getPromptTokens());
        $this->assertEquals(0, $result->getTokenUsage()->getCompletionTokens());
        $this->assertEquals(5, $result->getTokenUsage()->getTotalTokens());
        $this->assertEquals(['created' => 1678886400], $result->getAdditionalData());
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with valid response (b64_json).
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultValidResponseB64Json(): void
    {
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $response = new Response(
            200,
            [],
            json_encode([
                'id' => 'test-id-b64',
                'data' => [
                    [
                        'b64_json' => $base64Image,
                    ],
                ],
                'usage' => [
                    'input_tokens' => 7,
                    'output_tokens' => 0,
                    'total_tokens' => 7,
                ],
            ])
        );
        $model = $this->createModel();
        $result = $model->exposeParseResponseToGenerativeAiResult($response);

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertEquals('test-id-b64', $result->getId());
        $this->assertCount(1, $result->getCandidates());
        $this->assertEquals(
            $base64Image,
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getBase64Data()
        );
        $this->assertEquals(
            'image/png',
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getMimeType()
        );
        $this->assertEquals(FinishReasonEnum::stop(), $result->getCandidates()[0]->getFinishReason());
        $this->assertEquals(7, $result->getTokenUsage()->getPromptTokens());
        $this->assertEquals(0, $result->getTokenUsage()->getCompletionTokens());
        $this->assertEquals(7, $result->getTokenUsage()->getTotalTokens());
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with missing data key.
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultMissingData(): void
    {
        $response = new Response(200, [], json_encode(['id' => 'test-id']));
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('Unexpected TestProvider API response: Missing the "data" key.');

        $model->exposeParseResponseToGenerativeAiResult($response);
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with invalid data type.
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultInvalidDataType(): void
    {
        $response = new Response(
            200,
            [],
            json_encode(['data' => 'invalid'])
        );
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(
            'Unexpected TestProvider API response: Invalid "data" key: The value must be an array.'
        );

        $model->exposeParseResponseToGenerativeAiResult($response);
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with invalid choice element type.
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultInvalidChoiceElementType(): void
    {
        $response = new Response(200, [], json_encode(['data' => ['invalid']]));
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(
            'Unexpected TestProvider API response: Invalid "data[0]" key: The value must be an associative array.'
        );

        $model->exposeParseResponseToGenerativeAiResult($response);
    }

    /**
     * Tests parseResponseChoiceToCandidate() with valid URL data.
     *
     * @return void
     */
    public function testParseResponseChoiceToCandidateValidUrlData(): void
    {
        $choiceData = [
            'url' => 'https://example.com/image.png',
        ];
        $model = $this->createModel();
        $candidate = $model->exposeParseResponseChoiceToCandidate($choiceData, 0, 'image/png');

        $this->assertInstanceOf(Candidate::class, $candidate);
        $this->assertEquals(
            'https://example.com/image.png',
            $candidate->getMessage()->getParts()[0]->getFile()->getUrl()
        );
        $this->assertEquals('image/png', $candidate->getMessage()->getParts()[0]->getFile()->getMimeType());
        $this->assertEquals(MessageRoleEnum::model(), $candidate->getMessage()->getRole());
        $this->assertEquals(FinishReasonEnum::stop(), $candidate->getFinishReason());
    }

    /**
     * Tests parseResponseChoiceToCandidate() with valid b64_json data.
     *
     * @return void
     */
    public function testParseResponseChoiceToCandidateValidB64JsonData(): void
    {
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
        $choiceData = [
            'b64_json' => $base64Image,
        ];
        $model = $this->createModel();
        $candidate = $model->exposeParseResponseChoiceToCandidate($choiceData, 0, 'image/png');

        $this->assertInstanceOf(Candidate::class, $candidate);
        $this->assertEquals($base64Image, $candidate->getMessage()->getParts()[0]->getFile()->getBase64Data());
        $this->assertEquals('image/png', $candidate->getMessage()->getParts()[0]->getFile()->getMimeType());
        $this->assertEquals(MessageRoleEnum::model(), $candidate->getMessage()->getRole());
        $this->assertEquals(FinishReasonEnum::stop(), $candidate->getFinishReason());
    }

    /**
     * Tests parseResponseChoiceToCandidate() with missing url or b64_json.
     *
     * @return void
     */
    public function testParseResponseChoiceToCandidateMissingUrlOrB64Json(): void
    {
        $choiceData = [
            'other_key' => 'value',
        ];
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(
            'Unexpected TestProvider API response: Invalid "choices[0]" key: The value must contain either a ' .
            'url or b64_json key with a string value.'
        );

        $model->exposeParseResponseChoiceToCandidate($choiceData, 0);
    }
}

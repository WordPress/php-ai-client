<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\ProviderImplementations\OpenAi;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
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

/**
 * @covers \WordPress\AiClient\ProviderImplementations\OpenAi\OpenAiImageGenerationModel
 */
class OpenAiImageGenerationModelTest extends TestCase
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
        $this->modelMetadata->method('getId')->willReturn('gpt-image-1');
        $this->providerMetadata = $this->createStub(ProviderMetadata::class);
        $this->providerMetadata->method('getName')->willReturn('OpenAI');
        $this->mockHttpTransporter = $this->createMock(HttpTransporterInterface::class);
        $this->mockRequestAuthentication = $this->createMock(RequestAuthenticationInterface::class);
    }

    /**
     * Creates a mock instance of OpenAiImageGenerationModel.
     *
     * @param ModelConfig|null $modelConfig
     * @param string $modelId
     * @return MockOpenAiImageGenerationModel
     */
    private function createModel(
        ?ModelConfig $modelConfig = null,
        string $modelId = 'gpt-image-1'
    ): MockOpenAiImageGenerationModel {
        $this->modelMetadata = $this->createStub(ModelMetadata::class);
        $this->modelMetadata->method('getId')->willReturn($modelId);

        $model = new MockOpenAiImageGenerationModel(
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
     * A minimal valid 1x1 pixel PNG image encoded in base64.
     */
    private const VALID_BASE64_IMAGE =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    /**
     * Tests generateImageResult() method on success.
     *
     * @return void
     */
    public function testGenerateImageResultSuccess(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Generate a cat')])];
        $response = new Response(
            200,
            [],
            json_encode([
                'created' => 1234567890,
                'data' => [
                    [
                        'b64_json' => self::VALID_BASE64_IMAGE,
                    ],
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
        $result = $model->generateImageResult($prompt);

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertEquals('img-1234567890', $result->getId());
        $this->assertCount(1, $result->getCandidates());
        $candidate = $result->getCandidates()[0];
        $this->assertEquals(FinishReasonEnum::stop(), $candidate->getFinishReason());
        $parts = $candidate->getMessage()->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isFile());
        $file = $parts[0]->getFile();
        $this->assertNotNull($file);
        $this->assertEquals(self::VALID_BASE64_IMAGE, $file->getBase64Data());
    }

    /**
     * Tests generateImageResult() method on API failure.
     *
     * @return void
     */
    public function testGenerateImageResultApiFailure(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Generate a cat')])];
        $response = new Response(400, [], '{"error": "Invalid request."}');

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
        $this->expectExceptionMessage('Bad Request (400) - Invalid request.');

        $model->generateImageResult($prompt);
    }

    /**
     * Tests isGptImageModel() method.
     *
     * @return void
     */
    public function testIsGptImageModel(): void
    {
        $model = $this->createModel();

        // GPT image models should return true.
        $this->assertTrue($model->exposeIsGptImageModel('gpt-image-1'));
        $this->assertTrue($model->exposeIsGptImageModel('gpt-image-1-mini'));
        $this->assertTrue($model->exposeIsGptImageModel('gpt-image-1.5'));

        // DALL-E models should return false.
        $this->assertFalse($model->exposeIsGptImageModel('dall-e-2'));
        $this->assertFalse($model->exposeIsGptImageModel('dall-e-3'));

        // Other models should return false.
        $this->assertFalse($model->exposeIsGptImageModel('gpt-4o'));
    }

    /**
     * Tests preparePromptParam() with valid single message.
     *
     * @return void
     */
    public function testPreparePromptParamWithValidMessage(): void
    {
        $model = $this->createModel();
        $messages = [new Message(MessageRoleEnum::user(), [new MessagePart('Generate a cat')])];

        $prompt = $model->exposePreparePromptParam($messages);

        $this->assertEquals('Generate a cat', $prompt);
    }

    /**
     * Tests preparePromptParam() with multiple messages throws exception.
     *
     * @return void
     */
    public function testPreparePromptParamWithMultipleMessagesThrowsException(): void
    {
        $model = $this->createModel();
        $messages = [
            new Message(MessageRoleEnum::user(), [new MessagePart('Message 1')]),
            new Message(MessageRoleEnum::user(), [new MessagePart('Message 2')]),
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The API requires a single user message as prompt.');

        $model->exposePreparePromptParam($messages);
    }

    /**
     * Tests preparePromptParam() with non-user message throws exception.
     *
     * @return void
     */
    public function testPreparePromptParamWithNonUserMessageThrowsException(): void
    {
        $model = $this->createModel();
        $messages = [new Message(MessageRoleEnum::model(), [new MessagePart('Response')])];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The API requires a user message as prompt.');

        $model->exposePreparePromptParam($messages);
    }

    /**
     * Tests prepareSizeParam() with GPT image model aspect ratios.
     *
     * @return void
     */
    public function testPrepareSizeParamWithGptImageModelAspectRatios(): void
    {
        $model = $this->createModel();

        $this->assertEquals('1024x1024', $model->exposePrepareSize('gpt-image-1', null, '1:1'));
        $this->assertEquals('1536x1024', $model->exposePrepareSize('gpt-image-1', null, '3:2'));
        $this->assertEquals('1024x1536', $model->exposePrepareSize('gpt-image-1', null, '2:3'));
    }

    /**
     * Tests prepareSizeParam() with GPT image model orientations.
     *
     * @return void
     */
    public function testPrepareSizeParamWithGptImageModelOrientations(): void
    {
        $model = $this->createModel();

        $landscape = MediaOrientationEnum::landscape();
        $portrait = MediaOrientationEnum::portrait();
        $square = MediaOrientationEnum::square();

        $this->assertEquals('1536x1024', $model->exposePrepareSize('gpt-image-1', $landscape, null));
        $this->assertEquals('1024x1536', $model->exposePrepareSize('gpt-image-1', $portrait, null));
        $this->assertEquals('1024x1024', $model->exposePrepareSize('gpt-image-1', $square, null));
    }

    /**
     * Tests prepareSizeParam() with DALL-E 3 aspect ratios.
     *
     * @return void
     */
    public function testPrepareSizeParamWithDalle3AspectRatios(): void
    {
        $model = $this->createModel();

        $this->assertEquals('1024x1024', $model->exposePrepareSize('dall-e-3', null, '1:1'));
        $this->assertEquals('1792x1024', $model->exposePrepareSize('dall-e-3', null, '7:4'));
        $this->assertEquals('1024x1792', $model->exposePrepareSize('dall-e-3', null, '4:7'));
    }

    /**
     * Tests prepareSizeParam() with DALL-E 3 orientations.
     *
     * @return void
     */
    public function testPrepareSizeParamWithDalle3Orientations(): void
    {
        $model = $this->createModel();

        $landscape = MediaOrientationEnum::landscape();
        $portrait = MediaOrientationEnum::portrait();
        $square = MediaOrientationEnum::square();

        $this->assertEquals('1792x1024', $model->exposePrepareSize('dall-e-3', $landscape, null));
        $this->assertEquals('1024x1792', $model->exposePrepareSize('dall-e-3', $portrait, null));
        $this->assertEquals('1024x1024', $model->exposePrepareSize('dall-e-3', $square, null));
    }

    /**
     * Tests prepareSizeParam() with DALL-E 2 (only supports square).
     *
     * @return void
     */
    public function testPrepareSizeParamWithDalle2(): void
    {
        $model = $this->createModel();

        $landscape = MediaOrientationEnum::landscape();
        $portrait = MediaOrientationEnum::portrait();

        // DALL-E 2 only supports square images.
        $this->assertEquals('1024x1024', $model->exposePrepareSize('dall-e-2', null, '1:1'));
        $this->assertEquals('1024x1024', $model->exposePrepareSize('dall-e-2', $landscape, null));
        $this->assertEquals('1024x1024', $model->exposePrepareSize('dall-e-2', $portrait, null));
    }

    /**
     * Tests prepareSizeParam() defaults to square.
     *
     * @return void
     */
    public function testPrepareSizeParamDefaultsToSquare(): void
    {
        $model = $this->createModel();

        $this->assertEquals('1024x1024', $model->exposePrepareSize('gpt-image-1', null, null));
        $this->assertEquals('1024x1024', $model->exposePrepareSize('dall-e-3', null, null));
    }

    /**
     * Tests parseImageDataToCandidate() method.
     *
     * @return void
     */
    public function testParseImageDataToCandidate(): void
    {
        $model = $this->createModel();

        $candidate = $model->exposeParseImageDataToCandidate([
            'b64_json' => self::VALID_BASE64_IMAGE,
        ], 0);

        $this->assertNotNull($candidate);
        $this->assertEquals(FinishReasonEnum::stop(), $candidate->getFinishReason());
        $parts = $candidate->getMessage()->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isFile());
        $file = $parts[0]->getFile();
        $this->assertNotNull($file);
        $this->assertEquals(self::VALID_BASE64_IMAGE, $file->getBase64Data());
        $this->assertEquals('image/png', $file->getMimeType());
    }

    /**
     * Tests parseImageDataToCandidate() with custom MIME type.
     *
     * @return void
     */
    public function testParseImageDataToCandidateWithCustomMimeType(): void
    {
        $config = new ModelConfig();
        $config->setOutputMimeType('image/jpeg');
        $model = $this->createModel($config);

        $candidate = $model->exposeParseImageDataToCandidate([
            'b64_json' => self::VALID_BASE64_IMAGE,
        ], 0);

        $file = $candidate->getMessage()->getParts()[0]->getFile();
        $this->assertNotNull($file);
        $this->assertEquals('image/jpeg', $file->getMimeType());
    }

    /**
     * Tests prepareGenerateImageParams() for GPT image model.
     *
     * @return void
     */
    public function testPrepareGenerateImageParamsForGptImageModel(): void
    {
        $config = new ModelConfig();
        $config->setOutputMimeType('image/webp');
        $model = $this->createModel($config, 'gpt-image-1');

        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Generate a cat')])];
        $params = $model->exposePrepareGenerateImageParams($prompt);

        $this->assertEquals('gpt-image-1', $params['model']);
        $this->assertEquals('Generate a cat', $params['prompt']);
        $this->assertEquals('webp', $params['output_format']);
        $this->assertArrayNotHasKey('response_format', $params);
    }

    /**
     * Tests prepareGenerateImageParams() for DALL-E model.
     *
     * @return void
     */
    public function testPrepareGenerateImageParamsForDalleModel(): void
    {
        $model = $this->createModel(null, 'dall-e-3');

        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Generate a cat')])];
        $params = $model->exposePrepareGenerateImageParams($prompt);

        $this->assertEquals('dall-e-3', $params['model']);
        $this->assertEquals('Generate a cat', $params['prompt']);
        $this->assertEquals('b64_json', $params['response_format']);
        $this->assertArrayNotHasKey('output_format', $params);
    }

    /**
     * Tests prepareGenerateImageParams() with size configuration.
     *
     * @return void
     */
    public function testPrepareGenerateImageParamsWithSizeConfig(): void
    {
        $config = new ModelConfig();
        $config->setOutputMediaAspectRatio('3:2');
        $model = $this->createModel($config, 'gpt-image-1');

        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Generate a cat')])];
        $params = $model->exposePrepareGenerateImageParams($prompt);

        $this->assertEquals('1536x1024', $params['size']);
    }
}

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
     * @return MockOpenAiImageGenerationModel
     */
    private function createModel(?ModelConfig $modelConfig = null): MockOpenAiImageGenerationModel
    {
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
                'id' => 'resp_img_123',
                'status' => 'completed',
                'output' => [
                    [
                        'type' => 'image_generation_call',
                        'result' => self::VALID_BASE64_IMAGE,
                    ],
                ],
                'usage' => [
                    'input_tokens' => 50,
                    'output_tokens' => 1000,
                    'total_tokens' => 1050,
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
        $this->assertEquals('resp_img_123', $result->getId());
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
     * Tests getHostModelForImageGeneration() method.
     *
     * @return void
     */
    public function testGetHostModelForImageGeneration(): void
    {
        $model = $this->createModel();

        // For gpt-image-* models, should return gpt-4o as host.
        $this->assertEquals('gpt-4o', $model->exposeGetHostModelForImageGeneration('gpt-image-1'));
        $this->assertEquals('gpt-4o', $model->exposeGetHostModelForImageGeneration('gpt-image-1-mini'));

        // For other models, should return the model itself.
        $this->assertEquals('gpt-4o', $model->exposeGetHostModelForImageGeneration('gpt-4o'));
        $this->assertEquals('gpt-5', $model->exposeGetHostModelForImageGeneration('gpt-5'));
    }

    /**
     * Tests prepareImageGenerationTool() with gpt-image model.
     *
     * @return void
     */
    public function testPrepareImageGenerationToolWithGptImageModel(): void
    {
        $model = $this->createModel();

        $tool = $model->exposePrepareImageGenerationTool('gpt-image-1');

        $this->assertEquals('image_generation', $tool['type']);
        $this->assertEquals('gpt-image-1', $tool['model']);
    }

    /**
     * Tests prepareImageGenerationTool() with size configuration.
     *
     * @return void
     */
    public function testPrepareImageGenerationToolWithSize(): void
    {
        $config = new ModelConfig();
        $config->setOutputMediaAspectRatio('16:9');
        $model = $this->createModel($config);

        $tool = $model->exposePrepareImageGenerationTool('gpt-image-1');

        $this->assertEquals('1792x1024', $tool['size']);
    }

    /**
     * Tests prepareImageGenerationTool() with output format.
     *
     * @return void
     */
    public function testPrepareImageGenerationToolWithOutputFormat(): void
    {
        $config = new ModelConfig();
        $config->setOutputMimeType('image/webp');
        $model = $this->createModel($config);

        $tool = $model->exposePrepareImageGenerationTool('gpt-image-1');

        $this->assertEquals('webp', $tool['output_format']);
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
     * Tests prepareSizeParam() with aspect ratios.
     *
     * @return void
     */
    public function testPrepareSizeParamWithAspectRatios(): void
    {
        $model = $this->createModel();

        $this->assertEquals('1024x1024', $model->exposePrepareSize(null, '1:1'));
        $this->assertEquals('1792x1024', $model->exposePrepareSize(null, '16:9'));
        $this->assertEquals('1024x1792', $model->exposePrepareSize(null, '9:16'));
    }

    /**
     * Tests prepareSizeParam() with orientations.
     *
     * @return void
     */
    public function testPrepareSizeParamWithOrientations(): void
    {
        $model = $this->createModel();

        $this->assertEquals('1792x1024', $model->exposePrepareSize(MediaOrientationEnum::landscape(), null));
        $this->assertEquals('1024x1792', $model->exposePrepareSize(MediaOrientationEnum::portrait(), null));
        $this->assertEquals('1024x1024', $model->exposePrepareSize(MediaOrientationEnum::square(), null));
    }

    /**
     * Tests prepareSizeParam() defaults to square.
     *
     * @return void
     */
    public function testPrepareSizeParamDefaultsToSquare(): void
    {
        $model = $this->createModel();

        $this->assertEquals('1024x1024', $model->exposePrepareSize(null, null));
    }

    /**
     * Tests parseImageGenerationCallToCandidate() method.
     *
     * @return void
     */
    public function testParseImageGenerationCallToCandidate(): void
    {
        $model = $this->createModel();

        $candidate = $model->exposeParseImageGenerationCallToCandidate([
            'type' => 'image_generation_call',
            'result' => self::VALID_BASE64_IMAGE,
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
     * Tests parseImageGenerationCallToCandidate() with custom MIME type.
     *
     * @return void
     */
    public function testParseImageGenerationCallToCandidateWithCustomMimeType(): void
    {
        $config = new ModelConfig();
        $config->setOutputMimeType('image/jpeg');
        $model = $this->createModel($config);

        $candidate = $model->exposeParseImageGenerationCallToCandidate([
            'type' => 'image_generation_call',
            'result' => self::VALID_BASE64_IMAGE,
        ], 0);

        $file = $candidate->getMessage()->getParts()[0]->getFile();
        $this->assertNotNull($file);
        $this->assertEquals('image/jpeg', $file->getMimeType());
    }

    /**
     * Tests parseOutputItemToCandidate() skips non-image output.
     *
     * @return void
     */
    public function testParseOutputItemToCandidateSkipsNonImageOutput(): void
    {
        $model = $this->createModel();

        $result = $model->exposeParseOutputItemToCandidate([
            'type' => 'message',
            'role' => 'assistant',
            'content' => [],
        ], 0);

        $this->assertNull($result);
    }
}

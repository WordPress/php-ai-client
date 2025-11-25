<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\OpenAiCompatibleImplementation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Files\DTO\File;
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
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tests\mocks\MockOpenAiCompatibleTextToSpeechConversionModel;

/**
 * @covers \WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextToSpeechConversionModel
 */
class AbstractOpenAiCompatibleTextToSpeechConversionModelTest extends TestCase
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
        $this->modelMetadata->method('getId')->willReturn('tts-1');
        $this->providerMetadata = $this->createStub(ProviderMetadata::class);
        $this->providerMetadata->method('getName')->willReturn('TestProvider');
        $this->mockHttpTransporter = $this->createMock(HttpTransporterInterface::class);
        $this->mockRequestAuthentication = $this->createMock(RequestAuthenticationInterface::class);
    }

    /**
     * Creates a mock instance of MockOpenAiCompatibleTextToSpeechConversionModel.
     *
     * @param ModelConfig|null $modelConfig The model configuration.
     * @return MockOpenAiCompatibleTextToSpeechConversionModel The mock model instance.
     */
    private function createModel(?ModelConfig $modelConfig = null): MockOpenAiCompatibleTextToSpeechConversionModel
    {
        $model = new MockOpenAiCompatibleTextToSpeechConversionModel(
            $this->modelMetadata,
            $this->providerMetadata
        );
        $model->setHttpTransporter($this->mockHttpTransporter);
        $model->setRequestAuthentication($this->mockRequestAuthentication);
        if ($modelConfig) {
            $model->setConfig($modelConfig);
        }
        return $model;
    }

    /**
     * Tests convertTextToSpeechResult() method on success.
     *
     * @return void
     */
    public function testConvertTextToSpeechResultSuccess(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello world')])];
        // Simulated binary audio data.
        $binaryAudioData = 'fake-binary-audio-data-for-testing';
        $response = new Response(200, ['Content-Type' => ['audio/mpeg']], $binaryAudioData);

        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $model = $this->createModel();
        $result = $model->convertTextToSpeechResult($prompt);

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertCount(1, $result->getCandidates());
        $this->assertEquals(
            base64_encode($binaryAudioData),
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getBase64Data()
        );
        $this->assertEquals(
            'audio/mpeg',
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getMimeType()
        );
        $this->assertEquals(FinishReasonEnum::stop(), $result->getCandidates()[0]->getFinishReason());
    }

    /**
     * Tests convertTextToSpeechResult() with custom voice.
     *
     * @return void
     */
    public function testConvertTextToSpeechResultWithCustomVoice(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Hello')])];
        $binaryAudioData = 'audio-data';
        $response = new Response(200, [], $binaryAudioData);

        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $modelConfig = ModelConfig::fromArray(['outputSpeechVoice' => 'nova']);
        $model = $this->createModel($modelConfig);
        $result = $model->convertTextToSpeechResult($prompt);

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
    }

    /**
     * Tests convertTextToSpeechResult() method on API failure.
     *
     * @return void
     */
    public function testConvertTextToSpeechResultApiFailure(): void
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

        $model->convertTextToSpeechResult($prompt);
    }

    /**
     * Tests prepareConvertTextToSpeechParams() with basic text prompt.
     *
     * @return void
     */
    public function testPrepareConvertTextToSpeechParamsBasicText(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test speech text')])];
        $model = $this->createModel();

        $params = $model->exposePrepareConvertTextToSpeechParams($prompt);

        $this->assertArrayHasKey('model', $params);
        $this->assertEquals('tts-1', $params['model']);
        $this->assertArrayHasKey('input', $params);
        $this->assertEquals('Test speech text', $params['input']);
        $this->assertArrayHasKey('voice', $params);
        $this->assertEquals('alloy', $params['voice']);
    }

    /**
     * Tests prepareConvertTextToSpeechParams() with custom voice.
     *
     * @return void
     */
    public function testPrepareConvertTextToSpeechParamsWithCustomVoice(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['outputSpeechVoice' => 'shimmer']);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareConvertTextToSpeechParams($prompt);

        $this->assertArrayHasKey('voice', $params);
        $this->assertEquals('shimmer', $params['voice']);
    }

    /**
     * Tests prepareConvertTextToSpeechParams() with output MIME type.
     *
     * @return void
     * @dataProvider mimeTypeToFormatProvider
     */
    public function testPrepareConvertTextToSpeechParamsWithOutputMimeType(
        string $mimeType,
        string $expectedFormat
    ): void {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['outputMimeType' => $mimeType]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareConvertTextToSpeechParams($prompt);

        $this->assertArrayHasKey('response_format', $params);
        $this->assertEquals($expectedFormat, $params['response_format']);
    }

    /**
     * Provides MIME types and their expected response_format values.
     *
     * @return array<string, array<string>>
     */
    public function mimeTypeToFormatProvider(): array
    {
        return [
            'audio/mpeg' => ['audio/mpeg', 'mp3'],
            'audio/ogg' => ['audio/ogg', 'opus'],
            'audio/aac' => ['audio/aac', 'aac'],
            'audio/flac' => ['audio/flac', 'flac'],
            'audio/wav' => ['audio/wav', 'wav'],
        ];
    }

    /**
     * Tests prepareConvertTextToSpeechParams() with custom options.
     *
     * @return void
     */
    public function testPrepareConvertTextToSpeechParamsWithCustomOptions(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray([
            'customOptions' => [
                'speed' => 1.5,
                'instructions' => 'Speak in a cheerful tone.',
            ],
        ]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareConvertTextToSpeechParams($prompt);

        $this->assertArrayHasKey('speed', $params);
        $this->assertEquals(1.5, $params['speed']);
        $this->assertArrayHasKey('instructions', $params);
        $this->assertEquals('Speak in a cheerful tone.', $params['instructions']);
    }

    /**
     * Tests prepareConvertTextToSpeechParams() with conflicting custom option.
     *
     * @return void
     */
    public function testPrepareConvertTextToSpeechParamsWithConflictingCustomOption(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Test')])];
        $modelConfig = ModelConfig::fromArray(['customOptions' => ['model' => 'conflicting-model']]);
        $model = $this->createModel($modelConfig);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The custom option "model" conflicts with an existing parameter.');

        $model->exposePrepareConvertTextToSpeechParams($prompt);
    }

    /**
     * Tests prepareInputParam() with a single user message.
     *
     * @return void
     */
    public function testPrepareInputParamSingleUserMessage(): void
    {
        $message = new Message(MessageRoleEnum::user(), [new MessagePart('Hello speech')]);
        $model = $this->createModel();

        $preparedInput = $model->exposePrepareInputParam([$message]);

        $this->assertEquals('Hello speech', $preparedInput);
    }

    /**
     * Tests prepareInputParam() with multiple messages.
     *
     * @return void
     */
    public function testPrepareInputParamMultipleMessages(): void
    {
        $messages = [
            new Message(MessageRoleEnum::user(), [new MessagePart('First part.')]),
            new Message(MessageRoleEnum::user(), [new MessagePart('Second part.')]),
        ];
        $model = $this->createModel();

        $preparedInput = $model->exposePrepareInputParam($messages);

        $this->assertEquals('First part. Second part.', $preparedInput);
    }

    /**
     * Tests prepareInputParam() with multiple message parts.
     *
     * @return void
     */
    public function testPrepareInputParamMultipleMessageParts(): void
    {
        $message = new Message(MessageRoleEnum::user(), [
            new MessagePart('Part one.'),
            new MessagePart('Part two.'),
        ]);
        $model = $this->createModel();

        $preparedInput = $model->exposePrepareInputParam([$message]);

        $this->assertEquals('Part one. Part two.', $preparedInput);
    }

    /**
     * Tests prepareInputParam() with empty messages array.
     *
     * @return void
     */
    public function testPrepareInputParamEmptyMessages(): void
    {
        $model = $this->createModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one message is required for text-to-speech conversion.');

        $model->exposePrepareInputParam([]);
    }

    /**
     * Tests prepareInputParam() with message without text part.
     *
     * @return void
     */
    public function testPrepareInputParamMessageWithoutTextPart(): void
    {
        $message = new Message(
            MessageRoleEnum::user(),
            [new MessagePart(new File('https://example.com/image.png', 'image/png'))]
        );
        $model = $this->createModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one text message part is required for text-to-speech conversion.');

        $model->exposePrepareInputParam([$message]);
    }

    /**
     * Tests throwIfNotSuccessful() with a successful response.
     *
     * @return void
     */
    public function testThrowIfNotSuccessfulSuccess(): void
    {
        $response = new Response(200, [], 'binary-audio-data');
        $model = $this->createModel();
        $model->exposeThrowIfNotSuccessful($response);
        $this->assertTrue(true);
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
     * Tests parseResponseToGenerativeAiResult() with valid binary audio response.
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultValidResponse(): void
    {
        $binaryAudioData = 'test-binary-audio-data';
        $response = new Response(200, [], $binaryAudioData);
        $model = $this->createModel();

        $result = $model->exposeParseResponseToGenerativeAiResult($response, 'audio/mpeg');

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertCount(1, $result->getCandidates());
        $this->assertEquals(
            base64_encode($binaryAudioData),
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getBase64Data()
        );
        $this->assertEquals(
            'audio/mpeg',
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getMimeType()
        );
        $this->assertEquals(FinishReasonEnum::stop(), $result->getCandidates()[0]->getFinishReason());
        $this->assertEquals(MessageRoleEnum::model(), $result->getCandidates()[0]->getMessage()->getRole());
        $this->assertEquals(0, $result->getTokenUsage()->getTotalTokens());
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with different MIME types.
     *
     * @return void
     * @dataProvider audioMimeTypeProvider
     */
    public function testParseResponseToGenerativeAiResultWithDifferentMimeTypes(string $mimeType): void
    {
        $binaryAudioData = 'audio-data-' . $mimeType;
        $response = new Response(200, [], $binaryAudioData);
        $model = $this->createModel();

        $result = $model->exposeParseResponseToGenerativeAiResult($response, $mimeType);

        $this->assertEquals(
            $mimeType,
            $result->getCandidates()[0]->getMessage()->getParts()[0]->getFile()->getMimeType()
        );
    }

    /**
     * Provides different audio MIME types.
     *
     * @return array<string, array<string>>
     */
    public function audioMimeTypeProvider(): array
    {
        return [
            'mp3' => ['audio/mpeg'],
            'ogg' => ['audio/ogg'],
            'aac' => ['audio/aac'],
            'flac' => ['audio/flac'],
            'wav' => ['audio/wav'],
        ];
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with empty body.
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultEmptyBody(): void
    {
        $response = new Response(200, [], '');
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('Unexpected TestProvider API response: Missing the "audio data" key.');

        $model->exposeParseResponseToGenerativeAiResult($response);
    }

    /**
     * Tests parseResponseToGenerativeAiResult() with null body.
     *
     * @return void
     */
    public function testParseResponseToGenerativeAiResultNullBody(): void
    {
        $response = new Response(200, [], null);
        $model = $this->createModel();

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('Unexpected TestProvider API response: Missing the "audio data" key.');

        $model->exposeParseResponseToGenerativeAiResult($response);
    }
}

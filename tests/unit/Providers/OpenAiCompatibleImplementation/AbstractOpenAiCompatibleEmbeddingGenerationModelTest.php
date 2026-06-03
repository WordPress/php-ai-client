<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\OpenAiCompatibleImplementation;

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
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Tests\mocks\MockOpenAiCompatibleEmbeddingGenerationModel;

/**
 * @covers \WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleEmbeddingGenerationModel
 */
class AbstractOpenAiCompatibleEmbeddingGenerationModelTest extends TestCase
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
        $this->modelMetadata->method('getId')->willReturn('test-embedding-model');
        $this->providerMetadata = $this->createStub(ProviderMetadata::class);
        $this->providerMetadata->method('getName')->willReturn('TestProvider');
        $this->mockHttpTransporter = $this->createMock(HttpTransporterInterface::class);
        $this->mockRequestAuthentication = $this->createMock(RequestAuthenticationInterface::class);
    }

    public function testGenerateEmbeddingResultSuccess(): void
    {
        $response = new Response(
            200,
            [],
            json_encode([
                'id' => 'emb-result-123',
                'data' => [
                    ['embedding' => [0.1, 0.2, 0.3], 'index' => 0],
                ],
                'usage' => [
                    'prompt_tokens' => 4,
                    'total_tokens' => 4,
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

        $result = $this->createModel()->generateEmbeddingResult([
            new Message(MessageRoleEnum::user(), [new MessagePart('Hello world')]),
        ]);

        $this->assertInstanceOf(EmbeddingResult::class, $result);
        $this->assertEquals('emb-result-123', $result->getId());
        $this->assertEquals([[0.1, 0.2, 0.3]], $result->getEmbeddings());
        $this->assertEquals(4, $result->getTokenUsage()->getPromptTokens());
        $this->assertEquals(0, $result->getTokenUsage()->getCompletionTokens());
        $this->assertEquals(4, $result->getTokenUsage()->getTotalTokens());
    }

    public function testPrepareGenerateEmbeddingParamsWithBatchAndDimensions(): void
    {
        $modelConfig = ModelConfig::fromArray(['embeddingDimensions' => 3]);
        $model = $this->createModel($modelConfig);

        $params = $model->exposePrepareGenerateEmbeddingParams([
            new Message(MessageRoleEnum::user(), [new MessagePart('First'), new MessagePart('Second')]),
        ]);

        $this->assertEquals('test-embedding-model', $params['model']);
        $this->assertEquals(['First', 'Second'], $params['input']);
        $this->assertEquals(3, $params['dimensions']);
    }

    public function testPrepareGenerateEmbeddingParamsRejectsNonTextParts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The API requires text message parts as embedding input.');

        $this->createModel()->exposePrepareGenerateEmbeddingParams([
            new Message(MessageRoleEnum::user(), [new MessagePart(new File('https://example.com/image.png'))]),
        ]);
    }

    public function testParseResponseWithoutDataThrows(): void
    {
        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturn(new Response(200, [], json_encode(['id' => 'missing-data'])));

        $this->expectException(ResponseException::class);

        $this->createModel()->generateEmbeddingResult([
            new Message(MessageRoleEnum::user(), [new MessagePart('Hello world')]),
        ]);
    }

    private function createModel(?ModelConfig $modelConfig = null): MockOpenAiCompatibleEmbeddingGenerationModel
    {
        $model = new MockOpenAiCompatibleEmbeddingGenerationModel(
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
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\ProviderImplementations\Anthropic;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\TokenLimitReachedException;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * @covers \WordPress\AiClient\ProviderImplementations\Anthropic\AnthropicTextGenerationModel
 */
class AnthropicTextGenerationModelTest extends TestCase
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
        $this->modelMetadata->method('getId')->willReturn('claude-3-5-sonnet');

        $this->providerMetadata = $this->createStub(ProviderMetadata::class);
        $this->providerMetadata->method('getName')->willReturn('Anthropic');

        $this->mockHttpTransporter = $this->createMock(HttpTransporterInterface::class);
        $this->mockRequestAuthentication = $this->createMock(RequestAuthenticationInterface::class);
    }

    /**
     * Creates a mock Anthropic model instance.
     *
     * @return MockAnthropicTextGenerationModel
     */
    private function createModel(): MockAnthropicTextGenerationModel
    {
        return new MockAnthropicTextGenerationModel(
            $this->modelMetadata,
            $this->providerMetadata,
            $this->mockHttpTransporter,
            $this->mockRequestAuthentication
        );
    }

    /**
     * Tests generateTextResult() on a normal successful response.
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
                'id' => 'msg_123',
                'role' => 'assistant',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Hi there!',
                    ],
                ],
                'stop_reason' => 'end_turn',
                'usage' => [
                    'input_tokens' => 10,
                    'output_tokens' => 5,
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
        $this->assertEquals('msg_123', $result->getId());
        $this->assertCount(1, $result->getCandidates());
        $this->assertEquals(
            FinishReasonEnum::stop(),
            $result->getCandidates()[0]->getFinishReason()
        );
        $this->assertEquals('Hi there!', $result->getCandidates()[0]->getMessage()->getParts()[0]->getText());
    }

    /**
     * Tests generateTextResult() throws when token limit is reached without tools.
     *
     * @return void
     */
    public function testGenerateTextResultThrowsTokenLimitReachedException(): void
    {
        $prompt = [new Message(MessageRoleEnum::user(), [new MessagePart('Tell me a very long story')])];
        $response = new Response(
            200,
            [],
            json_encode([
                'id' => 'msg_456',
                'role' => 'assistant',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Once upon a time...',
                    ],
                ],
                'stop_reason' => 'max_tokens',
                'usage' => [
                    'input_tokens' => 25,
                    'output_tokens' => 4096,
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

        $this->expectException(TokenLimitReachedException::class);
        $model->generateTextResult($prompt);
    }
}

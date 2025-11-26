<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\ProviderImplementations\OpenAi;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\ProviderImplementations\OpenAi\OpenAiEmbeddingModel;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Tests\mocks\MockHttpTransporter;
use WordPress\AiClient\Tests\mocks\MockRequestAuthentication;

/**
 * @covers \WordPress\AiClient\ProviderImplementations\OpenAi\OpenAiEmbeddingModel
 */
class OpenAiEmbeddingModelTest extends TestCase
{
    /**
     * Tests generating embeddings result with a mocked HTTP response.
     */
    public function testGenerateEmbeddingsResult(): void
    {
        $metadata = new ModelMetadata(
            'text-embedding-3-small',
            'Text Embedding 3 Small',
            [CapabilityEnum::embeddingGeneration()],
            []
        );
        $providerMetadata = new ProviderMetadata('openai', 'OpenAI', ProviderTypeEnum::cloud());

        $model = new OpenAiEmbeddingModel($metadata, $providerMetadata);

        $transporter = new MockHttpTransporter();
        $model->setHttpTransporter($transporter);
        $model->setRequestAuthentication(new MockRequestAuthentication('test-token'));

        $responseBody = json_encode([
            'id' => 'embed-123',
            'model' => 'text-embedding-3-small',
            'data' => [
                ['embedding' => [0.1, 0.2], 'index' => 0],
                ['embedding' => [0.3, 0.4], 'index' => 1],
            ],
            'usage' => [
                'prompt_tokens' => 20,
                'total_tokens' => 20,
            ],
        ]);
        $transporter->setResponseToReturn(new Response(200, [], $responseBody));

        $messages = [
            new UserMessage([new MessagePart('First document')]),
            new UserMessage([new MessagePart('Second document')]),
        ];

        $result = $model->generateEmbeddingsResult($messages);

        $this->assertSame([[0.1, 0.2], [0.3, 0.4]], $result->toVectors());
        $this->assertEquals('embed-123', $result->getId());
        $this->assertEquals(20, $result->getTokenUsage()->getPromptTokens());

        $request = $transporter->getLastRequest();
        $this->assertNotNull($request);
        $this->assertStringEndsWith('/embeddings', $request->getUri());

        $payload = $request->getData();
        $this->assertIsArray($payload);
        $this->assertSame('text-embedding-3-small', $payload['model']);
        $this->assertSame(['First document', 'Second document'], $payload['input']);
    }
}

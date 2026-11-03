<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\integration\OpenAi;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Results\DTO\Embedding;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Tests\integration\traits\IntegrationTestTrait;
use WordPress\OpenAiAiProvider\Provider\OpenAiProvider;

/**
 * Integration tests for OpenAI embedding generation.
 *
 * These tests make real API calls to OpenAI and require the OPENAI_API_KEY
 * environment variable to be set.
 *
 * An embedding model must always be named explicitly, since embedding vectors are only comparable
 * to other vectors produced by the same model.
 *
 * @group integration
 * @group openai
 *
 * @coversNothing
 */
class EmbeddingGenerationIntegrationTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * The embedding model used by these tests. It supports a configurable output dimensionality.
     */
    private const MODEL_ID = 'text-embedding-3-small';

    /**
     * An OpenAI model that generates text rather than embeddings.
     */
    private const TEXT_MODEL_ID = 'gpt-4o-mini';

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireApiKey('OPENAI_API_KEY');
    }

    /**
     * Tests generating a single embedding from a string prompt.
     */
    public function testSingleEmbeddingGeneration(): void
    {
        $embedding = AiClient::input('PHP powers a large part of the web.')
            ->usingProviderModel('openai', self::MODEL_ID)
            ->generateEmbedding();

        $this->assertInstanceOf(Embedding::class, $embedding);
        $this->assertGreaterThan(0, count($embedding));
        $this->assertSame(count($embedding), $embedding->getDimensions());
        $this->assertIsFloat($embedding->getValues()[0]);
    }

    /**
     * Tests generating a single embedding from a list of inputs.
     */
    public function testSingleEmbeddingGenerationInputs(): void
    {
        $embedding = AiClient::input([
            'PHP powers a large part of the web.',
        ])
            ->usingProviderModel('openai', self::MODEL_ID)
            ->generateEmbedding();

        $this->assertInstanceOf(Embedding::class, $embedding);
        $this->assertGreaterThan(0, count($embedding));
        $this->assertSame(count($embedding), $embedding->getDimensions());
        $this->assertIsFloat($embedding->getValues()[0]);
    }

    /**
     * Tests generating a single embedding from a spread list passed to withInput().
     */
    public function testSingleEmbeddingGenerationWithInput(): void
    {
        $embedding = AiClient::input()
            ->withInput(...['PHP powers a large part of the web.'])
            ->usingProviderModel('openai', self::MODEL_ID)
            ->generateEmbedding();

        $this->assertInstanceOf(Embedding::class, $embedding);
        $this->assertGreaterThan(0, count($embedding));
        $this->assertSame(count($embedding), $embedding->getDimensions());
        $this->assertIsFloat($embedding->getValues()[0]);
    }

    /**
     * Tests generating an embedding from a model instance.
     *
     * A model created by the provider directly has no HTTP transporter or authentication, so this
     * also verifies the builder binds those dependencies.
     */
    public function testEmbeddingGenerationWithModelInstance(): void
    {
        $embedding = AiClient::input('PHP powers a large part of the web.')
            ->usingModel(OpenAiProvider::model(self::MODEL_ID))
            ->generateEmbedding();

        $this->assertInstanceOf(Embedding::class, $embedding);
        $this->assertGreaterThan(0, count($embedding));
    }

    /**
     * Tests generating an embedding with an explicit dimension count.
     */
    public function testEmbeddingGenerationWithDimensions(): void
    {
        $embedding = AiClient::input('PHP powers a large part of the web.')
            ->usingProviderModel('openai', self::MODEL_ID)
            ->usingDimensions(256)
            ->generateEmbedding();

        $this->assertSame(256, $embedding->getDimensions());
        $this->assertCount(256, $embedding->getValues());
    }

    /**
     * Tests generating embeddings for a batch of inputs.
     */
    public function testBatchEmbeddingGeneration(): void
    {
        $embeddings = AiClient::input([
            'PHP powers a large part of the web.',
            'WordPress makes publishing accessible.',
        ])
            ->usingProviderModel('openai', self::MODEL_ID)
            ->usingDimensions(256)
            ->generateEmbeddings();

        // Exercises the positional batch count guard: one vector must be returned per input.
        $this->assertCount(2, $embeddings);
        $this->assertContainsOnlyInstancesOf(Embedding::class, $embeddings);
        $this->assertSame(
            $embeddings[0]->getDimensions(),
            $embeddings[1]->getDimensions()
        );
    }

    /**
     * Tests generating an embedding through the traditional API.
     */
    public function testTraditionalApiWithProviderModelTuple(): void
    {
        $embedding = AiClient::generateEmbedding(
            'PHP powers a large part of the web.',
            ['openai', self::MODEL_ID]
        );

        $this->assertInstanceOf(Embedding::class, $embedding);
        $this->assertGreaterThan(0, count($embedding));
    }

    /**
     * Tests that the embedding result exposes provider metadata and token usage.
     */
    public function testEmbeddingResultMetadataAndTokenUsage(): void
    {
        $result = AiClient::input('PHP powers a large part of the web.')
            ->usingProviderModel('openai', self::MODEL_ID)
            ->generateEmbeddingResult();

        $this->assertInstanceOf(EmbeddingResult::class, $result);
        $this->assertCount(1, $result->getEmbeddings());
        $this->assertSame('openai', $result->getProviderMetadata()->getId());
        $this->assertSame(self::MODEL_ID, $result->getModelMetadata()->getId());
        $this->assertGreaterThan(0, $result->getTokenUsage()->getPromptTokens());
        $this->assertGreaterThan(0, $result->getTokenUsage()->getTotalTokens());
    }

    /**
     * Tests that omitting the model is rejected before any request is made.
     */
    public function testGenerationWithoutModelIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('An embedding model must be specified.');

        AiClient::input('PHP powers a large part of the web.')->generateEmbedding();
    }

    /**
     * Tests that a text generation model is rejected for embedding generation.
     */
    public function testTextGenerationModelIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Model "%s" from provider "openai" does not support embedding generation.',
                self::TEXT_MODEL_ID
            )
        );

        AiClient::input('PHP powers a large part of the web.')
            ->usingProviderModel('openai', self::TEXT_MODEL_ID)
            ->generateEmbedding();
    }

    /**
     * Tests that a file input is rejected for a text-only embedding model.
     *
     * The failure is local: OpenAI's embedding models advertise text input only, so this never
     * reaches the API.
     */
    public function testFileInputIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported options: inputModalities ([image]).');

        AiClient::input(new File('https://example.com/image.jpg', 'image/jpeg'))
            ->usingProviderModel('openai', self::MODEL_ID)
            ->generateEmbedding();
    }

    /**
     * Tests that an unknown model identifier is rejected.
     */
    public function testUnknownModelIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AiClient::input('PHP powers a large part of the web.')
            ->usingProviderModel('openai', 'definitely-not-a-real-model')
            ->generateEmbedding();
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\integration\OpenAi;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Results\DTO\Embedding;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Tests\integration\traits\IntegrationTestTrait;

/**
 * Integration tests for OpenAI embedding generation.
 *
 * These tests make real API calls to OpenAI and require the OPENAI_API_KEY
 * environment variable to be set.
 *
 * @group integration
 * @group openai
 *
 * @coversNothing
 */
class EmbeddingGenerationIntegrationTest extends TestCase
{
    use IntegrationTestTrait;

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
            ->usingProvider('openai')
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
            ->usingProvider('openai')
            ->generateEmbedding();

        $this->assertInstanceOf(Embedding::class, $embedding);
        $this->assertGreaterThan(0, count($embedding));
        $this->assertSame(count($embedding), $embedding->getDimensions());
        $this->assertIsFloat($embedding->getValues()[0]);
    }

    /**
     * Tests generating a single embedding using withInput().
     */
    public function testSingleEmbeddingGenerationWithInput(): void
    {
        $embedding = AiClient::input()
            ->withInput('PHP powers a large part of the web.')
            ->usingProvider('openai')
            ->generateEmbedding();

        $this->assertInstanceOf(Embedding::class, $embedding);
        $this->assertGreaterThan(0, count($embedding));
        $this->assertSame(count($embedding), $embedding->getDimensions());
        $this->assertIsFloat($embedding->getValues()[0]);
    }

    /**
     * Tests generating an embedding with an explicit dimension count.
     */
    public function testEmbeddingGenerationWithDimensions(): void
    {
        $embedding = AiClient::input('PHP powers a large part of the web.')
            ->usingProvider('openai')
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
            ->usingProvider('openai')
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
     * Tests that the embedding result exposes provider metadata and token usage.
     */
    public function testEmbeddingResultMetadataAndTokenUsage(): void
    {
        $result = AiClient::input('PHP powers a large part of the web.')
            ->usingProvider('openai')
            ->generateEmbeddingResult();

        $this->assertInstanceOf(EmbeddingResult::class, $result);
        $this->assertCount(1, $result->getEmbeddings());
        $this->assertSame('openai', $result->getProviderMetadata()->getId());
        $this->assertGreaterThan(0, $result->getTokenUsage()->getPromptTokens());
        $this->assertGreaterThan(0, $result->getTokenUsage()->getTotalTokens());
    }
}

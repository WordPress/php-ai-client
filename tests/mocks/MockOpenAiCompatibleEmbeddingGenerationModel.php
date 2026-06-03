<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleEmbeddingGenerationModel;

/**
 * Mock OpenAI-compatible embedding model for tests.
 */
class MockOpenAiCompatibleEmbeddingGenerationModel extends AbstractOpenAiCompatibleEmbeddingGenerationModel
{
    /**
     * {@inheritDoc}
     */
    protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request {
        return new Request($method, 'https://api.example.test/' . $path, $headers, $data);
    }

    /**
     * Exposes protected request parameter preparation for tests.
     *
     * @param array $prompt The prompt to generate embeddings for.
     * @return array<string, mixed> The parameters for the API request.
     */
    public function exposePrepareGenerateEmbeddingParams(array $prompt): array
    {
        return $this->prepareGenerateEmbeddingParams($prompt);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleImageGenerationModel;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Mock class for AbstractOpenAiCompatibleImageGenerationModel to expose protected methods for testing.
 */
class MockOpenAiCompatibleImageGenerationModel extends AbstractOpenAiCompatibleImageGenerationModel
{
    /**
     * @inheritDoc
     */
    protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request {
        return new Request($method, $path, $headers, $data);
    }

    /**
     * Exposes the protected prepareGenerateImageParams method.
     *
     * @param list<Message> $prompt
     * @return array<string, mixed>
     */
    public function exposePrepareGenerateImageParams(array $prompt): array
    {
        return $this->prepareGenerateImageParams($prompt);
    }

    /**
     * Exposes the protected preparePromptParam method.
     *
     * @param list<Message> $messages
     * @return string
     */
    public function exposePreparePromptParam(array $messages): string
    {
        return $this->preparePromptParam($messages);
    }

    /**
     * Exposes the protected prepareSizeParam method.
     *
     * @param MediaOrientationEnum|null $orientation
     * @param string|null $aspectRatio
     * @return string
     */
    public function exposePrepareSizeParam(?MediaOrientationEnum $orientation, ?string $aspectRatio): string
    {
        return $this->prepareSizeParam($orientation, $aspectRatio);
    }

    /**
     * Exposes the protected throwIfNotSuccessful method.
     *
     * @param Response $response
     */
    public function exposeThrowIfNotSuccessful(Response $response): void
    {
        $this->throwIfNotSuccessful($response);
    }

    /**
     * Exposes the protected parseResponseToGenerativeAiResult method.
     *
     * @param Response $response
     * @param string $expectedMimeType
     * @return GenerativeAiResult
     */
    public function exposeParseResponseToGenerativeAiResult(
        Response $response,
        string $expectedMimeType = 'image/png'
    ): GenerativeAiResult {
        return $this->parseResponseToGenerativeAiResult($response, $expectedMimeType);
    }

    /**
     * Exposes the protected parseResponseChoiceToCandidate method.
     *
     * @param array<string, mixed> $choiceData
     * @param string $expectedMimeType
     * @return \WordPress\AiClient\Results\DTO\Candidate
     */
    public function exposeParseResponseChoiceToCandidate(
        array $choiceData,
        string $expectedMimeType = 'image/png'
    ): \WordPress\AiClient\Results\DTO\Candidate {
        return $this->parseResponseChoiceToCandidate($choiceData, $expectedMimeType);
    }
}

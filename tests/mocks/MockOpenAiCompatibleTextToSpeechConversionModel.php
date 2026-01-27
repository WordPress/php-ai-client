<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextToSpeechConversionModel;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Mock class for AbstractOpenAiCompatibleTextToSpeechConversionModel to expose protected methods for testing.
 */
class MockOpenAiCompatibleTextToSpeechConversionModel extends AbstractOpenAiCompatibleTextToSpeechConversionModel
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
     * Exposes the protected prepareConvertTextToSpeechParams method.
     *
     * @param list<Message> $prompt The prompt to prepare parameters for.
     * @return array<string, mixed> The prepared parameters.
     */
    public function exposePrepareConvertTextToSpeechParams(array $prompt): array
    {
        return $this->prepareConvertTextToSpeechParams($prompt);
    }

    /**
     * Exposes the protected prepareInputParam method.
     *
     * @param list<Message> $messages The messages to prepare.
     * @return string The prepared input parameter.
     */
    public function exposePrepareInputParam(array $messages): string
    {
        return $this->prepareInputParam($messages);
    }

    /**
     * Exposes the protected throwIfNotSuccessful method.
     *
     * @param Response $response The response to check.
     */
    public function exposeThrowIfNotSuccessful(Response $response): void
    {
        $this->throwIfNotSuccessful($response);
    }

    /**
     * Exposes the protected parseResponseToGenerativeAiResult method.
     *
     * @param Response $response The response to parse.
     * @param string $expectedMimeType The expected MIME type.
     * @return GenerativeAiResult The parsed result.
     */
    public function exposeParseResponseToGenerativeAiResult(
        Response $response,
        string $expectedMimeType = 'audio/mpeg'
    ): GenerativeAiResult {
        return $this->parseResponseToGenerativeAiResult($response, $expectedMimeType);
    }
}

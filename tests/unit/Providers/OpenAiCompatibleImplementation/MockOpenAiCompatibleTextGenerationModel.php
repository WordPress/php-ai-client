<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\OpenAiCompatibleImplementation;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Mock class for testing AbstractOpenAiCompatibleTextGenerationModel.
 */
class MockOpenAiCompatibleTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
{
    /**
     * @var HttpTransporterInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockHttpTransporter;

    /**
     * @var RequestAuthenticationInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockRequestAuthentication;

    /**
     * @var GenerativeAiResult|null
     */
    private ?GenerativeAiResult $mockGenerativeAiResult = null;

    /**
     * Constructor.
     *
     * @param ModelMetadata $metadata
     * @param ProviderMetadata $providerMetadata
     * @param HttpTransporterInterface&\PHPUnit\Framework\MockObject\MockObject $mockHttpTransporter
     * @param RequestAuthenticationInterface&\PHPUnit\Framework\MockObject\MockObject $mockRequestAuthentication
     */
    public function __construct(
        ModelMetadata $metadata,
        ProviderMetadata $providerMetadata,
        $mockHttpTransporter,
        $mockRequestAuthentication
    ) {
        parent::__construct($metadata, $providerMetadata);
        $this->mockHttpTransporter = $mockHttpTransporter;
        $this->mockRequestAuthentication = $mockRequestAuthentication;
    }

    /**
     * @inheritdoc
     */
    public function getHttpTransporter(): HttpTransporterInterface
    {
        return $this->mockHttpTransporter;
    }

    /**
     * @inheritdoc
     */
    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        return $this->mockRequestAuthentication;
    }

    /**
     * @inheritdoc
     */
    protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request {
        return new Request($method, 'https://example.com/' . $path, $headers, $data, $this->getRequestOptions());
    }

    /**
     * Sets a mock generative AI result to be returned by parseResponseToGenerativeAiResult.
     *
     * @param GenerativeAiResult $result
     */
    public function setMockGenerativeAiResult(GenerativeAiResult $result): void
    {
        $this->mockGenerativeAiResult = $result;
    }

    /**
     * @inheritdoc
     */
    public function parseResponseToGenerativeAiResult(Response $response): GenerativeAiResult
    {
        if ($this->mockGenerativeAiResult) {
            return $this->mockGenerativeAiResult;
        }
        // Fallback to parent if no mock is set, or implement a basic parsing for testing.
        return parent::parseResponseToGenerativeAiResult($response);
    }

    // Expose protected methods for testing.
    public function exposePrepareGenerateTextParams(array $prompt): array
    {
        return $this->prepareGenerateTextParams($prompt);
    }

    public function exposePrepareMessagesParamWithSystemInstruction(array $prompt, string $systemInstruction): array
    {
        return $this->prepareMessagesParam($prompt, $systemInstruction);
    }

    public function exposePrepareMessagesParam(array $messages): array
    {
        return $this->prepareMessagesParam($messages);
    }

    public function exposeGetMessageRoleString(MessageRoleEnum $role): string
    {
        return $this->getMessageRoleString($role);
    }

    public function exposeGetMessagePartContentData(MessagePart $part): ?array
    {
        return $this->getMessagePartContentData($part);
    }

    public function exposeGetMessagePartToolCallData(MessagePart $part): ?array
    {
        return $this->getMessagePartToolCallData($part);
    }

    public function exposeValidateOutputModalities(array $outputModalities): void
    {
        $this->validateOutputModalities($outputModalities);
    }

    public function exposePrepareOutputModalitiesParam(array $modalities): array
    {
        return $this->prepareOutputModalitiesParam($modalities);
    }

    public function exposePrepareToolsParam(array $functionDeclarations): array
    {
        return $this->prepareToolsParam($functionDeclarations);
    }

    public function exposePrepareResponseFormatParam(?array $outputSchema): array
    {
        return $this->prepareResponseFormatParam($outputSchema);
    }

    public function exposeParseResponseChoiceToCandidate(array $choiceData, int $index = 0): Candidate
    {
        return $this->parseResponseChoiceToCandidate($choiceData, $index);
    }

    public function exposeParseResponseChoiceMessage(array $messageData, int $index = 0): Message
    {
        return $this->parseResponseChoiceMessage($messageData, $index);
    }

    public function exposeParseResponseChoiceMessageParts(array $messageData, int $index = 0): array
    {
        return $this->parseResponseChoiceMessageParts($messageData, $index);
    }

    public function exposeParseResponseChoiceMessageToolCallPart(array $toolCallData): ?MessagePart
    {
        return $this->parseResponseChoiceMessageToolCallPart($toolCallData);
    }
}

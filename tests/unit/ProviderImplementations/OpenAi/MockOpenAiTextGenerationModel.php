<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\ProviderImplementations\OpenAi;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\ProviderImplementations\OpenAi\OpenAiTextGenerationModel;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\WebSearch;

/**
 * Mock class for testing OpenAiTextGenerationModel.
 */
class MockOpenAiTextGenerationModel extends OpenAiTextGenerationModel
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
     * {@inheritDoc}
     */
    public function getHttpTransporter(): HttpTransporterInterface
    {
        return $this->mockHttpTransporter;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        return $this->mockRequestAuthentication;
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
     * {@inheritDoc}
     */
    public function parseResponseToGenerativeAiResult(Response $response): GenerativeAiResult
    {
        if ($this->mockGenerativeAiResult) {
            return $this->mockGenerativeAiResult;
        }
        return parent::parseResponseToGenerativeAiResult($response);
    }

    // Expose protected methods for testing.

    /**
     * Exposes prepareGenerateTextParams for testing.
     *
     * @param list<Message> $prompt
     * @return array<string, mixed>
     */
    public function exposePrepareGenerateTextParams(array $prompt): array
    {
        return $this->prepareGenerateTextParams($prompt);
    }

    /**
     * Exposes prepareInputParam for testing.
     *
     * @param list<Message> $messages
     * @return list<array<string, mixed>>
     */
    public function exposePrepareInputParam(array $messages): array
    {
        return $this->prepareInputParam($messages);
    }

    /**
     * Exposes getMessageInputItem for testing.
     *
     * @param Message $message
     * @return array<string, mixed>|null
     */
    public function exposeGetMessageInputItem(Message $message): ?array
    {
        return $this->getMessageInputItem($message);
    }

    /**
     * Exposes getMessageRoleString for testing.
     *
     * @param MessageRoleEnum $role
     * @return string
     */
    public function exposeGetMessageRoleString(MessageRoleEnum $role): string
    {
        return $this->getMessageRoleString($role);
    }

    /**
     * Exposes getMessagePartData for testing.
     *
     * @param MessagePart $part
     * @return array<string, mixed>|null
     */
    public function exposeGetMessagePartData(MessagePart $part): ?array
    {
        return $this->getMessagePartData($part);
    }

    /**
     * Exposes prepareToolsParam for testing.
     *
     * @param list<FunctionDeclaration>|null $functionDeclarations
     * @param WebSearch|null $webSearch
     * @param bool $codeInterpreter
     * @return list<array<string, mixed>>
     */
    public function exposePrepareToolsParam(
        ?array $functionDeclarations,
        ?WebSearch $webSearch,
        bool $codeInterpreter = false
    ): array {
        return $this->prepareToolsParam($functionDeclarations, $webSearch, $codeInterpreter);
    }

    /**
     * Exposes parseOutputItemToCandidate for testing.
     *
     * @param array<string, mixed> $outputItem
     * @param int $index
     * @param string $responseStatus
     * @return Candidate|null
     */
    public function exposeParseOutputItemToCandidate(
        array $outputItem,
        int $index,
        string $responseStatus
    ): ?Candidate {
        return $this->parseOutputItemToCandidate($outputItem, $index, $responseStatus);
    }

    /**
     * Exposes parseMessageOutputToCandidate for testing.
     *
     * @param array<string, mixed> $outputItem
     * @param int $index
     * @param string $responseStatus
     * @return Candidate
     */
    public function exposeParseMessageOutputToCandidate(
        array $outputItem,
        int $index,
        string $responseStatus
    ): Candidate {
        return $this->parseMessageOutputToCandidate($outputItem, $index, $responseStatus);
    }

    /**
     * Exposes parseFunctionCallOutputToCandidate for testing.
     *
     * @param array<string, mixed> $outputItem
     * @param int $index
     * @return Candidate
     */
    public function exposeParseFunctionCallOutputToCandidate(array $outputItem, int $index): Candidate
    {
        return $this->parseFunctionCallOutputToCandidate($outputItem, $index);
    }

    /**
     * Exposes parseOutputContentToPart for testing.
     *
     * @param array<string, mixed> $contentItem
     * @return MessagePart|null
     */
    public function exposeParseOutputContentToPart(array $contentItem): ?MessagePart
    {
        return $this->parseOutputContentToPart($contentItem);
    }

    /**
     * Exposes parseStatusToFinishReason for testing.
     *
     * @param string $status
     * @param bool $hasFunctionCalls
     * @return FinishReasonEnum
     */
    public function exposeParseStatusToFinishReason(string $status, bool $hasFunctionCalls): FinishReasonEnum
    {
        return $this->parseStatusToFinishReason($status, $hasFunctionCalls);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\ProviderImplementations\AwsBedrock;

use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\ProviderImplementations\AwsBedrock\AwsBedrockTextGenerationModel;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * Mock class for testing AwsBedrockTextGenerationModel.
 */
class MockAwsBedrockTextGenerationModel extends AwsBedrockTextGenerationModel
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
    protected function parseResponseToGenerativeAiResult(Response $response): GenerativeAiResult
    {
        if ($this->mockGenerativeAiResult) {
            return $this->mockGenerativeAiResult;
        }
        return parent::parseResponseToGenerativeAiResult($response);
    }

    // Expose protected methods for testing.

    /**
     * Exposes getRegion for testing.
     *
     * @return string
     */
    public function exposeGetRegion(): string
    {
        return $this->getRegion();
    }

    /**
     * Exposes prepareConverseParams for testing.
     *
     * @param list<Message> $prompt
     * @return array<string, mixed>
     */
    public function exposePrepareConverseParams(array $prompt): array
    {
        return $this->prepareConverseParams($prompt);
    }

    /**
     * Exposes prepareMessagesParam for testing.
     *
     * @param list<Message> $prompt
     * @return list<array<string, mixed>>
     */
    public function exposePrepareMessagesParam(array $prompt): array
    {
        return $this->prepareMessagesParam($prompt);
    }

    /**
     * Exposes prepareFileContent for testing.
     *
     * @param File $file
     * @return array<string, mixed>
     */
    public function exposePrepareFileContent(File $file): array
    {
        return $this->prepareFileContent($file);
    }

    /**
     * Exposes getImageFormat for testing.
     *
     * @param string $mimeType
     * @return string
     */
    public function exposeGetImageFormat(string $mimeType): string
    {
        return $this->getImageFormat($mimeType);
    }

    /**
     * Exposes getDocumentFormat for testing.
     *
     * @param string $mimeType
     * @return string
     */
    public function exposeGetDocumentFormat(string $mimeType): string
    {
        return $this->getDocumentFormat($mimeType);
    }

    /**
     * Exposes prepareFunctionDeclarations for testing.
     *
     * @param list<\WordPress\AiClient\Tools\DTO\FunctionDeclaration> $functionDeclarations
     * @return list<array<string, mixed>>
     */
    public function exposePrepareFunctionDeclarations(array $functionDeclarations): array
    {
        return $this->prepareFunctionDeclarations($functionDeclarations);
    }

    /**
     * Exposes mapStopReason for testing.
     *
     * @param string $stopReason
     * @return FinishReasonEnum
     */
    public function exposeMapStopReason(string $stopReason): FinishReasonEnum
    {
        return $this->mapStopReason($stopReason);
    }
}

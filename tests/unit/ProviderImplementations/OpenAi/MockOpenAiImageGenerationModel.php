<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\ProviderImplementations\OpenAi;

use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\ProviderImplementations\OpenAi\OpenAiImageGenerationModel;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Mock class for testing OpenAiImageGenerationModel.
 */
class MockOpenAiImageGenerationModel extends OpenAiImageGenerationModel
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
     * Exposes prepareGenerateImageParams for testing.
     *
     * @param list<Message> $prompt
     * @return array<string, mixed>
     */
    public function exposePrepareGenerateImageParams(array $prompt): array
    {
        return $this->prepareGenerateImageParams($prompt);
    }

    /**
     * Exposes getHostModelForImageGeneration for testing.
     *
     * @param string $modelId
     * @return string
     */
    public function exposeGetHostModelForImageGeneration(string $modelId): string
    {
        return $this->getHostModelForImageGeneration($modelId);
    }

    /**
     * Exposes prepareImageGenerationTool for testing.
     *
     * @param string $modelId
     * @return array<string, mixed>
     */
    public function exposePrepareImageGenerationTool(string $modelId): array
    {
        return $this->prepareImageGenerationTool($modelId);
    }

    /**
     * Exposes preparePromptParam for testing.
     *
     * @param list<Message> $messages
     * @return string
     */
    public function exposePreparePromptParam(array $messages): string
    {
        return $this->preparePromptParam($messages);
    }

    /**
     * Exposes prepareSizeParam for testing.
     *
     * @param MediaOrientationEnum|null $orientation
     * @param string|null $aspectRatio
     * @return string
     */
    public function exposePrepareSize(?MediaOrientationEnum $orientation, ?string $aspectRatio): string
    {
        return $this->prepareSizeParam($orientation, $aspectRatio);
    }

    /**
     * Exposes parseOutputItemToCandidate for testing.
     *
     * @param array<string, mixed> $outputItem
     * @param int $index
     * @return Candidate|null
     */
    public function exposeParseOutputItemToCandidate(array $outputItem, int $index): ?Candidate
    {
        return $this->parseOutputItemToCandidate($outputItem, $index);
    }

    /**
     * Exposes parseImageGenerationCallToCandidate for testing.
     *
     * @param array<string, mixed> $outputItem
     * @param int $index
     * @return Candidate
     */
    public function exposeParseImageGenerationCallToCandidate(array $outputItem, int $index): Candidate
    {
        return $this->parseImageGenerationCallToCandidate($outputItem, $index);
    }
}

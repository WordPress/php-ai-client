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
    protected function parseResponseToGenerativeAiResult(
        Response $response,
        string $expectedMimeType = 'image/png'
    ): GenerativeAiResult {
        if ($this->mockGenerativeAiResult) {
            return $this->mockGenerativeAiResult;
        }
        return parent::parseResponseToGenerativeAiResult($response, $expectedMimeType);
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
     * Exposes isGptImageModel for testing.
     *
     * @param string $modelId
     * @return bool
     */
    public function exposeIsGptImageModel(string $modelId): bool
    {
        return $this->isGptImageModel($modelId);
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
    public function exposePrepareSize(
        ?MediaOrientationEnum $orientation,
        ?string $aspectRatio
    ): string {
        return $this->prepareSizeParam($orientation, $aspectRatio);
    }

    /**
     * Exposes parseResponseChoiceToCandidate for testing.
     *
     * @param array<string, mixed> $choiceData
     * @param int $index
     * @param string $expectedMimeType
     * @return Candidate
     */
    public function exposeParseResponseChoiceToCandidate(
        array $choiceData,
        int $index,
        string $expectedMimeType = 'image/png'
    ): Candidate {
        return $this->parseResponseChoiceToCandidate($choiceData, $index, $expectedMimeType);
    }
}

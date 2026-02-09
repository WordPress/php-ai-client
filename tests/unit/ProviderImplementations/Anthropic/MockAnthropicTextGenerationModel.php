<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\ProviderImplementations\Anthropic;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\ProviderImplementations\Anthropic\AnthropicTextGenerationModel;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Tools\DTO\FunctionCall;

/**
 * Mock class for testing AnthropicTextGenerationModel.
 */
class MockAnthropicTextGenerationModel extends AnthropicTextGenerationModel
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
     * Constructor.
     *
     * @param ModelMetadata $metadata Model metadata.
     * @param ProviderMetadata $providerMetadata Provider metadata.
     * @param HttpTransporterInterface&\PHPUnit\Framework\MockObject\MockObject $mockHttpTransporter Mock HTTP transporter.
     * @param RequestAuthenticationInterface&\PHPUnit\Framework\MockObject\MockObject $mockRequestAuthentication Mock request authentication.
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
     * Exposes prepareGenerateTextParams() for testing.
     *
     * @param list<Message> $prompt The prompt.
     * @return array<string, mixed> The parameters.
     */
    public function exposePrepareGenerateTextParams(array $prompt): array
    {
        return $this->prepareGenerateTextParams($prompt);
    }

    /**
     * Exposes parseResponseToGenerativeAiResult() for testing.
     *
     * @param Response $response The response.
     * @return GenerativeAiResult The parsed result.
     */
    public function exposeParseResponseToGenerativeAiResult(Response $response): GenerativeAiResult
    {
        return $this->parseResponseToGenerativeAiResult($response);
    }

    /**
     * Exposes isTokenLimitStopReason() for testing.
     *
     * @param string $stopReason The stop reason.
     * @return bool Whether the stop reason indicates token limit.
     */
    public function exposeIsTokenLimitStopReason(string $stopReason): bool
    {
        return $this->isTokenLimitStopReason($stopReason);
    }

    /**
     * Exposes getRequiredParametersByFunctionName() for testing.
     *
     * @return array<string, list<string>> Required parameters indexed by function name.
     */
    public function exposeGetRequiredParametersByFunctionName(): array
    {
        return $this->getRequiredParametersByFunctionName();
    }

    /**
     * Exposes getMissingRequiredParameters() for testing.
     *
     * @param FunctionCall|null $functionCall The function call.
     * @param list<string> $requiredParameters Required parameter names.
     * @return list<string> Missing required parameters.
     */
    public function exposeGetMissingRequiredParameters(?FunctionCall $functionCall, array $requiredParameters): array
    {
        return $this->getMissingRequiredParameters($functionCall, $requiredParameters);
    }

    /**
     * Exposes maybeThrowTokenLimitReachedException() for testing.
     *
     * @param array<string, mixed> $responseData The response data.
     * @param list<MessagePart> $parts Parsed message parts.
     * @return void
     */
    public function exposeMaybeThrowTokenLimitReachedException(array $responseData, array $parts): void
    {
        $this->maybeThrowTokenLimitReachedException($responseData, $parts);
    }
}

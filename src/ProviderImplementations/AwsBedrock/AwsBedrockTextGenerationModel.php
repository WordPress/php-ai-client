<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\AwsBedrock;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;

/**
 * Class for an AWS Bedrock text generation model.
 *
 * @since n.e.x.t
 *
 * @phpstan-type UsageData array{
 *     inputTokens?: int,
 *     outputTokens?: int,
 *     totalTokens?: int
 * }
 *
 * @phpstan-type ContentBlock array{
 *     text?: string,
 *     toolUse?: array{toolUseId: string, name: string, input: array<string, mixed>}
 * }
 *
 * @phpstan-type MessageOutput array{
 *     role: string,
 *     content: list<ContentBlock>
 * }
 *
 * @phpstan-type ResponseData array{
 *     output?: array{message?: MessageOutput},
 *     stopReason?: string,
 *     usage?: UsageData,
 *     metrics?: array{latencyMs?: int}
 * }
 */
class AwsBedrockTextGenerationModel extends AbstractApiBasedModel implements TextGenerationModelInterface
{
    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        /*
         * Since we're calling the AWS Bedrock API here, we need to use the Bedrock specific
         * API key authentication class.
         */
        $requestAuthentication = parent::getRequestAuthentication();
        if (!$requestAuthentication instanceof ApiKeyRequestAuthentication) {
            return $requestAuthentication;
        }
        return new AwsBedrockApiKeyRequestAuthentication($requestAuthentication->getApiKey());
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    final public function generateTextResult(array $prompt): GenerativeAiResult
    {
        $httpTransporter = $this->getHttpTransporter();

        // Prepare request parameters
        $params = $this->prepareConverseParams($prompt);

        // Get region from config
        $region = $this->getRegion();
        $modelId = $this->metadata()->getId();

        // Build request
        $request = new Request(
            HttpMethodEnum::POST(),
            AwsBedrockProvider::url("model/{$modelId}/converse", $region),
            ['Content-Type' => 'application/json'],
            $params,
            $this->getRequestOptions()
        );

        // Add authentication credentials to the request
        $request = $this->getRequestAuthentication()->authenticateRequest($request);

        // Send and process the request
        $response = $httpTransporter->send($request);
        ResponseUtil::throwIfNotSuccessful($response);

        return $this->parseResponseToGenerativeAiResult($response);
    }

    /**
     * Gets the AWS region from the model configuration.
     *
     * @since n.e.x.t
     *
     * @return string The AWS region, defaults to 'us-east-1'.
     */
    protected function getRegion(): string
    {
        $customOptions = $this->getConfig()->getCustomOptions();
        $region = $customOptions['region'] ?? null;

        if (is_string($region)) {
            return $region;
        }

        return 'us-east-1';
    }

    /**
     * Prepares the given prompt and the model configuration into parameters for the Converse API.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt The prompt to generate text for.
     * @return array<string, mixed> The parameters for the Converse API request.
     */
    protected function prepareConverseParams(array $prompt): array
    {
        $config = $this->getConfig();

        $params = [
            'messages' => $this->prepareMessagesParam($prompt),
        ];

        // System instruction
        $systemInstruction = $config->getSystemInstruction();
        if ($systemInstruction) {
            $params['system'] = [
                ['text' => $systemInstruction]
            ];
        }

        // Inference configuration
        $inferenceConfig = [];

        $maxTokens = $config->getMaxTokens();
        if ($maxTokens !== null) {
            $inferenceConfig['maxTokens'] = $maxTokens;
        }

        $temperature = $config->getTemperature();
        if ($temperature !== null) {
            $inferenceConfig['temperature'] = $temperature;
        }

        $topP = $config->getTopP();
        if ($topP !== null) {
            $inferenceConfig['topP'] = $topP;
        }

        $stopSequences = $config->getStopSequences();
        if ($stopSequences !== null && !empty($stopSequences)) {
            $inferenceConfig['stopSequences'] = $stopSequences;
        }

        if (!empty($inferenceConfig)) {
            $params['inferenceConfig'] = $inferenceConfig;
        }

        // Tool configuration
        $functionDeclarations = $config->getFunctionDeclarations();
        if ($functionDeclarations !== null && !empty($functionDeclarations)) {
            $params['toolConfig'] = [
                'tools' => $this->prepareFunctionDeclarations($functionDeclarations)
            ];
        }

        return $params;
    }

    /**
     * Prepares the messages parameter from the prompt messages.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt The prompt messages.
     * @return list<array<string, mixed>> The formatted messages for the API.
     */
    protected function prepareMessagesParam(array $prompt): array
    {
        $messages = [];

        foreach ($prompt as $message) {
            $role = $message->getRole()->value;
            $content = [];

            foreach ($message->getParts() as $part) {
                if ($part->getText() !== null) {
                    $content[] = ['text' => $part->getText()];
                } elseif ($part->getFile() !== null) {
                    $file = $part->getFile();
                    $content[] = $this->prepareFileContent($file);
                } elseif ($part->getFunctionResponse() !== null) {
                    $functionResponse = $part->getFunctionResponse();
                    $content[] = [
                        'toolResult' => [
                            'toolUseId' => $functionResponse->getId(),
                            'content' => [
                                ['json' => $functionResponse->getResponse()]
                            ]
                        ]
                    ];
                } elseif ($part->getFunctionCall() !== null) {
                    $functionCall = $part->getFunctionCall();
                    $content[] = [
                        'toolUse' => [
                            'toolUseId' => $functionCall->getId(),
                            'name' => $functionCall->getName(),
                            'input' => $functionCall->getArgs()
                        ]
                    ];
                }
            }

            $messages[] = [
                'role' => $role,
                'content' => $content
            ];
        }

        return $messages;
    }

    /**
     * Prepares file content for the API request.
     *
     * @since n.e.x.t
     *
     * @param File $file The file to prepare.
     * @return array<string, mixed> The formatted file content.
     * @throws InvalidArgumentException If the file type is not supported.
     */
    protected function prepareFileContent(File $file): array
    {
        $mimeType = $file->getMimeType();
        $base64Data = $file->getBase64Data();

        if ($base64Data === null) {
            throw new InvalidArgumentException('File must have base64 data for Bedrock API.');
        }

        // Handle image files
        if (str_starts_with($mimeType, 'image/')) {
            return [
                'image' => [
                    'format' => $this->getImageFormat($mimeType),
                    'source' => [
                        'bytes' => base64_decode($base64Data)
                    ]
                ]
            ];
        }

        // Handle document files
        if (str_starts_with($mimeType, 'application/pdf') || str_starts_with($mimeType, 'text/')) {
            return [
                'document' => [
                    'format' => $this->getDocumentFormat($mimeType),
                    'name' => 'document',
                    'source' => [
                        'bytes' => base64_decode($base64Data)
                    ]
                ]
            ];
        }

        throw new InvalidArgumentException(
            sprintf('Unsupported file MIME type: %s', $mimeType)
        );
    }

    /**
     * Gets the image format from MIME type.
     *
     * @since n.e.x.t
     *
     * @param string $mimeType The MIME type.
     * @return string The image format for Bedrock API.
     */
    protected function getImageFormat(string $mimeType): string
    {
        $formats = [
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $formats[$mimeType] ?? 'png';
    }

    /**
     * Gets the document format from MIME type.
     *
     * @since n.e.x.t
     *
     * @param string $mimeType The MIME type.
     * @return string The document format for Bedrock API.
     */
    protected function getDocumentFormat(string $mimeType): string
    {
        $formats = [
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'text/html' => 'html',
            'text/csv' => 'csv',
            'text/markdown' => 'md',
        ];

        return $formats[$mimeType] ?? 'txt';
    }

    /**
     * Prepares function declarations for the tool config.
     *
     * @since n.e.x.t
     *
     * @param list<\WordPress\AiClient\Tools\DTO\FunctionDeclaration> $functionDeclarations The function declarations.
     * @return list<array<string, mixed>> The formatted tools for the API.
     */
    protected function prepareFunctionDeclarations(array $functionDeclarations): array
    {
        $tools = [];

        foreach ($functionDeclarations as $declaration) {
            $tools[] = [
                'toolSpec' => [
                    'name' => $declaration->getName(),
                    'description' => $declaration->getDescription(),
                    'inputSchema' => [
                        'json' => $declaration->getParameters()
                    ]
                ]
            ];
        }

        return $tools;
    }

    /**
     * Parses the API response to a GenerativeAiResult.
     *
     * @since n.e.x.t
     *
     * @param Response $response The HTTP response from the API.
     * @return GenerativeAiResult The parsed result.
     * @throws ResponseException If the response data is invalid or missing required fields.
     */
    protected function parseResponseToGenerativeAiResult(Response $response): GenerativeAiResult
    {
        /** @var ResponseData $responseData */
        $responseData = $response->getData();

        if (!isset($responseData['output']['message'])) {
            throw ResponseException::fromMissingData('AWS Bedrock', 'output.message');
        }

        $messageData = $responseData['output']['message'];
        $roleString = $messageData['role'] ?? 'assistant';
        // Bedrock uses 'assistant', map to 'model' for SDK
        $role = $roleString === 'assistant' ? MessageRoleEnum::model() : MessageRoleEnum::from($roleString);

        // Parse content parts
        $parts = [];
        foreach ($messageData['content'] ?? [] as $contentItem) {
            if (isset($contentItem['text'])) {
                $parts[] = new MessagePart($contentItem['text']);
            } elseif (isset($contentItem['toolUse'])) {
                $toolUse = $contentItem['toolUse'];
                $parts[] = new MessagePart(
                    new FunctionCall(
                        $toolUse['toolUseId'],
                        $toolUse['name'],
                        $toolUse['input']
                    )
                );
            }
        }

        $responseMessage = new Message($role, $parts);

        // Parse stop reason
        $stopReason = $responseData['stopReason'] ?? 'end_turn';
        $finishReason = $this->mapStopReason($stopReason);

        // Parse usage
        $usage = $responseData['usage'] ?? [];
        $tokenUsage = new TokenUsage(
            $usage['inputTokens'] ?? 0,
            $usage['outputTokens'] ?? 0,
            $usage['totalTokens'] ?? 0
        );

        $candidate = new Candidate($responseMessage, $finishReason);

        // Additional data
        $additionalData = $responseData;
        unset($additionalData['output'], $additionalData['stopReason'], $additionalData['usage']);

        // Generate a unique ID (Bedrock doesn't provide one in Converse API)
        $id = uniqid('bedrock_', true);

        return new GenerativeAiResult(
            $id,
            [$candidate],
            $tokenUsage,
            $this->providerMetadata(),
            $this->metadata(),
            $additionalData
        );
    }

    /**
     * Maps the Bedrock stop reason to the SDK's FinishReasonEnum.
     *
     * @since n.e.x.t
     *
     * @param string $stopReason The stop reason from Bedrock API.
     * @return FinishReasonEnum The mapped finish reason.
     */
    protected function mapStopReason(string $stopReason): FinishReasonEnum
    {
        $mapping = [
            'end_turn' => FinishReasonEnum::stop(),
            'max_tokens' => FinishReasonEnum::length(),
            'stop_sequence' => FinishReasonEnum::stop(),
            'tool_use' => FinishReasonEnum::toolCalls(),
            'content_filtered' => FinishReasonEnum::contentFilter(),
        ];

        return $mapping[$stopReason] ?? FinishReasonEnum::error();
    }
}

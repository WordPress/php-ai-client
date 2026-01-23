<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\Anthropic;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
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
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\WebSearch;

/**
 * Class for an Anthropic text generation model.
 *
 * @since 0.1.0
 * @since 0.4.0 Enhanced to use Anthropic's primary API instead of OpenAI compatibility layer.
 *
 * @phpstan-type UsageData array{
 *     input_tokens?: int,
 *     output_tokens?: int,
 *     cache_creation_input_tokens?: int,
 *     cache_read_input_tokens?: int
 * }
 * @phpstan-type ResponseData array{
 *     id?: string,
 *     role?: string,
 *     content?: list<array<string, mixed>>,
 *     stop_reason?: string,
 *     usage?: UsageData
 * }
 */
class AnthropicTextGenerationModel extends AbstractApiBasedModel implements TextGenerationModelInterface
{
    /**
     * {@inheritDoc}
     *
     * @since 0.4.0
     */
    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        /*
         * Since we're calling the Anthropic API here, we need to use the Anthropic specific
         * API key authentication class.
         */
        $requestAuthentication = parent::getRequestAuthentication();
        if (!$requestAuthentication instanceof ApiKeyRequestAuthentication) {
            return $requestAuthentication;
        }
        return new AnthropicApiKeyRequestAuthentication($requestAuthentication->getApiKey());
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.4.0
     */
    final public function generateTextResult(array $prompt): GenerativeAiResult
    {
        $httpTransporter = $this->getHttpTransporter();

        $params = $this->prepareGenerateTextParams($prompt);

        $headers = ['Content-Type' => 'application/json'];

        // Add beta header for structured outputs if JSON schema output is requested.
        $config = $this->getConfig();
        if ('application/json' === $config->getOutputMimeType() && $config->getOutputSchema()) {
            $headers['anthropic-beta'] = 'structured-outputs-2025-11-13';
        }

        $request = new Request(
            HttpMethodEnum::POST(),
            AnthropicProvider::url('messages'),
            $headers,
            $params,
            $this->getRequestOptions()
        );

        // Add authentication credentials to the request.
        $request = $this->getRequestAuthentication()->authenticateRequest($request);

        // Send and process the request.
        $response = $httpTransporter->send($request);
        ResponseUtil::throwIfNotSuccessful($response);
        return $this->parseResponseToGenerativeAiResult($response);
    }

    /**
     * Prepares the given prompt and the model configuration into parameters for the API request.
     *
     * @since 0.4.0
     *
     * @param list<Message> $prompt The prompt to generate text for. Either a single message or a list of messages
     *                              from a chat.
     * @return array<string, mixed> The parameters for the API request.
     */
    protected function prepareGenerateTextParams(array $prompt): array
    {
        $config = $this->getConfig();

        $params = [
            'model' => $this->metadata()->getId(),
            'messages' => $this->prepareMessagesParam($prompt),
        ];

        $systemInstruction = $config->getSystemInstruction();
        if ($systemInstruction) {
            $params['system'] = $systemInstruction;
        }

        $maxTokens = $config->getMaxTokens();
        if ($maxTokens !== null) {
            $params['max_tokens'] = $maxTokens;
        } else {
            // The 'max_tokens' parameter is required in the Anthropic API, so we need a default.
            $params['max_tokens'] = 4096;
        }

        $temperature = $config->getTemperature();
        if ($temperature !== null) {
            $params['temperature'] = $temperature;
        }

        $topP = $config->getTopP();
        if ($topP !== null) {
            $params['top_p'] = $topP;
        }

        $topK = $config->getTopK();
        if ($topK !== null) {
            $params['top_k'] = $topK;
        }

        $stopSequences = $config->getStopSequences();
        if (is_array($stopSequences)) {
            $params['stop_sequences'] = $stopSequences;
        }

        $outputMimeType = $config->getOutputMimeType();
        $outputSchema = $config->getOutputSchema();
        if ($outputMimeType === 'application/json' && $outputSchema) {
            $params['output_format'] = [
                'type' => 'json_schema',
                'schema' => $outputSchema,
            ];
        }

        $functionDeclarations = $config->getFunctionDeclarations();
        $webSearch = $config->getWebSearch();
        if (is_array($functionDeclarations) || $webSearch) {
            $params['tools'] = $this->prepareToolsParam($functionDeclarations, $webSearch);
        }

        /*
         * Any custom options are added to the parameters as well.
         * This allows developers to pass other options that may be more niche or not yet supported by the SDK.
         */
        $customOptions = $config->getCustomOptions();
        foreach ($customOptions as $key => $value) {
            if (isset($params[$key])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'The custom option "%s" conflicts with an existing parameter.',
                        $key
                    )
                );
            }
            $params[$key] = $value;
        }

        return $params;
    }

    /**
     * Prepares the messages parameter for the API request.
     *
     * @since 0.4.0
     *
     * @param list<Message> $messages The messages to prepare.
     * @return list<array<string, mixed>> The prepared messages parameter.
     */
    protected function prepareMessagesParam(array $messages): array
    {
        return array_map(
            function (Message $message): array {
                return [
                    'role' => $this->getMessageRoleString($message->getRole()),
                    'content' => array_values(array_filter(array_map(
                        [$this, 'getMessagePartData'],
                        $message->getParts()
                    ))),
                ];
            },
            $messages
        );
    }

    /**
     * Returns the Anthropic API specific role string for the given message role.
     *
     * @since 0.4.0
     *
     * @param MessageRoleEnum $role The message role.
     * @return string The role for the API request.
     */
    protected function getMessageRoleString(MessageRoleEnum $role): string
    {
        if ($role === MessageRoleEnum::model()) {
            return 'assistant';
        }
        return 'user';
    }

    /**
     * Returns the Anthropic API specific data for a message part.
     *
     * @since 0.4.0
     *
     * @param MessagePart $part The message part to get the data for.
     * @return ?array<string, mixed> The data for the message part, or null if not applicable.
     * @throws InvalidArgumentException If the message part type or data is unsupported.
     */
    protected function getMessagePartData(MessagePart $part): ?array
    {
        $type = $part->getType();
        if ($type->isText()) {
            if ($part->getChannel()->isThought()) {
                return [
                    'type' => 'thinking',
                    'thinking' => $part->getText(),
                ];
            }
            return [
                'type' => 'text',
                'text' => $part->getText(),
            ];
        }
        if ($type->isFile()) {
            $file = $part->getFile();
            if (!$file) {
                // This should be impossible due to class internals, but still needs to be checked.
                throw new RuntimeException(
                    'The file typed message part must contain a file.'
                );
            }
            if ($file->isRemote()) {
                $fileUrl = $file->getUrl();
                if (!$fileUrl) {
                    throw new RuntimeException(
                        'The remote file must contain a URL.'
                    );
                }
                if ($file->isDocument()) {
                    return [
                        'type' => 'document',
                        'source' => [
                            'type' => 'url',
                            'url' => $fileUrl,
                        ],
                    ];
                }
                throw new InvalidArgumentException(
                    'Unsupported file type: The API only supports inline files for non-document types.'
                );
            }
            // Else, it is an inline file.
            $fileBase64Data = $file->getBase64Data();
            if (!$fileBase64Data) {
                // This should be impossible due to class internals, but still needs to be checked.
                throw new RuntimeException(
                    'The inline file must contain base64 data.'
                );
            }
            if ($file->isImage()) {
                return [
                    'type' => 'image',
                    'source' => array(
                        'type' => 'base64',
                        'media_type' => $file->getMimeType(),
                        'data' => $fileBase64Data,
                    ),
                ];
            }
            if ($file->isDocument()) {
                return [
                    'type' => 'document',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => $file->getMimeType(),
                        'data' => $fileBase64Data,
                    ],
                ];
            }
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported MIME type "%s" for inline file message part.',
                    $file->getMimeType()
                )
            );
        }
        if ($type->isFunctionCall()) {
            $functionCall = $part->getFunctionCall();
            if (!$functionCall) {
                // This should be impossible due to class internals, but still needs to be checked.
                throw new RuntimeException(
                    'The function_call typed message part must contain a function call.'
                );
            }
            // Ensure null becomes empty object for Anthropic's API which expects an object.
            $input = $functionCall->getArgs();
            if ($input === null) {
                $input = new \stdClass();
            }
            return [
                'type' => 'tool_use',
                'id' => $functionCall->getId(),
                'name' => $functionCall->getName(),
                'input' => $input,
            ];
        }
        if ($type->isFunctionResponse()) {
            $functionResponse = $part->getFunctionResponse();
            if (!$functionResponse) {
                // This should be impossible due to class internals, but still needs to be checked.
                throw new RuntimeException(
                    'The function_response typed message part must contain a function response.'
                );
            }
            return [
                'type' => 'tool_result',
                'tool_use_id' => $functionResponse->getId(),
                'content'     => json_encode($functionResponse->getResponse()),
            ];
        }
        throw new InvalidArgumentException(
            sprintf(
                'Unsupported message part type "%s".',
                $type
            )
        );
    }

    /**
     * Prepares the tools parameter for the API request.
     *
     * @since 0.4.0
     *
     * @param list<FunctionDeclaration>|null $functionDeclarations The function declarations, or null if none.
     * @param WebSearch|null $webSearch The web search config, or null if none.
     * @return list<array<string, mixed>> The prepared tools parameter.
     */
    protected function prepareToolsParam(?array $functionDeclarations, ?WebSearch $webSearch): array
    {
        $tools = [];

        if (is_array($functionDeclarations)) {
            foreach ($functionDeclarations as $functionDeclaration) {
                /*
                 * Anthropic requires input_schema to always be present, even for
                 * functions with no parameters. Use an empty object schema in that case.
                 */
                $inputSchema = $functionDeclaration->getParameters();
                if ($inputSchema === null) {
                    $inputSchema = [
                        'type' => 'object',
                        'properties' => new \stdClass(),
                    ];
                }

                $tools[] = array_filter([
                    'name' => $functionDeclaration->getName(),
                    'description' => $functionDeclaration->getDescription(),
                    'input_schema' => $inputSchema,
                ]);
            }
        }

        if ($webSearch) {
            $tools[] = array_filter([
                'type' => 'web_search_20250305',
                'name' => 'web_search',
                'max_uses' => 1,
                'allowed_domains' => $webSearch->getAllowedDomains(),
                'blocked_domains' => $webSearch->getDisallowedDomains(),
            ]);
        }

        return $tools;
    }

    /**
     * Parses the response from the API endpoint to a generative AI result.
     *
     * @since 0.4.0
     *
     * @param Response $response The response from the API endpoint.
     * @return GenerativeAiResult The parsed generative AI result.
     */
    protected function parseResponseToGenerativeAiResult(Response $response): GenerativeAiResult
    {
        /** @var ResponseData $responseData */
        $responseData = $response->getData();
        if (!isset($responseData['content']) || !$responseData['content']) {
            throw ResponseException::fromMissingData($this->providerMetadata()->getName(), 'content');
        }
        if (!is_array($responseData['content']) || !array_is_list($responseData['content'])) {
            throw ResponseException::fromInvalidData(
                $this->providerMetadata()->getName(),
                'content',
                'The value must be an indexed array.'
            );
        }

        $role = isset($responseData['role']) && 'user' === $responseData['role']
            ? MessageRoleEnum::user()
            : MessageRoleEnum::model();

        $parts = [];
        foreach ($responseData['content'] as $partIndex => $messagePartData) {
            try {
                $newPart = $this->parseResponseContentMessagePart($messagePartData);
                if ($newPart) {
                    $parts[] = $newPart;
                }
            } catch (InvalidArgumentException $e) {
                throw ResponseException::fromInvalidData(
                    $this->providerMetadata()->getName(),
                    "content[{$partIndex}]",
                    $e->getMessage()
                );
            }
        }

        if (!isset($responseData['stop_reason'])) {
            throw ResponseException::fromMissingData(
                $this->providerMetadata()->getName(),
                'stop_reason'
            );
        }

        switch ($responseData['stop_reason']) {
            case 'pause_turn':
            case 'end_turn':
            case 'stop_sequence':
                $finishReason = FinishReasonEnum::stop();
                break;
            case 'max_tokens':
            case 'model_context_window_exceeded':
                $finishReason = FinishReasonEnum::length();
                break;
            case 'refusal':
                $finishReason = FinishReasonEnum::contentFilter();
                break;
            case 'tool_use':
                $finishReason = FinishReasonEnum::toolCalls();
                break;
            default:
                throw ResponseException::fromInvalidData(
                    $this->providerMetadata()->getName(),
                    'stop_reason',
                    sprintf('Invalid stop reason "%s".', $responseData['stop_reason'])
                );
        }

        $candidates = [new Candidate(
            new Message($role, $parts),
            $finishReason
        )];

        $id = isset($responseData['id']) && is_string($responseData['id']) ? $responseData['id'] : '';

        if (isset($responseData['usage']) && is_array($responseData['usage'])) {
            $usage = $responseData['usage'];
            $inputTokens = ($usage['input_tokens'] ?? 0) +
                ($usage['cache_creation_input_tokens'] ?? 0) +
                ($usage['cache_read_input_tokens'] ?? 0);

            $tokenUsage = new TokenUsage(
                $inputTokens,
                $usage['output_tokens'] ?? 0,
                $inputTokens + ($usage['output_tokens'] ?? 0)
            );
        } else {
            $tokenUsage = new TokenUsage(0, 0, 0);
        }

        // Use any other data from the response as provider-specific response metadata.
        $additionalData = $responseData;
        unset(
            $additionalData['id'],
            $additionalData['role'],
            $additionalData['content'],
            $additionalData['stop_reason'],
            $additionalData['usage']
        );

        return new GenerativeAiResult(
            $id,
            $candidates,
            $tokenUsage,
            $this->providerMetadata(),
            $this->metadata(),
            $additionalData
        );
    }

    /**
     * Parses a message part from the content in the API response.
     *
     * @since 0.4.0
     *
     * @param array<string, mixed> $partData The message part data from the API response.
     * @return MessagePart|null The parsed message part, or null to ignore.
     */
    protected function parseResponseContentMessagePart(array $partData): ?MessagePart
    {
        if (!isset($partData['type'])) {
            throw new InvalidArgumentException('Part is missing a type field.');
        }

        switch ($partData['type']) {
            case 'text':
                if (!isset($partData['text']) || !is_string($partData['text'])) {
                    throw new InvalidArgumentException('Part has an invalid text shape.');
                }
                return new MessagePart($partData['text']);
            case 'thinking':
                if (!isset($partData['thinking']) || !is_string($partData['thinking'])) {
                    throw new InvalidArgumentException('Part has an invalid thinking shape.');
                }
                return new MessagePart($partData['thinking'], MessagePartChannelEnum::thought());
            case 'tool_use':
                if (
                    !isset($partData['id']) ||
                    !is_string($partData['id']) ||
                    !isset($partData['name']) ||
                    !is_string($partData['name']) ||
                    !isset($partData['input'])
                ) {
                    throw new InvalidArgumentException('Part has an invalid tool_use shape.');
                }
                /*
                 * Normalize empty object/array to null.
                 * Anthropic returns `input: {}` for functions with no arguments,
                 * which becomes an empty array after json_decode. Semantically,
                 * an empty object means "no arguments".
                 */
                $args = $partData['input'];
                if (is_array($args) && count($args) === 0) {
                    $args = null;
                }
                return new MessagePart(
                    new FunctionCall(
                        $partData['id'],
                        $partData['name'],
                        $args
                    )
                );
            case 'redacted_thinking':
            case 'server_tool_use':
            case 'web_search_tool_result':
                // No special handling for now. These can be ignored for now.
                return null;
        }

        throw new InvalidArgumentException('Part has an unexpected type.');
    }
}

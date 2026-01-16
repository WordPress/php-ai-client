<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
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
 * Class for an OpenAI text generation model using the Responses API.
 *
 * @since n.e.x.t
 *
 * @phpstan-type OutputContentData array{
 *     type: string,
 *     text?: string,
 *     call_id?: string,
 *     name?: string,
 *     arguments?: string
 * }
 * @phpstan-type OutputItemData array{
 *     type: string,
 *     id?: string,
 *     role?: string,
 *     status?: string,
 *     content?: list<OutputContentData>
 * }
 * @phpstan-type UsageData array{
 *     input_tokens?: int,
 *     output_tokens?: int,
 *     total_tokens?: int
 * }
 * @phpstan-type ResponseData array{
 *     id?: string,
 *     status?: string,
 *     output?: list<OutputItemData>,
 *     output_text?: string,
 *     usage?: UsageData
 * }
 */
class OpenAiTextGenerationModel extends AbstractApiBasedModel implements TextGenerationModelInterface
{
    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    final public function generateTextResult(array $prompt): GenerativeAiResult
    {
        $httpTransporter = $this->getHttpTransporter();

        $params = $this->prepareGenerateTextParams($prompt);

        $request = new Request(
            HttpMethodEnum::POST(),
            OpenAiProvider::url('responses'),
            ['Content-Type' => 'application/json'],
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
     * @since n.e.x.t
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
            'input' => $this->prepareInputParam($prompt),
        ];

        $systemInstruction = $config->getSystemInstruction();
        if ($systemInstruction) {
            $params['instructions'] = $systemInstruction;
        }

        $maxTokens = $config->getMaxTokens();
        if ($maxTokens !== null) {
            $params['max_output_tokens'] = $maxTokens;
        }

        $temperature = $config->getTemperature();
        if ($temperature !== null) {
            $params['temperature'] = $temperature;
        }

        $topP = $config->getTopP();
        if ($topP !== null) {
            $params['top_p'] = $topP;
        }

        // Note: OpenAI does not support top_k parameter.

        $outputMimeType = $config->getOutputMimeType();
        $outputSchema = $config->getOutputSchema();
        if ($outputMimeType === 'application/json' && $outputSchema) {
            $params['text'] = [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'response_schema',
                    'schema' => $outputSchema,
                    'strict' => true,
                ],
            ];
        }

        $functionDeclarations = $config->getFunctionDeclarations();
        $webSearch = $config->getWebSearch();
        $customOptions = $config->getCustomOptions();

        if (is_array($functionDeclarations) || $webSearch) {
            $params['tools'] = $this->prepareToolsParam(
                $functionDeclarations,
                $webSearch
            );
        }

        /*
         * Any custom options are added to the parameters as well.
         * This allows developers to pass other options that may be more niche or not yet supported by the SDK.
         */
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
     * Prepares the input parameter for the API request.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to prepare.
     * @return list<array<string, mixed>> The prepared input parameter.
     */
    protected function prepareInputParam(array $messages): array
    {
        $this->validateMessages($messages);

        $input = [];
        foreach ($messages as $message) {
            $inputItem = $this->getMessageInputItem($message);
            if ($inputItem !== null) {
                $input[] = $inputItem;
            }
        }
        return $input;
    }

    /**
     * Validates that the messages are appropriate for the OpenAI Responses API.
     *
     * The OpenAI Responses API requires function calls and function responses to be
     * sent as top-level input items rather than nested in message content. As such,
     * they must be the only part in a message.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to validate.
     * @return void
     * @throws InvalidArgumentException If validation fails.
     */
    protected function validateMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $parts = $message->getParts();

            if (count($parts) <= 1) {
                continue;
            }

            foreach ($parts as $part) {
                $type = $part->getType();

                if ($type->isFunctionCall()) {
                    throw new InvalidArgumentException(
                        'Function call parts must be the only part in a message for the OpenAI Responses API.'
                    );
                }

                if ($type->isFunctionResponse()) {
                    throw new InvalidArgumentException(
                        'Function response parts must be the only part in a message for the OpenAI Responses API.'
                    );
                }
            }
        }
    }

    /**
     * Converts a Message object to a Responses API input item.
     *
     * @since n.e.x.t
     *
     * @param Message $message The message to convert.
     * @return array<string, mixed>|null The input item, or null if the message is empty.
     */
    protected function getMessageInputItem(Message $message): ?array
    {
        $parts = $message->getParts();

        if (empty($parts)) {
            return null;
        }

        $content = [];
        foreach ($parts as $part) {
            $partData = $this->getMessagePartData($part);

            // Function calls and responses are top-level items, not wrapped in a message.
            // validateMessages() ensures these are the only part in a message.
            $partType = $partData['type'] ?? '';
            if ($partType === 'function_call' || $partType === 'function_call_output') {
                return $partData;
            }

            $content[] = $partData;
        }

        return [
            'role' => $this->getMessageRoleString($message->getRole()),
            'content' => $content,
        ];
    }

    /**
     * Returns the OpenAI API specific role string for the given message role.
     *
     * @since n.e.x.t
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
     * Returns the OpenAI API specific data for a message part.
     *
     * @since n.e.x.t
     *
     * @param MessagePart $part The message part to get the data for.
     * @return array<string, mixed> The data for the message part.
     * @throws InvalidArgumentException If the message part type or data is unsupported.
     */
    protected function getMessagePartData(MessagePart $part): array
    {
        $type = $part->getType();
        if ($type->isText()) {
            return [
                'type' => 'input_text',
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
                    // This should be impossible due to class internals, but still needs to be checked.
                    throw new RuntimeException(
                        'The remote file must contain a URL.'
                    );
                }
                if ($file->isImage()) {
                    return [
                        'type' => 'input_image',
                        'image_url' => $fileUrl,
                    ];
                }
                // For other file types, use input_file with URL.
                return [
                    'type' => 'input_file',
                    'file_url' => $fileUrl,
                ];
            }
            // Else, it is an inline file.
            $dataUri = $file->getDataUri();
            if (!$dataUri) {
                // This should be impossible due to class internals, but still needs to be checked.
                throw new RuntimeException(
                    'The inline file must contain base64 data.'
                );
            }
            if ($file->isImage()) {
                return [
                    'type' => 'input_image',
                    'image_url' => $dataUri,
                ];
            }
            // For other file types (like PDF), use input_file.
            return [
                'type' => 'input_file',
                'filename' => 'file',
                'file_data' => $dataUri,
            ];
        }
        if ($type->isFunctionCall()) {
            $functionCall = $part->getFunctionCall();
            if (!$functionCall) {
                throw new RuntimeException(
                    'The function_call typed message part must contain a function call.'
                );
            }
            return [
                'type' => 'function_call',
                'call_id' => $functionCall->getId(),
                'name' => $functionCall->getName(),
                'arguments' => json_encode($functionCall->getArgs()),
            ];
        }
        if ($type->isFunctionResponse()) {
            $functionResponse = $part->getFunctionResponse();
            if (!$functionResponse) {
                throw new RuntimeException(
                    'The function_response typed message part must contain a function response.'
                );
            }
            return [
                'type' => 'function_call_output',
                'call_id' => $functionResponse->getId(),
                'output' => json_encode($functionResponse->getResponse()),
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
     * @since n.e.x.t
     *
     * @param list<FunctionDeclaration>|null $functionDeclarations The function declarations, or null if none.
     * @param WebSearch|null $webSearch The web search config, or null if none.
     * @return list<array<string, mixed>> The prepared tools parameter.
     */
    protected function prepareToolsParam(
        ?array $functionDeclarations,
        ?WebSearch $webSearch
    ): array {
        $tools = [];

        if (is_array($functionDeclarations)) {
            foreach ($functionDeclarations as $functionDeclaration) {
                $tools[] = [
                    'type' => 'function',
                    'name' => $functionDeclaration->getName(),
                    'description' => $functionDeclaration->getDescription(),
                    'parameters' => $functionDeclaration->getParameters(),
                ];
            }
        }

        if ($webSearch) {
            $webSearchTool = ['type' => 'web_search'];
            // Note: The OpenAI Responses API web_search tool may have different filtering options.
            // For now, we use the basic form.
            $tools[] = $webSearchTool;
        }

        return $tools;
    }

    /**
     * Parses the response from the API endpoint to a generative AI result.
     *
     * @since n.e.x.t
     *
     * @param Response $response The response from the API endpoint.
     * @return GenerativeAiResult The parsed generative AI result.
     */
    protected function parseResponseToGenerativeAiResult(Response $response): GenerativeAiResult
    {
        /** @var ResponseData $responseData */
        $responseData = $response->getData();

        if (!isset($responseData['output']) || !$responseData['output']) {
            throw ResponseException::fromMissingData($this->providerMetadata()->getName(), 'output');
        }
        if (!is_array($responseData['output']) || !array_is_list($responseData['output'])) {
            throw ResponseException::fromInvalidData(
                $this->providerMetadata()->getName(),
                'output',
                'The value must be an indexed array.'
            );
        }

        $candidates = [];
        foreach ($responseData['output'] as $index => $outputItem) {
            if (!is_array($outputItem) || array_is_list($outputItem)) {
                throw ResponseException::fromInvalidData(
                    $this->providerMetadata()->getName(),
                    "output[{$index}]",
                    'The value must be an associative array.'
                );
            }

            $candidate = $this->parseOutputItemToCandidate($outputItem, $index, $responseData['status'] ?? 'completed');
            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        $id = isset($responseData['id']) && is_string($responseData['id']) ? $responseData['id'] : '';

        if (isset($responseData['usage']) && is_array($responseData['usage'])) {
            $usage = $responseData['usage'];
            $tokenUsage = new TokenUsage(
                $usage['input_tokens'] ?? 0,
                $usage['output_tokens'] ?? 0,
                $usage['total_tokens'] ?? (($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0))
            );
        } else {
            $tokenUsage = new TokenUsage(0, 0, 0);
        }

        // Use any other data from the response as provider-specific response metadata.
        $additionalData = $responseData;
        unset($additionalData['id'], $additionalData['output'], $additionalData['usage']);

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
     * Parses a single output item from the API response into a Candidate object.
     *
     * @since n.e.x.t
     *
     * @param OutputItemData $outputItem The output item data from the API response.
     * @param int $index The index of the output item in the output array.
     * @param string $responseStatus The overall response status.
     * @return Candidate|null The parsed candidate, or null if the output item should be skipped.
     */
    protected function parseOutputItemToCandidate(array $outputItem, int $index, string $responseStatus): ?Candidate
    {
        $type = $outputItem['type'] ?? '';

        // Handle message output type.
        if ($type === 'message') {
            return $this->parseMessageOutputToCandidate($outputItem, $index, $responseStatus);
        }

        // Handle function_call output type (top-level function call).
        if ($type === 'function_call') {
            return $this->parseFunctionCallOutputToCandidate($outputItem, $index);
        }

        // Skip other output types for now (e.g., image_generation_call is handled in image model).
        return null;
    }

    /**
     * Parses a message output item into a Candidate object.
     *
     * @since n.e.x.t
     *
     * @param OutputItemData $outputItem The output item data.
     * @param int $index The index of the output item.
     * @param string $responseStatus The overall response status.
     * @return Candidate The parsed candidate.
     */
    protected function parseMessageOutputToCandidate(
        array $outputItem,
        int $index,
        string $responseStatus
    ): Candidate {
        $role = isset($outputItem['role']) && $outputItem['role'] === 'user'
            ? MessageRoleEnum::user()
            : MessageRoleEnum::model();

        $parts = [];
        $hasFunctionCalls = false;

        if (isset($outputItem['content']) && is_array($outputItem['content'])) {
            foreach ($outputItem['content'] as $contentIndex => $contentItem) {
                try {
                    $part = $this->parseOutputContentToPart($contentItem);
                    if ($part !== null) {
                        $parts[] = $part;
                        if ($part->getType()->isFunctionCall()) {
                            $hasFunctionCalls = true;
                        }
                    }
                } catch (InvalidArgumentException $e) {
                    throw ResponseException::fromInvalidData(
                        $this->providerMetadata()->getName(),
                        "output[{$index}].content[{$contentIndex}]",
                        $e->getMessage()
                    );
                }
            }
        }

        $message = new Message($role, $parts);
        $finishReason = $this->parseStatusToFinishReason($responseStatus, $hasFunctionCalls);

        return new Candidate($message, $finishReason);
    }

    /**
     * Parses a function_call output item into a Candidate object.
     *
     * @since n.e.x.t
     *
     * @param OutputItemData $outputItem The output item data.
     * @param int $index The index of the output item.
     * @return Candidate The parsed candidate.
     */
    protected function parseFunctionCallOutputToCandidate(array $outputItem, int $index): Candidate
    {
        if (!isset($outputItem['call_id']) || !is_string($outputItem['call_id'])) {
            throw ResponseException::fromMissingData(
                $this->providerMetadata()->getName(),
                "output[{$index}].call_id"
            );
        }
        if (!isset($outputItem['name']) || !is_string($outputItem['name'])) {
            throw ResponseException::fromMissingData(
                $this->providerMetadata()->getName(),
                "output[{$index}].name"
            );
        }

        $args = [];
        if (isset($outputItem['arguments']) && is_string($outputItem['arguments'])) {
            $decoded = json_decode($outputItem['arguments'], true);
            if (is_array($decoded)) {
                $args = $decoded;
            }
        }

        $functionCall = new FunctionCall(
            $outputItem['call_id'],
            $outputItem['name'],
            $args
        );

        $part = new MessagePart($functionCall);
        $message = new Message(MessageRoleEnum::model(), [$part]);

        return new Candidate($message, FinishReasonEnum::toolCalls());
    }

    /**
     * Parses an output content item into a MessagePart.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $contentItem The content item data.
     * @return MessagePart|null The parsed message part, or null to skip.
     */
    protected function parseOutputContentToPart(array $contentItem): ?MessagePart
    {
        $type = $contentItem['type'] ?? '';

        if ($type === 'output_text') {
            if (!isset($contentItem['text']) || !is_string($contentItem['text'])) {
                throw new InvalidArgumentException('Content has an invalid output_text shape.');
            }
            return new MessagePart($contentItem['text']);
        }

        if ($type === 'function_call') {
            if (
                !isset($contentItem['call_id']) ||
                !is_string($contentItem['call_id']) ||
                !isset($contentItem['name']) ||
                !is_string($contentItem['name'])
            ) {
                throw new InvalidArgumentException('Content has an invalid function_call shape.');
            }

            $args = [];
            if (isset($contentItem['arguments']) && is_string($contentItem['arguments'])) {
                $decoded = json_decode($contentItem['arguments'], true);
                if (is_array($decoded)) {
                    $args = $decoded;
                }
            }

            return new MessagePart(
                new FunctionCall(
                    $contentItem['call_id'],
                    $contentItem['name'],
                    $args
                )
            );
        }

        // Skip unknown content types.
        return null;
    }

    /**
     * Parses the response status to a finish reason.
     *
     * @since n.e.x.t
     *
     * @param string $status The response status.
     * @param bool $hasFunctionCalls Whether the response contains function calls.
     * @return FinishReasonEnum The finish reason.
     */
    protected function parseStatusToFinishReason(string $status, bool $hasFunctionCalls): FinishReasonEnum
    {
        switch ($status) {
            case 'completed':
                return $hasFunctionCalls ? FinishReasonEnum::toolCalls() : FinishReasonEnum::stop();
            case 'incomplete':
                return FinishReasonEnum::length();
            case 'failed':
            case 'cancelled':
                return FinishReasonEnum::error();
            default:
                // Default to stop for unknown statuses.
                return FinishReasonEnum::stop();
        }
    }
}

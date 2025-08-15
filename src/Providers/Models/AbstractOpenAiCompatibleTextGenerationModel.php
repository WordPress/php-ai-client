<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models;

use Generator;
use InvalidArgumentException;
use RuntimeException;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\SystemMessage;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;

/**
 * Base class for a text generation model for an OpenAI compatible provider.
 *
 * @since n.e.x.t
 */
abstract class AbstractOpenAiCompatibleTextGenerationModel extends AbstractApiBasedModel implements
    TextGenerationModelInterface
{
    /**
     * @inheritDoc
     */
    final public function generateTextResult(array $prompt): GenerativeAiResult
    {
        $httpTransporter = $this->getHttpTransporter();

        $params = $this->prepareGenerateTextParams($prompt);

        $request = $this->createRequest(HttpMethodEnum::POST(), 'chat/completions', [], $params);
        $response = $httpTransporter->send($request);

        return $this->parseResponseToGenerativeAiResult($response);
    }

    /**
     * @inheritDoc
     */
    final public function streamGenerateTextResult(array $prompt): Generator
    {
        $params = $this->prepareGenerateTextParams($prompt);

        // TODO: Implement streaming support.
        throw new RuntimeException(
            'Streaming is not yet implemented.'
        );
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

        $systemInstruction = $config->getSystemInstruction();
        if ($systemInstruction) {
            $prompt = $this->mergeSystemInstruction($prompt, $systemInstruction);
        }

        $params = [
            'model' => $this->metadata()->getId(),
            'messages' => $this->prepareMessagesParam($prompt),
        ];

        $outputModalities = $config->getOutputModalities();
        if (is_array($outputModalities)) {
            $this->validateOutputModalities($outputModalities);
            if (count($outputModalities) > 1) {
                $params['modalities'] = $this->prepareOutputModalitiesParam($outputModalities);
            }
        }

        // TODO: Prepare other parameters based on config.

        return $params;
    }

    /**
     * Merges the system instruction into the prompt, ensuring that it is the first message.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt The prompt to merge the system instruction into.
     * @param string $systemInstruction The system instruction to merge.
     * @return list<Message> The updated prompt with the system instruction as the first message.
     * @throws InvalidArgumentException If the first message in the prompt is already a system message.
     */
    protected function mergeSystemInstruction(array $prompt, string $systemInstruction): array
    {
        // If the first message is a system message, throw an exception due to a conflict.
        if (isset($prompt[0]) && $prompt[0]->getRole() === MessageRoleEnum::system()) {
            throw new InvalidArgumentException(
                'The first message in the prompt cannot be a system message when using a system instruction.'
            );
        }

        $systemMessage = new SystemMessage([
            new MessagePart($systemInstruction),
        ]);
        array_unshift($prompt, $systemMessage);
        return $prompt;
    }

    /**
     * Prepares the messages parameter for the API request.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to prepare.
     * @return list<array<string, mixed>> The prepared messages parameter.
     */
    protected function prepareMessagesParam(array $messages): array
    {
        return array_map(
            function (Message $message): array {
                // Special case: Function response.
                $messageParts = $message->getParts();
                if (count($messageParts) === 1 && $messageParts[0]->getType()->isFunctionResponse()) {
                    $functionResponse = $messageParts[0]->getFunctionResponse();
                    if (!$functionResponse) {
                        // This should be impossible due to class internals, but still needs to be checked.
                        throw new RuntimeException(
                            'The function response typed message part must contain a function response.'
                        );
                    }
                    return [
                        'role' => 'tool',
                        'content' => json_encode($functionResponse->getResponse()),
                        'tool_call_id' => $functionResponse->getId(),
                    ];
                }
                return [
                    'role' => $this->getMessageRoleString($message->getRole()),
                    'content' => array_filter(array_map(
                        function (MessagePart $part): ?array {
                            return $this->getMessagePartContentData($part);
                        },
                        $messageParts
                    )),
                    'tool_calls' => array_filter(array_map(
                        function (MessagePart $part): ?array {
                            return $this->getMessagePartToolCallData($part);
                        },
                        $messageParts
                    )),
                ];
            },
            $messages
        );
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
        if ($role === MessageRoleEnum::system()) {
            return 'system';
        }
        return 'user';
    }

    /**
     * Returns the OpenAI API specific content data for a message part.
     *
     * @since n.e.x.t
     *
     * @param MessagePart $part The message part to get the data for.
     * @return ?array<string, mixed> The data for the message content part, or null if not applicable.
     * @throws InvalidArgumentException If the message part type or data is unsupported.
     */
    protected function getMessagePartContentData(MessagePart $part): ?array
    {
        $type = $part->getType();
        if ($type->isText()) {
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
            if ($file->getFileType()->isRemote()) {
                if ($file->isImage()) {
                    return [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $file->getUrl(),
                        ],
                    ];
                }
                throw new InvalidArgumentException(
                    sprintf(
                        'Unsupported MIME type "%s" for remote file message part.',
                        $file->getMimeType()
                    )
                );
            }
            // Else, it is an inline file.
            if ($file->isImage()) {
                return [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $file->getBase64Data(),
                    ],
                ];
            }
            if ($file->isAudio()) {
                return [
                    'type' => 'input_audio',
                    'input_audio' => [
                        'data' => $file->getBase64Data(),
                        'format' => '', // TODO: Add method to transform MIME type into file extension.
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
            // Skip, as this is separately included. See `getMessagePartToolCallData()`.
            return null;
        }
        if ($type->isFunctionResponse()) {
            // Special case: Function response.
            throw new InvalidArgumentException(
                'The API only allows a single function response, as the only content of the message.'
            );
        }
        throw new InvalidArgumentException(
            sprintf(
                'Unsupported message part type "%s".',
                $type
            )
        );
    }

    /**
     * Returns the OpenAI API specific tool calls data for a message part.
     *
     * @since n.e.x.t
     *
     * @param MessagePart $part The message part to get the data for.
     * @return ?array<string, mixed> The data for the message tool call part, or null if not applicable.
     * @throws InvalidArgumentException If the message part type or data is unsupported.
     */
    protected function getMessagePartToolCallData(MessagePart $part): ?array
    {
        $type = $part->getType();
        if ($type->isFunctionCall()) {
            $functionCall = $part->getFunctionCall();
            if (!$functionCall) {
                // This should be impossible due to class internals, but still needs to be checked.
                throw new RuntimeException(
                    'The function call typed message part must contain a function call.'
                );
            }
            return [
                'type' => 'function',
                'id' => $functionCall->getId(),
                'function' => [
                    'name' => $functionCall->getName(),
                    'arguments' => json_encode($functionCall->getArgs()),
                ],
            ];
        }
        // All other types are handled in `getMessagePartContentData()`.
        return null;
    }

    /**
     * Validates that the given output modalities to ensure that at least one output modality is text.
     *
     * @since n.e.x.t
     *
     * @param array<ModalityEnum> $outputModalities The output modalities to validate.
     * @throws InvalidArgumentException If no text output modality is present.
     */
    protected function validateOutputModalities(array $outputModalities): void
    {
        // If no output modalities are set, it's fine, as we can assume text.
        if (count($outputModalities) === 0) {
            return;
        }

        foreach ($outputModalities as $modality) {
            if ($modality->isText()) {
                return;
            }
        }

        throw new InvalidArgumentException(
            'A text output modality must be present when generating text.'
        );
    }

    /**
     * Prepares the output modalities parameter for the API request.
     *
     * @since n.e.x.t
     *
     * @param array<ModalityEnum> $modalities The modalities to prepare.
     * @return list<string> The prepared modalities parameter.
     */
    protected function prepareOutputModalitiesParam(array $modalities): array
    {
        $prepared = [];
        foreach ($modalities as $modality) {
            if ($modality->isText()) {
                $prepared[] = 'text';
            } elseif ($modality->isImage()) {
                $prepared[] = 'image';
            } elseif ($modality->isAudio()) {
                $prepared[] = 'audio';
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'Unsupported output modality "%s".',
                        $modality
                    )
                );
            }
        }
        return $prepared;
    }

    /**
     * Creates a request object for the provider's API.
     *
     * @since n.e.x.t
     *
     * @param HttpMethodEnum $method The HTTP method.
     * @param string $path The API endpoint path, relative to the base URI.
     * @param array<string, string|list<string>> $headers The request headers.
     * @param string|array<string, mixed>|null $data The request data.
     * @return Request The request object.
     */
    abstract protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request;

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
        $responseData = $response->getData();
        if (!isset($responseData['choices']) || !$responseData['choices']) {
            throw new RuntimeException(
                'Unexpected API response: Missing the choices key.'
            );
        }
        if (!is_array($responseData['choices'])) {
            throw new RuntimeException(
                'Unexpected API response: The choices key must contain an array.'
            );
        }

        $candidates = [];
        foreach ($responseData['choices'] as $choiceData) {
            if (!is_array($choiceData) || array_is_list($choiceData)) {
                throw new RuntimeException(
                    'Unexpected API response: Each element in the choices key must be an associative array.'
                );
            }

            /** @var array<string, mixed> $choiceData */
            $candidates[] = $this->parseResponseChoiceToCandidate($choiceData);
        }

        $id = isset($responseData['id']) && is_string($responseData['id']) ? $responseData['id'] : '';

        if (isset($responseData['usage']) && is_array($responseData['usage'])) {
            /** @var array<string, int> $usage */
            $usage = $responseData['usage'];

            $tokenUsage = new TokenUsage(
                $usage['prompt_tokens'] ?? 0,
                $usage['completion_tokens'] ?? 0,
                $usage['total_tokens'] ?? 0
            );
        } else {
            $tokenUsage = new TokenUsage(0, 0, 0);
        }

        // Use any other data from the response as provider metadata.
        $providerMetadata = $responseData;
        unset($providerMetadata['id'], $providerMetadata['choices'], $providerMetadata['usage']);

        return new GenerativeAiResult(
            $id,
            $candidates,
            $tokenUsage,
            $providerMetadata
        );
    }

    /**
     * Parses a single choice from the API response into a Candidate object.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $choiceData The choice data from the API response.
     * @return Candidate The parsed candidate.
     * @throws RuntimeException If the choice data is invalid.
     */
    protected function parseResponseChoiceToCandidate(array $choiceData): Candidate
    {
        if (
            !isset($choiceData['message']) ||
            !is_array($choiceData['message']) ||
            array_is_list($choiceData['message'])
        ) {
            throw new RuntimeException(
                'Unexpected API response: Each choice must contain a message key with an associative array.'
            );
        }

        if (!isset($choiceData['finish_reason']) || !is_string($choiceData['finish_reason'])) {
            throw new RuntimeException(
                'Unexpected API response: Each choice must contain a finish_reason key with a string value.'
            );
        }

        /** @var array<string, mixed> $messageData */
        $messageData = $choiceData['message'];
        $message = $this->parseResponseChoiceMessage($messageData);

        switch ($choiceData['finish_reason']) {
            case 'stop':
                $finishReason = FinishReasonEnum::stop();
                break;
            case 'length':
                $finishReason = FinishReasonEnum::length();
                break;
            case 'content_filter':
                $finishReason = FinishReasonEnum::contentFilter();
                break;
            case 'tool_calls':
                $finishReason = FinishReasonEnum::toolCalls();
                break;
            default:
                throw new RuntimeException(
                    sprintf(
                        'Unexpected API response: Invalid finish reason "%s".',
                        $choiceData['finish_reason']
                    )
                );
        }

        return new Candidate($message, $finishReason);
    }

    /**
     * Parses the message from a choice in the API response.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $messageData The message data from the API response.
     * @return Message The parsed message.
     */
    protected function parseResponseChoiceMessage(array $messageData): Message
    {
        $role = isset($messageData['role']) && 'user' === $messageData['role']
            ? MessageRoleEnum::user()
            : MessageRoleEnum::model();

        $parts = $this->parseResponseChoiceMessageParts($messageData);

        return new Message($role, $parts);
    }

    /**
     * Parses the message parts from a choice in the API response.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $messageData The message data from the API response.
     * @return MessagePart[] The parsed message parts.
     */
    protected function parseResponseChoiceMessageParts(array $messageData): array
    {
        $parts = [];

        if (isset($messageData['reasoning_content']) && is_string($messageData['reasoning_content'])) {
            $parts[] = new MessagePart($messageData['reasoning_content'], MessagePartChannelEnum::thought());
        }

        if (isset($messageData['content']) && is_string($messageData['content'])) {
            $parts[] = new MessagePart($messageData['content']);
        }

        if (isset($messageData['tool_calls']) && is_array($messageData['tool_calls'])) {
            foreach ($messageData['tool_calls'] as $toolCallData) {
                /** @var array<string, mixed> $toolCallData */
                $toolCallPart = $this->parseResponseChoiceMessageToolCallPart($toolCallData);
                if (!$toolCallPart) {
                    throw new RuntimeException(
                        'Unexpected API response: The response includes a tool call of an unexpected type.'
                    );
                }
                $parts[] = $toolCallPart;
            }
        }

        return $parts;
    }

    /**
     * Parses a tool call part from the API response.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $toolCallData The tool call data from the API response.
     * @return MessagePart|null The parsed message part for the tool call, or null if not applicable.
     */
    protected function parseResponseChoiceMessageToolCallPart(array $toolCallData): ?MessagePart
    {
        /*
         * For now, only function calls are supported.
         *
         * Not all OpenAI compatible APIs include a 'type' key, so we only check its value if it is set.
         */
        if (
            (isset($toolCallData['type']) && 'function' !== $toolCallData['type']) ||
            !isset($toolCallData['function']) ||
            !is_array($toolCallData['function'])
        ) {
            return null;
        }

        /** @var array<string, mixed> $functionArguments */
        $functionArguments = is_string($toolCallData['function']['arguments'])
            ? json_decode($toolCallData['function']['arguments'], true)
            : $toolCallData['function']['arguments'];

        $functionCall = new FunctionCall(
            isset($toolCallData['id']) && is_string($toolCallData['id']) ?
                $toolCallData['id'] :
                null,
            isset($toolCallData['function']['name']) && is_string($toolCallData['function']['name']) ?
                $toolCallData['function']['name'] :
                null,
            $functionArguments
        );

        return new MessagePart($functionCall);
    }
}

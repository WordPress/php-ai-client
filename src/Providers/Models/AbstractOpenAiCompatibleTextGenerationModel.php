<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models;

use Generator;
use InvalidArgumentException;
use RuntimeException;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\SystemMessage;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

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

        // Something like this.
        $request = $this->createRequest('chat/completions', $params);
        $response = $httpTransporter->sendRequest($request);

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
     * @param string $path The API endpoint path, relative to the base URI.
     * @param array<string, mixed> $params The parameters for the API request.
     * @return RequestInterface The request object.
     */
    abstract protected function createRequest(string $path, array $params): RequestInterface;

    /**
     * Parses the response from the API endpoint to a generative AI result.
     *
     * @since n.e.x.t
     *
     * @param ResponseInterface $response The response from the API endpoint.
     * @return GenerativeAiResult The parsed generative AI result.
     */
    protected function parseResponseToGenerativeAiResult(ResponseInterface $response): GenerativeAiResult
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
        foreach ($responseData['choices'] as $choice) {
            if (!is_array($choice)) {
                throw new RuntimeException(
                    'Unexpected API response: Each element in the choices key must be an associative array.'
                );
            }
            $candidates[] = $this->parseResponseChoiceToCandidate($choice);
        }

        $id = $responseData['id'] ?? '';
        $tokenUsage = new TokenUsage(
            $responseData['usage']['prompt_tokens'] ?? 0,
            $responseData['usage']['completion_tokens'] ?? 0,
            $responseData['usage']['total_tokens'] ?? 0
        );

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
     * @param array<string, mixed> $choice The choice data from the API response.
     * @return Candidate The parsed candidate.
     * @throws RuntimeException If the choice data is invalid.
     */
    protected function parseResponseChoiceToCandidate(array $choice): Candidate
    {
        if (!isset($choice['message']) || !is_array($choice['message'])) {
            throw new RuntimeException(
                'Unexpected API response: Each choice must contain a message key with an associative array.'
            );
        }

        // TODO: Correctly implement this, as this is not correct - 'message' isn't just a string.
        $message = new Message($choice['message']);

        if (!isset($choice['finish_reason']) || !is_string($choice['finish_reason'])) {
            throw new RuntimeException(
                'Unexpected API response: Each choice must contain a finish_reason key with a string value.'
            );
        }
        switch ($choice['finish_reason']) {
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
                        $choice['finish_reason']
                    )
                );
        }

        return new Candidate($message, $finishReason);
    }
}

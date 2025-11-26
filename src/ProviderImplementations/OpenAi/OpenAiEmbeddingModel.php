<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Embeddings\DTO\Embedding;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts\EmbeddingGenerationModelInterface;
use WordPress\AiClient\Results\DTO\EmbeddingResult;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * OpenAI embedding generation model.
 *
 * @since 0.2.0
 *
 * @phpstan-type EmbeddingData array{embedding?: list<float|int>, index?: int}
 * @phpstan-type UsageData array{prompt_tokens?: int, total_tokens?: int}
 * @phpstan-type ResponseData array{id?: string, data?: list<EmbeddingData>, usage?: UsageData, model?: string}
 */
class OpenAiEmbeddingModel extends AbstractApiBasedModel implements EmbeddingGenerationModelInterface
{
    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    public function generateEmbeddingsResult(array $input): EmbeddingResult
    {
        $httpTransporter = $this->getHttpTransporter();

        $params = $this->prepareGenerateEmbeddingsParams($input);

        $request = new Request(
            HttpMethodEnum::POST(),
            OpenAiProvider::url('embeddings'),
            ['Content-Type' => 'application/json'],
            $params
        );

        $request = $this->getRequestAuthentication()->authenticateRequest($request);

        $response = $httpTransporter->send($request);
        ResponseUtil::throwIfNotSuccessful($response);

        return $this->parseResponseToEmbeddingResult($response);
    }

    /**
     * Prepares the request payload for the embeddings endpoint.
     *
     * @param list<Message> $input The embedding inputs.
     * @return array<string, mixed>
     */
    private function prepareGenerateEmbeddingsParams(array $input): array
    {
        if (!array_is_list($input)) {
            throw new InvalidArgumentException('Embedding input must be provided as a list of messages.');
        }

        $preparedInput = array_map(
            fn(Message $message): string => $this->messageToText($message),
            $input
        );

        $params = [
            'model' => $this->metadata()->getId(),
            'input' => $preparedInput,
        ];

        $customOptions = $this->getConfig()->getCustomOptions();
        foreach ($customOptions as $key => $value) {
            if (isset($params[$key])) {
                throw new InvalidArgumentException(
                    sprintf('The custom option "%s" conflicts with an existing parameter.', $key)
                );
            }
            $params[$key] = $value;
        }

        return $params;
    }

    /**
     * Converts a message to a text payload accepted by the embeddings API.
     *
     * @param Message $message The message to convert.
     * @return string
     */
    private function messageToText(Message $message): string
    {
        $parts = [];
        /** @var MessagePart $part */
        foreach ($message->getParts() as $part) {
            $text = $part->getText();
            if ($text !== null) {
                $parts[] = $text;
            }
        }

        if (empty($parts)) {
            throw new InvalidArgumentException(
                'Embedding input messages must contain at least one text part.'
            );
        }

        return implode("\n\n", $parts);
    }

    /**
     * Parses the embeddings response into an EmbeddingResult.
     *
     * @param Response $response The API response.
     * @return EmbeddingResult
     */
    private function parseResponseToEmbeddingResult(Response $response): EmbeddingResult
    {
        /** @var ResponseData $responseData */
        $responseData = $response->getData();
        if ($responseData === null) {
            throw ResponseException::fromInvalidData(
                $this->providerMetadata()->getName(),
                'body',
                'Response body must contain JSON data.'
            );
        }

        if (!isset($responseData['data']) || !is_array($responseData['data']) || empty($responseData['data'])) {
            throw ResponseException::fromMissingData($this->providerMetadata()->getName(), 'data');
        }

        $embeddings = [];
        foreach ($responseData['data'] as $index => $embeddingData) {
            if (
                !is_array($embeddingData) ||
                !isset($embeddingData['embedding']) ||
                !is_array($embeddingData['embedding'])
            ) {
                throw ResponseException::fromInvalidData(
                    $this->providerMetadata()->getName(),
                    sprintf('data[%d].embedding', $index),
                    'The value must be an array of floats.'
                );
            }

            $embeddings[] = new Embedding(
                $embeddingData['embedding'],
                count($embeddingData['embedding'])
            );
        }

        $usageData = $responseData['usage'] ?? [];
        $promptTokens = isset($usageData['prompt_tokens']) ? (int) $usageData['prompt_tokens'] : 0;
        $totalTokens = isset($usageData['total_tokens']) ? (int) $usageData['total_tokens'] : $promptTokens;

        $tokenUsage = new TokenUsage($promptTokens, 0, $totalTokens);

        $resultId = isset($responseData['id']) && is_string($responseData['id'])
            ? $responseData['id']
            : sprintf('%s-embeddings', $this->metadata()->getId());

        $additionalData = [];
        if (isset($responseData['model']) && is_string($responseData['model'])) {
            $additionalData['model'] = $responseData['model'];
        }

        return new EmbeddingResult(
            $resultId,
            $embeddings,
            $tokenUsage,
            $this->providerMetadata(),
            $this->metadata(),
            $additionalData
        );
    }
}

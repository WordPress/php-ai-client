<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\OpenAiCompatibleImplementation;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Messages\DTO\Message;
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
 * Base class for embedding models that implement OpenAI's API format.
 *
 * @since n.e.x.t
 *
 * @phpstan-type EmbeddingData array{embedding?: list<float|int>, index?: int}
 * @phpstan-type UsageData array{prompt_tokens?: int, total_tokens?: int}
 * @phpstan-type ResponseData array{id?: string, data?: list<EmbeddingData>, usage?: UsageData}
 */
abstract class AbstractOpenAiCompatibleEmbeddingGenerationModel extends AbstractApiBasedModel implements
    EmbeddingGenerationModelInterface
{
    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    final public function generateEmbeddingResult(array $prompt): EmbeddingResult
    {
        $params = $this->prepareGenerateEmbeddingParams($prompt);

        $request = $this->createRequest(
            HttpMethodEnum::POST(),
            'embeddings',
            ['Content-Type' => 'application/json'],
            $params
        );

        $request = $this->getRequestAuthentication()->authenticateRequest($request);
        $response = $this->getHttpTransporter()->send($request);
        $this->throwIfNotSuccessful($response);

        return $this->parseResponseToEmbeddingResult($response);
    }

    /**
     * Prepares embedding API request parameters.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt The prompt to generate embeddings for.
     * @return array<string, mixed> The parameters for the API request.
     */
    protected function prepareGenerateEmbeddingParams(array $prompt): array
    {
        $params = [
            'model' => $this->metadata()->getId(),
            'input' => $this->prepareInputParam($prompt),
        ];

        $dimensions = $this->getConfig()->getEmbeddingDimensions();
        if ($dimensions !== null) {
            $params['dimensions'] = $dimensions;
        }

        foreach ($this->getConfig()->getCustomOptions() as $key => $value) {
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
     * Prepares the input parameter for the embedding API request.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to prepare.
     * @return string|list<string> The input parameter.
     */
    protected function prepareInputParam(array $messages)
    {
        $inputs = [];
        foreach ($messages as $message) {
            if (!$message->getRole()->isUser()) {
                throw new InvalidArgumentException('The API requires user messages as embedding input.');
            }

            foreach ($message->getParts() as $part) {
                $text = $part->getText();
                if ($text === null) {
                    throw new InvalidArgumentException('The API requires text message parts as embedding input.');
                }
                $inputs[] = $text;
            }
        }

        if (empty($inputs)) {
            throw new InvalidArgumentException('The API requires at least one text input.');
        }

        return count($inputs) === 1 ? $inputs[0] : $inputs;
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
     * Throws an exception if the response is not successful.
     *
     * @since n.e.x.t
     *
     * @param Response $response The HTTP response to check.
     * @throws ResponseException If the response is not successful.
     */
    protected function throwIfNotSuccessful(Response $response): void
    {
        ResponseUtil::throwIfNotSuccessful($response);
    }

    /**
     * Parses the response from the API endpoint to an embedding result.
     *
     * @since n.e.x.t
     *
     * @param Response $response The response from the API endpoint.
     * @return EmbeddingResult The parsed embedding result.
     */
    protected function parseResponseToEmbeddingResult(Response $response): EmbeddingResult
    {
        /** @var ResponseData $responseData */
        $responseData = $response->getData();
        if (!isset($responseData['data']) || !$responseData['data']) {
            throw ResponseException::fromMissingData($this->providerMetadata()->getName(), 'data');
        }
        if (!is_array($responseData['data'])) {
            throw ResponseException::fromInvalidData(
                $this->providerMetadata()->getName(),
                'data',
                'The value must be an array.'
            );
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
                    "data[{$index}]",
                    'The value must contain an embedding array.'
                );
            }

            $embeddings[] = array_map('floatval', $embeddingData['embedding']);
        }

        $usage = isset($responseData['usage']) && is_array($responseData['usage']) ? $responseData['usage'] : [];
        $tokenUsage = new TokenUsage(
            $usage['prompt_tokens'] ?? 0,
            0,
            $usage['total_tokens'] ?? ($usage['prompt_tokens'] ?? 0)
        );

        $providerMetadata = $responseData;
        unset($providerMetadata['id'], $providerMetadata['data'], $providerMetadata['usage']);

        return new EmbeddingResult(
            $this->getResultId($responseData),
            $embeddings,
            $tokenUsage,
            $this->providerMetadata(),
            $this->metadata(),
            $providerMetadata
        );
    }

    /**
     * Extracts the result ID from the API response data.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $responseData The response data from the API.
     * @return string The result ID.
     */
    protected function getResultId(array $responseData): string
    {
        return isset($responseData['id']) && is_string($responseData['id']) ? $responseData['id'] : '';
    }
}

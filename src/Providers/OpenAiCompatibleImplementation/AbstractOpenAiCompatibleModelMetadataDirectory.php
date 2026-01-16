<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\OpenAiCompatibleImplementation;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Contracts\CachesDataInterface;
use WordPress\AiClient\Common\Traits\WithDataCachingTrait;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Base class for a model metadata directory for providers that implement OpenAI's API format.
 *
 * This abstract class is designed to work with any AI provider that offers an OpenAI-compatible
 * models listing endpoint, including but not limited to Anthropic, Google, and other
 * providers that have adopted OpenAI's models API specification as a standard interface.
 *
 * @since 0.1.0
 *
 * @phpstan-import-type ModelMetadataArrayShape from ModelMetadata
 */
abstract class AbstractOpenAiCompatibleModelMetadataDirectory extends AbstractApiBasedModelMetadataDirectory implements
    CachesDataInterface
{
    use WithDataCachingTrait;

    /**
     * The cache key suffix for the models list.
     *
     * @var string
     */
    private const MODELS_CACHE_KEY = 'models';

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected function sendListModelsRequest(): array
    {
        // Try to get cached data.
        $cachedData = $this->getCache(self::MODELS_CACHE_KEY);
        if (is_array($cachedData)) {
            /** @var array<string, ModelMetadataArrayShape> $cachedData */
            return $this->hydrateModelMetadataMap($cachedData);
        }

        // Fetch from API.
        $httpTransporter = $this->getHttpTransporter();

        $request = $this->createRequest(HttpMethodEnum::GET(), 'models');
        $request = $this->getRequestAuthentication()->authenticateRequest($request);
        $response = $httpTransporter->send($request);

        $this->throwIfNotSuccessful($response);
        $modelsMetadataList = $this->parseResponseToModelMetadataList($response);

        // Parse list to map.
        $modelMetadataMap = [];
        foreach ($modelsMetadataList as $modelMetadata) {
            $modelMetadataMap[$modelMetadata->getId()] = $modelMetadata;
        }

        // Store in cache for 1 day.
        $this->setCache(self::MODELS_CACHE_KEY, $this->dehydrateModelMetadataMap($modelMetadataMap), 86400);

        return $modelMetadataMap;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected function getCachedKeys(): array
    {
        return [self::MODELS_CACHE_KEY];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected function getBaseCacheKey(): string
    {
        return 'ai_client_' . AiClient::VERSION . '_' . md5(static::class);
    }

    /**
     * Converts a model metadata map to an array format suitable for caching.
     *
     * @since n.e.x.t
     *
     * @param array<string, ModelMetadata> $modelMetadataMap The model metadata map.
     * @return array<string, array<string, mixed>> The dehydrated data.
     */
    private function dehydrateModelMetadataMap(array $modelMetadataMap): array
    {
        $data = [];
        foreach ($modelMetadataMap as $modelId => $modelMetadata) {
            $data[$modelId] = $modelMetadata->toArray();
        }
        return $data;
    }

    /**
     * Converts cached array data back to a model metadata map.
     *
     * @since n.e.x.t
     *
     * @param array<string, ModelMetadataArrayShape> $cachedData The cached data.
     * @return array<string, ModelMetadata> The hydrated model metadata map.
     */
    private function hydrateModelMetadataMap(array $cachedData): array
    {
        $modelMetadataMap = [];
        foreach ($cachedData as $modelId => $modelData) {
            $modelMetadataMap[$modelId] = ModelMetadata::fromArray($modelData);
        }
        return $modelMetadataMap;
    }

    /**
     * Creates a request object for the provider's API.
     *
     * @since 0.1.0
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
     * @since 0.1.0
     *
     * @param Response $response The HTTP response to check.
     * @throws ResponseException If the response is not successful.
     */
    protected function throwIfNotSuccessful(Response $response): void
    {
        /*
         * While this method only calls the utility method, it's important to have it here as a protected method so
         * that child classes can override it if needed.
         */
        ResponseUtil::throwIfNotSuccessful($response);
    }

    /**
     * Parses the response from the API endpoint to list models into a list of model metadata objects.
     *
     * @since 0.1.0
     *
     * @param Response $response The response from the API endpoint to list models.
     * @return list<ModelMetadata> List of model metadata objects.
     */
    abstract protected function parseResponseToModelMetadataList(Response $response): array;
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Base class for a model metadata directory for an OpenAI compatible provider.
 *
 * @since n.e.x.t
 */
abstract class AbstractOpenAiCompatibleModelMetadataDirectory extends AbstractApiBasedModelMetadataDirectory
{
    /**
     * @inheritdoc
     */
    protected function sendListModelsRequest(): array
    {
        $httpTransporter = $this->getHttpTransporter();

        $request = $this->createRequest(HttpMethodEnum::GET(), 'models');
        $response = $httpTransporter->send($request);

        $modelsMetadataList = $this->parseResponseToModelMetadataList($response);

        // Parse list to map.
        $modelMetadataMap = [];
        foreach ($modelsMetadataList as $modelMetadata) {
            $modelMetadataMap[$modelMetadata->getId()] = $modelMetadata;
        }
        return $modelMetadataMap;
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
     * Parses the response from the API endpoint to list models into a list of model metadata objects.
     *
     * @since n.e.x.t
     *
     * @param Response $response The response from the API endpoint to list models.
     * @return list<ModelMetadata> List of model metadata objects.
     */
    abstract protected function parseResponseToModelMetadataList(Response $response): array;
}

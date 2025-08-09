<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

use RuntimeException;
use WordPress\AiClient\Providers\AbstractOpenAiCompatibleModelMetadataDirectory;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Class for the OpenAI model metadata directory.
 *
 * @since n.e.x.t
 */
class OpenAiModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    /**
     * @inheritDoc
     */
    protected function createRequest(string $path): RequestInterface
    {
        // Something like this.
        return new OpenAiCompatibleRequest('https://api.openai.com/v1', $path);
    }

    /**
     * @inheritDoc
     */
    protected function parseResponseToModelMetadataList(ResponseInterface $response): array
    {
        $responseData = $response->getData();
        if (!isset($responseData['data']) || !$responseData['data']) {
            throw new RuntimeException(
                'Unexpected API response: Missing the data key.'
            );
        }
        return array_values(
            array_map(
                static function (array $modelData): ModelMetadata {
                    // TODO: Create ModelMetadata object from API data.
                },
                (array) $responseData['data']
            )
        );
    }
}

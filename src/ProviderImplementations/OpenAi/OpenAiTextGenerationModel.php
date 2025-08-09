<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

use WordPress\AiClient\Providers\Models\AbstractOpenAiCompatibleTextGenerationModel;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Class for an OpenAI text generation model.
 *
 * @since n.e.x.t
 */
class OpenAiTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
{
    /**
     * @inheritDoc
     */
    protected function createRequest(string $path, array $params): RequestInterface
    {
        // Something like this.
        return new OpenAiCompatibleRequest('https://api.openai.com/v1', $path);
    }
}

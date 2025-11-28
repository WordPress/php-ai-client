<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextToSpeechConversionModel;

/**
 * Class for an OpenAI text-to-speech conversion model.
 *
 * This class implements text-to-speech conversion using OpenAI's TTS API endpoint.
 * It supports models like 'tts-1', 'tts-1-hd', and 'gpt-4o-mini-tts' with various
 * voice options and audio output formats.
 *
 * @since n.e.x.t
 */
class OpenAiTextToSpeechConversionModel extends AbstractOpenAiCompatibleTextToSpeechConversionModel
{
    /**
     * {@inheritDoc}
     */
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        return new Request(
            $method,
            OpenAiProvider::url($path),
            $headers,
            $data
        );
    }
}

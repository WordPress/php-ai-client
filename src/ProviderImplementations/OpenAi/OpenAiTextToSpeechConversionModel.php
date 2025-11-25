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
     * Creates a request object for the OpenAI API.
     *
     * @since n.e.x.t
     *
     * @param HttpMethodEnum $method The HTTP method.
     * @param string $path The API endpoint path, relative to the base URI.
     * @param array<string, string|list<string>> $headers The request headers.
     * @param string|array<string, mixed>|null $data The request data.
     * @return Request The request object.
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

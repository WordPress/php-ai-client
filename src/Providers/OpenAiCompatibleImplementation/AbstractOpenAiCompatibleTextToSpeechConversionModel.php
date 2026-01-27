<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\OpenAiCompatibleImplementation;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * Base class for a text-to-speech conversion model for providers that implement OpenAI's API format.
 *
 * This abstract class is designed to work with any AI provider that offers an OpenAI-compatible
 * API endpoint for text-to-speech conversion. The OpenAI TTS API accepts text input and returns
 * binary audio data in various formats (mp3, opus, aac, flac, wav, pcm).
 *
 * @since n.e.x.t
 */
abstract class AbstractOpenAiCompatibleTextToSpeechConversionModel extends AbstractApiBasedModel implements
    TextToSpeechConversionModelInterface
{
    /**
     * Default output MIME type.
     *
     * @since n.e.x.t
     */
    protected const DEFAULT_MIME_TYPE = 'audio/mpeg';

    /**
     * Mapping of MIME types to OpenAI response_format values.
     *
     * @since n.e.x.t
     *
     * @var array<string, string>
     */
    protected const MIME_TYPE_TO_FORMAT = [
        'audio/mpeg' => 'mp3',
        'audio/mp3' => 'mp3',
        'audio/ogg' => 'opus',
        'audio/opus' => 'opus',
        'audio/aac' => 'aac',
        'audio/flac' => 'flac',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/pcm' => 'pcm',
    ];

    /**
     * Mapping of OpenAI response_format values to MIME types.
     *
     * @since n.e.x.t
     *
     * @var array<string, string>
     */
    protected const FORMAT_TO_MIME_TYPE = [
        'mp3' => 'audio/mpeg',
        'opus' => 'audio/ogg',
        'aac' => 'audio/aac',
        'flac' => 'audio/flac',
        'wav' => 'audio/wav',
        'pcm' => 'audio/pcm',
    ];

    /**
     * Converts text to speech.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt Array of messages containing the text to convert to speech.
     * @return GenerativeAiResult Result containing generated speech audio.
     */
    public function convertTextToSpeechResult(array $prompt): GenerativeAiResult
    {
        $httpTransporter = $this->getHttpTransporter();

        $params = $this->prepareConvertTextToSpeechParams($prompt);

        $request = $this->createRequest(
            HttpMethodEnum::POST(),
            'audio/speech',
            ['Content-Type' => 'application/json'],
            $params
        );

        // Add authentication credentials to the request.
        $request = $this->getRequestAuthentication()->authenticateRequest($request);

        // Send and process the request.
        $response = $httpTransporter->send($request);
        $this->throwIfNotSuccessful($response);

        // Determine the expected MIME type from the response_format parameter.
        $responseFormat = $params['response_format'] ?? 'mp3';
        $expectedMimeType = self::FORMAT_TO_MIME_TYPE[$responseFormat] ?? self::DEFAULT_MIME_TYPE;

        return $this->parseResponseToGenerativeAiResult($response, $expectedMimeType);
    }

    /**
     * Prepares the given prompt and the model configuration into parameters for the API request.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt The prompt containing text to convert to speech.
     * @return array<string, mixed> The parameters for the API request.
     */
    protected function prepareConvertTextToSpeechParams(array $prompt): array
    {
        $config = $this->getConfig();

        $params = [
            'model' => $this->metadata()->getId(),
            'input' => $this->prepareInputParam($prompt),
        ];

        $voice = $config->getOutputSpeechVoice();
        if ($voice !== null) {
            $params['voice'] = $voice;
        }

        $outputMimeType = $config->getOutputMimeType();
        if ($outputMimeType !== null && isset(self::MIME_TYPE_TO_FORMAT[$outputMimeType])) {
            $params['response_format'] = self::MIME_TYPE_TO_FORMAT[$outputMimeType];
        }

        /*
         * Any custom options are added to the parameters as well.
         * This allows developers to pass other options that may be more niche or not yet supported by the SDK,
         * such as 'speed' (0.25 to 4.0) or 'instructions' (for gpt-4o-mini-tts models).
         */
        $customOptions = $config->getCustomOptions();
        foreach ($customOptions as $key => $value) {
            if (isset($params[$key])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'The custom option "%s" conflicts with an existing parameter.',
                        $key
                    )
                );
            }
            $params[$key] = $value;
        }

        return $params;
    }

    /**
     * Prepares the input parameter for the API request.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to prepare.
     * @return string The prepared input parameter containing the text to convert.
     */
    protected function prepareInputParam(array $messages): string
    {
        if (count($messages) === 0) {
            throw new InvalidArgumentException(
                'At least one message is required for text-to-speech conversion.'
            );
        }

        // Concatenate text from all messages.
        $textParts = [];
        foreach ($messages as $message) {
            foreach ($message->getParts() as $part) {
                $text = $part->getText();
                if ($text !== null) {
                    $textParts[] = $text;
                }
            }
        }

        if (count($textParts) === 0) {
            throw new InvalidArgumentException(
                'At least one text message part is required for text-to-speech conversion.'
            );
        }

        return implode(' ', $textParts);
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
        /*
         * While this method only calls the utility method, it's important to have it here as a protected method so
         * that child classes can override it if needed.
         */
        ResponseUtil::throwIfNotSuccessful($response);
    }

    /**
     * Parses the response from the API endpoint to a generative AI result.
     *
     * The OpenAI TTS API returns binary audio data directly in the response body,
     * not as JSON. This method handles that binary response format.
     *
     * @since n.e.x.t
     *
     * @param Response $response The response from the API endpoint.
     * @param string $expectedMimeType The expected MIME type of the audio response.
     * @return GenerativeAiResult The parsed generative AI result.
     */
    protected function parseResponseToGenerativeAiResult(
        Response $response,
        string $expectedMimeType = 'audio/mpeg'
    ): GenerativeAiResult {
        $binaryData = $response->getBody();

        if ($binaryData === '' || $binaryData === null) {
            throw ResponseException::fromMissingData($this->providerMetadata()->getName(), 'audio data');
        }

        // Encode the binary audio data as base64.
        $base64Data = base64_encode($binaryData);

        // Create a File object with the audio data.
        $audioFile = new File($base64Data, $expectedMimeType);

        $parts = [new MessagePart($audioFile)];
        $message = new Message(MessageRoleEnum::model(), $parts);
        $candidate = new Candidate($message, FinishReasonEnum::stop());

        // TTS API does not return token usage information.
        $tokenUsage = new TokenUsage(0, 0, 0);

        return new GenerativeAiResult(
            '',
            [$candidate],
            $tokenUsage,
            $this->providerMetadata(),
            $this->metadata(),
            []
        );
    }
}

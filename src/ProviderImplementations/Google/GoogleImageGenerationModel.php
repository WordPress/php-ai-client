<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\Google;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;

/**
 * Class for a Google image generation model.
 *
 * This caters for Gemini models that can generate images as part of their multimodal output capabilities as well
 * as for more traditional image generation models such as Imagen.
 *
 * @since 0.1.0
 * @since n.e.x.t Enhanced to use Google's primary API instead of OpenAI compatibility layer.
 *
 * @phpstan-type PredictionData array{
 *     bytesBase64Encoded?: string,
 *     url?: string,
 *     mimeType?: string
 * }
 * @phpstan-type ResponseData array{
 *     id?: string,
 *     predictions?: list<PredictionData>
 * }
 */
class GoogleImageGenerationModel extends AbstractApiBasedModel implements ImageGenerationModelInterface
{
    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function generateImageResult(array $prompt): GenerativeAiResult
    {
        /*
         * Gemini models that can generate images are multimodal and therefore
         * go through the more flexible `generateContent` endpoint, which is
         * used by the `GoogleTextGenerationModel` class.
         */
        if (str_starts_with($this->metadata()->getId(), 'gemini-')) {
            $multimodalOutputModel = new GoogleTextGenerationModel($this->metadata(), $this->providerMetadata());
            $multimodalOutputModel->setConfig($this->getConfig());
            $requestOptions = $this->getRequestOptions();
            if ($requestOptions) {
                $multimodalOutputModel->setRequestOptions($requestOptions);
            }
            return $multimodalOutputModel->generateTextResult($prompt);
        }

        $httpTransporter = $this->getHttpTransporter();

        $params = $this->prepareGenerateImageParams($prompt);

        $request = new Request(
            HttpMethodEnum::POST(),
            GoogleProvider::url("models/{$this->metadata()->getId()}:predict"),
            ['Content-Type' => 'application/json'],
            $params,
            $this->getRequestOptions()
        );

        // Add authentication credentials to the request.
        $request = $this->getRequestAuthentication()->authenticateRequest($request);

        // Send and process the request.
        $response = $httpTransporter->send($request);
        ResponseUtil::throwIfNotSuccessful($response);
        return $this->parseResponseToGenerativeAiResult(
            $response,
            isset($params['parameters']) &&
            is_array($params['parameters']) &&
            isset($params['parameters']['outputOptions']) &&
            is_array($params['parameters']['outputOptions']) &&
            isset($params['parameters']['outputOptions']['mimeType']) &&
            is_string($params['parameters']['outputOptions']['mimeType']) ?
                $params['parameters']['outputOptions']['mimeType'] :
                'image/png'
        );
    }

    /**
     * Prepares the given prompt and the model configuration into parameters for the API request.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt The prompt to generate an image for. Either a single message or a list of messages
     *                              from a chat. However as of today, Google image generation endpoints only support a
     *                              single user message.
     * @return array<string, mixed> The parameters for the API request.
     */
    protected function prepareGenerateImageParams(array $prompt): array
    {
        $config = $this->getConfig();

        $params = [
            'instances' => [
                ['prompt' => $this->preparePromptParam($prompt)],
            ],
            'parameters' => ['sampleCount' => 1],
        ];

        $candidateCount = $config->getCandidateCount();
        if ($candidateCount !== null) {
            $params['parameters']['sampleCount'] = $candidateCount;
        }

        if ($config->getOutputFileType() && $config->getOutputFileType()->isRemote()) {
            throw new InvalidArgumentException(
                'Unsupported output file type: Only inline is supported.'
            );
        }

        $outputMimeType = $config->getOutputMimeType();
        if ($outputMimeType !== null) {
            $params['parameters']['outputOptions'] = ['mimeType' => $outputMimeType];
        }

        $outputMediaOrientation = $config->getOutputMediaOrientation();
        $outputMediaAspectRatio = $config->getOutputMediaAspectRatio();
        if ($outputMediaOrientation !== null || $outputMediaAspectRatio !== null) {
            $params['aspectRatio'] = $this->prepareAspectRatioParam($outputMediaOrientation, $outputMediaAspectRatio);
        }

        /*
         * Any custom options are added to the parameters as well.
         * This allows developers to pass other options that may be more niche or not yet supported by the SDK.
         */
        $customOptions = $config->getCustomOptions();
        foreach ($customOptions as $key => $value) {
            // Special case: Support custom values as part of `parameters`.
            if (str_starts_with($key, 'parameters.')) {
                $key = substr($key, strlen('parameters.'));
                if (!isset($params['parameters']) || !is_array($params['parameters'])) {
                    $params['parameters'] = [$key => $value];
                    continue;
                }
                if (isset($params['parameters'][$key])) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'The custom parameters option "%s" conflicts with an existing parameter.',
                            $key
                        )
                    );
                }
                $params['parameters'][$key] = $value;
                continue;
            }

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
     * Prepares the prompt parameter for the API request.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to prepare. However as of today, Google image generation endpoints
     *                                only support a single user message.
     * @return string The prepared prompt parameter.
     */
    protected function preparePromptParam(array $messages): string
    {
        if (count($messages) !== 1) {
            throw new InvalidArgumentException(
                'The API only supports a single user message as prompt.'
            );
        }
        $message = $messages[0];
        if (!$message->getRole()->isUser()) {
            throw new InvalidArgumentException(
                'The API only supports a user message as prompt.'
            );
        }

        $text = null;
        foreach ($message->getParts() as $part) {
            $text = $part->getText();
            if ($text !== null) {
                break;
            }
        }

        if ($text === null) {
            throw new InvalidArgumentException(
                'The API only supports a single text message part as prompt.'
            );
        }

        return $text;
    }

    /**
     * Prepares the aspect ratio parameter for the API request.
     *
     * @since n.e.x.t
     *
     * @param MediaOrientationEnum|null $orientation The desired media orientation.
     * @param string|null $aspectRatio The desired media aspect ratio.
     * @return string The prepared aspect ratio parameter.
     */
    protected function prepareAspectRatioParam(?MediaOrientationEnum $orientation, ?string $aspectRatio): string
    {
        // Use aspect ratio if set, as it is more specific.
        if ($aspectRatio !== null) {
            return $aspectRatio;
        }

        // This should always have a value, as the method is only called if at least one or the other is set.
        if ($orientation !== null) {
            if ($orientation->isLandscape()) {
                return '16:9';
            }
            if ($orientation->isPortrait()) {
                return '9:16';
            }
        }
        return '1:1';
    }

    /**
     * Parses the response from the API endpoint to a generative AI result.
     *
     * @since n.e.x.t
     *
     * @param Response $response The response from the API endpoint.
     * @param string   $expectedMimeType The expected MIME type the response is in.
     * @return GenerativeAiResult The parsed generative AI result.
     */
    protected function parseResponseToGenerativeAiResult(
        Response $response,
        string $expectedMimeType = 'image/png'
    ): GenerativeAiResult {
        /** @var ResponseData $responseData */
        $responseData = $response->getData();
        if (!isset($responseData['predictions']) || !$responseData['predictions']) {
            throw ResponseException::fromMissingData($this->providerMetadata()->getName(), 'predictions');
        }
        if (!is_array($responseData['predictions'])) {
            throw ResponseException::fromInvalidData(
                $this->providerMetadata()->getName(),
                'predictions',
                'The value must be an array.'
            );
        }

        $candidates = [];
        foreach ($responseData['predictions'] as $index => $predictionData) {
            if (!is_array($predictionData) || array_is_list($predictionData)) {
                throw ResponseException::fromInvalidData(
                    $this->providerMetadata()->getName(),
                    "predictions[{$index}]",
                    'The value must be an associative array.'
                );
            }

            $candidates[] = $this->parseResponsePredictionToCandidate($predictionData, $index, $expectedMimeType);
        }

        $id = isset($responseData['id']) && is_string($responseData['id']) ? $responseData['id'] : '';

        // Use any other data from the response as provider-specific response metadata.
        $providerMetadata = $responseData;
        unset($providerMetadata['id'], $providerMetadata['predictions']);

        return new GenerativeAiResult(
            $id,
            $candidates,
            new TokenUsage(0, 0, 0),
            $this->providerMetadata(),
            $this->metadata(),
            $providerMetadata
        );
    }

    /**
     * Parses a single prediction from the API response into a Candidate object.
     *
     * @since n.e.x.t
     *
     * @param PredictionData $predictionData The prediction data from the API response.
     * @param int $index The index of the prediction in the predictions array.
     * @param string   $expectedMimeType The expected MIME type the response is in.
     * @return Candidate The parsed candidate.
     * @throws RuntimeException If the prediction data is invalid.
     */
    protected function parseResponsePredictionToCandidate(
        array $predictionData,
        int $index,
        string $expectedMimeType = 'image/png'
    ): Candidate {
        $mimeType = isset($predictionData['mimeType']) ? $predictionData['mimeType'] : $expectedMimeType;

        if (isset($predictionData['url']) && is_string($predictionData['url'])) {
            $imageFile = new File($predictionData['url'], $mimeType);
        } elseif (isset($predictionData['bytesBase64Encoded']) && is_string($predictionData['bytesBase64Encoded'])) {
            $imageFile = new File($predictionData['bytesBase64Encoded'], $mimeType);
        } else {
            throw ResponseException::fromInvalidData(
                $this->providerMetadata()->getName(),
                "predictions[{$index}]",
                'The value must contain either a url or bytesBase64Encoded key with a string value.'
            );
        }

        $parts = [new MessagePart($imageFile)];

        $message = new Message(MessageRoleEnum::model(), $parts);

        return new Candidate($message, FinishReasonEnum::stop());
    }
}

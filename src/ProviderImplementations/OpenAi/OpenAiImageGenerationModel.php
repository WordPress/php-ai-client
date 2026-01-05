<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
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
 * Class for an OpenAI image generation model using the Images API.
 *
 * This uses the Images API directly to generate images with GPT image models
 * (gpt-image-1, etc.) and DALL-E models (dall-e-2, dall-e-3).
 *
 * @since n.e.x.t
 *
 * @phpstan-type ImageData array{
 *     b64_json?: string,
 *     url?: string,
 *     revised_prompt?: string
 * }
 * @phpstan-type ResponseData array{
 *     created?: int,
 *     data?: list<ImageData>
 * }
 */
class OpenAiImageGenerationModel extends AbstractApiBasedModel implements ImageGenerationModelInterface
{
    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function generateImageResult(array $prompt): GenerativeAiResult
    {
        $httpTransporter = $this->getHttpTransporter();

        $params = $this->prepareGenerateImageParams($prompt);

        $request = new Request(
            HttpMethodEnum::POST(),
            OpenAiProvider::url('images/generations'),
            ['Content-Type' => 'application/json'],
            $params,
            $this->getRequestOptions()
        );

        // Add authentication credentials to the request.
        $request = $this->getRequestAuthentication()->authenticateRequest($request);

        // Send and process the request.
        $response = $httpTransporter->send($request);
        ResponseUtil::throwIfNotSuccessful($response);
        return $this->parseResponseToGenerativeAiResult($response);
    }

    /**
     * Prepares the given prompt and the model configuration into parameters for the API request.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $prompt The prompt to generate an image for. Should be a single user message.
     * @return array<string, mixed> The parameters for the API request.
     */
    protected function prepareGenerateImageParams(array $prompt): array
    {
        $config = $this->getConfig();
        $modelId = $this->metadata()->getId();

        $params = [
            'model' => $modelId,
            'prompt' => $this->preparePromptParam($prompt),
        ];

        // Add size configuration if available.
        $outputMediaOrientation = $config->getOutputMediaOrientation();
        $outputMediaAspectRatio = $config->getOutputMediaAspectRatio();
        if ($outputMediaOrientation !== null || $outputMediaAspectRatio !== null) {
            $params['size'] = $this->prepareSizeParam($modelId, $outputMediaOrientation, $outputMediaAspectRatio);
        }

        // Add model-specific parameters.
        if ($this->isGptImageModel($modelId)) {
            $this->addGptImageModelParams($params, $config);
        } else {
            $this->addDalleModelParams($params);
        }

        /*
         * Any custom options are added to the parameters as well.
         * This allows developers to pass other options that may be more niche or not yet supported by the SDK.
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
     * Adds GPT image model specific parameters to the request.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $params The parameters array to modify.
     * @param \WordPress\AiClient\Providers\Models\DTO\ModelConfig $config The model configuration.
     */
    protected function addGptImageModelParams(array &$params, $config): void
    {
        // Add output format configuration if available.
        $outputMimeType = $config->getOutputMimeType();
        if ($outputMimeType !== null) {
            $formatMap = [
                'image/png' => 'png',
                'image/jpeg' => 'jpeg',
                'image/webp' => 'webp',
            ];
            if (isset($formatMap[$outputMimeType])) {
                $params['output_format'] = $formatMap[$outputMimeType];
            }
        }
    }

    /**
     * Adds DALL-E model specific parameters to the request.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $params The parameters array to modify.
     */
    protected function addDalleModelParams(array &$params): void
    {
        // DALL-E models need response_format set to b64_json to get base64 data.
        $params['response_format'] = 'b64_json';
    }

    /**
     * Checks if the given model ID is a GPT image model.
     *
     * @since n.e.x.t
     *
     * @param string $modelId The model ID to check.
     * @return bool True if it's a GPT image model, false otherwise.
     */
    protected function isGptImageModel(string $modelId): bool
    {
        return str_starts_with($modelId, 'gpt-image-');
    }

    /**
     * Prepares the prompt parameter for the API request.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to prepare. Should be a single user message.
     * @return string The prepared prompt string.
     */
    protected function preparePromptParam(array $messages): string
    {
        if (count($messages) !== 1) {
            throw new InvalidArgumentException(
                'The API requires a single user message as prompt.'
            );
        }
        $message = $messages[0];
        if (!$message->getRole()->isUser()) {
            throw new InvalidArgumentException(
                'The API requires a user message as prompt.'
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
                'The API requires a text message part as prompt.'
            );
        }

        return $text;
    }

    /**
     * Prepares the size parameter for the image generation request.
     *
     * @since n.e.x.t
     *
     * @param string $modelId The model ID.
     * @param MediaOrientationEnum|null $orientation The desired media orientation.
     * @param string|null $aspectRatio The desired media aspect ratio.
     * @return string The size parameter value.
     */
    protected function prepareSizeParam(
        string $modelId,
        ?MediaOrientationEnum $orientation,
        ?string $aspectRatio
    ): string {
        if ($this->isGptImageModel($modelId)) {
            return $this->prepareGptImageSizeParam($orientation, $aspectRatio);
        }

        return $this->prepareDalleSizeParam($modelId, $orientation, $aspectRatio);
    }

    /**
     * Prepares the size parameter for GPT image models.
     *
     * @since n.e.x.t
     *
     * @param MediaOrientationEnum|null $orientation The desired media orientation.
     * @param string|null $aspectRatio The desired media aspect ratio.
     * @return string The size parameter value.
     */
    protected function prepareGptImageSizeParam(?MediaOrientationEnum $orientation, ?string $aspectRatio): string
    {
        // If aspect ratio is provided, map it to OpenAI size format.
        if ($aspectRatio !== null) {
            $aspectRatioMap = [
                '1:1' => '1024x1024',
                '3:2' => '1536x1024',
                '2:3' => '1024x1536',
            ];
            if (isset($aspectRatioMap[$aspectRatio])) {
                return $aspectRatioMap[$aspectRatio];
            }
        }

        // Map orientation to size.
        if ($orientation !== null) {
            if ($orientation->isLandscape()) {
                return '1536x1024';
            }
            if ($orientation->isPortrait()) {
                return '1024x1536';
            }
        }

        // Default to square.
        return '1024x1024';
    }

    /**
     * Prepares the size parameter for DALL-E models.
     *
     * @since n.e.x.t
     *
     * @param string $modelId The model ID (dall-e-2 or dall-e-3).
     * @param MediaOrientationEnum|null $orientation The desired media orientation.
     * @param string|null $aspectRatio The desired media aspect ratio.
     * @return string The size parameter value.
     */
    protected function prepareDalleSizeParam(
        string $modelId,
        ?MediaOrientationEnum $orientation,
        ?string $aspectRatio
    ): string {
        $isDalle3 = $modelId === 'dall-e-3';

        // If aspect ratio is provided, map it to size.
        if ($aspectRatio !== null) {
            if ($isDalle3) {
                $aspectRatioMap = [
                    '1:1' => '1024x1024',
                    '7:4' => '1792x1024',
                    '4:7' => '1024x1792',
                ];
            } else {
                // DALL-E 2 only supports square images at various resolutions.
                $aspectRatioMap = [
                    '1:1' => '1024x1024',
                ];
            }
            if (isset($aspectRatioMap[$aspectRatio])) {
                return $aspectRatioMap[$aspectRatio];
            }
        }

        // Map orientation to size.
        if ($orientation !== null) {
            if ($isDalle3) {
                if ($orientation->isLandscape()) {
                    return '1792x1024';
                }
                if ($orientation->isPortrait()) {
                    return '1024x1792';
                }
            }
            // DALL-E 2 only supports square, so orientation doesn't change the size.
        }

        // Default to square.
        return '1024x1024';
    }

    /**
     * Parses the response from the API endpoint to a generative AI result.
     *
     * @since n.e.x.t
     *
     * @param Response $response The response from the API endpoint.
     * @return GenerativeAiResult The parsed generative AI result.
     */
    protected function parseResponseToGenerativeAiResult(Response $response): GenerativeAiResult
    {
        /** @var ResponseData $responseData */
        $responseData = $response->getData();

        if (!isset($responseData['data']) || !$responseData['data']) {
            throw ResponseException::fromMissingData($this->providerMetadata()->getName(), 'data');
        }
        if (!is_array($responseData['data']) || !array_is_list($responseData['data'])) {
            throw ResponseException::fromInvalidData(
                $this->providerMetadata()->getName(),
                'data',
                'The value must be an indexed array.'
            );
        }

        $candidates = [];
        foreach ($responseData['data'] as $index => $imageData) {
            if (!is_array($imageData) || array_is_list($imageData)) {
                throw ResponseException::fromInvalidData(
                    $this->providerMetadata()->getName(),
                    "data[{$index}]",
                    'The value must be an associative array.'
                );
            }

            $candidates[] = $this->parseImageDataToCandidate($imageData, $index);
        }

        // The Images API doesn't return an ID, so we generate one from the created timestamp.
        $id = isset($responseData['created']) ? 'img-' . $responseData['created'] : '';

        // The Images API doesn't return token usage.
        $tokenUsage = new TokenUsage(0, 0, 0);

        // Use any other data from the response as provider-specific response metadata.
        $additionalData = $responseData;
        unset($additionalData['data'], $additionalData['created']);

        return new GenerativeAiResult(
            $id,
            $candidates,
            $tokenUsage,
            $this->providerMetadata(),
            $this->metadata(),
            $additionalData
        );
    }

    /**
     * Parses image data from the API response into a Candidate object.
     *
     * @since n.e.x.t
     *
     * @param ImageData $imageData The image data from the API response.
     * @param int $index The index of the image in the data array.
     * @return Candidate The parsed candidate.
     */
    protected function parseImageDataToCandidate(array $imageData, int $index): Candidate
    {
        if (!isset($imageData['b64_json']) || !is_string($imageData['b64_json'])) {
            throw ResponseException::fromMissingData(
                $this->providerMetadata()->getName(),
                "data[{$index}].b64_json"
            );
        }

        $base64Data = $imageData['b64_json'];

        // Determine MIME type from config or default to PNG.
        $config = $this->getConfig();
        $mimeType = $config->getOutputMimeType() ?? 'image/png';

        $imageFile = new File($base64Data, $mimeType);
        $parts = [new MessagePart($imageFile)];
        $message = new Message(MessageRoleEnum::model(), $parts);

        return new Candidate($message, FinishReasonEnum::stop());
    }
}

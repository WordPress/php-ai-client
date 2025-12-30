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
 * Class for an OpenAI image generation model using the Responses API.
 *
 * This uses the Responses API with the built-in image_generation tool.
 *
 * @since n.e.x.t
 *
 * @phpstan-type ImageGenerationCallData array{
 *     type: string,
 *     result?: string
 * }
 * @phpstan-type OutputItemData array{
 *     type: string,
 *     id?: string,
 *     role?: string,
 *     status?: string,
 *     content?: list<array<string, mixed>>
 * }
 * @phpstan-type UsageData array{
 *     input_tokens?: int,
 *     output_tokens?: int,
 *     total_tokens?: int
 * }
 * @phpstan-type ResponseData array{
 *     id?: string,
 *     status?: string,
 *     output?: list<OutputItemData>,
 *     usage?: UsageData
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
            OpenAiProvider::url('responses'),
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

        // The Responses API with image_generation tool requires a model that supports it.
        // We use a capable model like gpt-4o to process the request with the image_generation tool.
        $params = [
            'model' => $this->getHostModelForImageGeneration($modelId),
            'input' => $this->preparePromptParam($prompt),
            'tools' => [
                $this->prepareImageGenerationTool($modelId),
            ],
        ];

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
     * Gets the host model to use for image generation requests.
     *
     * The image_generation tool runs within a host model's context. For dedicated
     * image generation models like gpt-image-1, we use a capable host model.
     *
     * @since n.e.x.t
     *
     * @param string $modelId The requested model ID.
     * @return string The host model ID to use for the request.
     */
    protected function getHostModelForImageGeneration(string $modelId): string
    {
        // If this is a gpt-image-* model, we need a host model to run the tool.
        // Otherwise, the model itself can host the image_generation tool.
        if (str_starts_with($modelId, 'gpt-image-')) {
            return 'gpt-4o';
        }
        return $modelId;
    }

    /**
     * Prepares the image_generation tool configuration.
     *
     * @since n.e.x.t
     *
     * @param string $modelId The model ID for image generation.
     * @return array<string, mixed> The tool configuration.
     */
    protected function prepareImageGenerationTool(string $modelId): array
    {
        $config = $this->getConfig();
        $tool = ['type' => 'image_generation'];

        // If a specific image model is requested, include it in the tool config.
        if (str_starts_with($modelId, 'gpt-image-')) {
            $tool['model'] = $modelId;
        }

        // Add size configuration if available.
        $outputMediaOrientation = $config->getOutputMediaOrientation();
        $outputMediaAspectRatio = $config->getOutputMediaAspectRatio();
        if ($outputMediaOrientation !== null || $outputMediaAspectRatio !== null) {
            $tool['size'] = $this->prepareSizeParam($outputMediaOrientation, $outputMediaAspectRatio);
        }

        // Add output format configuration if available.
        $outputMimeType = $config->getOutputMimeType();
        if ($outputMimeType !== null) {
            // Map MIME type to OpenAI format.
            $formatMap = [
                'image/png' => 'png',
                'image/jpeg' => 'jpeg',
                'image/webp' => 'webp',
            ];
            if (isset($formatMap[$outputMimeType])) {
                $tool['output_format'] = $formatMap[$outputMimeType];
            }
        }

        return $tool;
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
     * Prepares the size parameter for the image generation tool.
     *
     * @since n.e.x.t
     *
     * @param MediaOrientationEnum|null $orientation The desired media orientation.
     * @param string|null $aspectRatio The desired media aspect ratio.
     * @return string The size parameter value.
     */
    protected function prepareSizeParam(?MediaOrientationEnum $orientation, ?string $aspectRatio): string
    {
        // If aspect ratio is provided, map it to OpenAI size format.
        if ($aspectRatio !== null) {
            $aspectRatioMap = [
                '1:1' => '1024x1024',
                '16:9' => '1792x1024',
                '9:16' => '1024x1792',
                '4:3' => '1024x768',
                '3:4' => '768x1024',
            ];
            if (isset($aspectRatioMap[$aspectRatio])) {
                return $aspectRatioMap[$aspectRatio];
            }
        }

        // Map orientation to size.
        if ($orientation !== null) {
            if ($orientation->isLandscape()) {
                return '1792x1024';
            }
            if ($orientation->isPortrait()) {
                return '1024x1792';
            }
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

        if (!isset($responseData['output']) || !$responseData['output']) {
            throw ResponseException::fromMissingData($this->providerMetadata()->getName(), 'output');
        }
        if (!is_array($responseData['output']) || !array_is_list($responseData['output'])) {
            throw ResponseException::fromInvalidData(
                $this->providerMetadata()->getName(),
                'output',
                'The value must be an indexed array.'
            );
        }

        $candidates = [];
        foreach ($responseData['output'] as $index => $outputItem) {
            if (!is_array($outputItem) || array_is_list($outputItem)) {
                throw ResponseException::fromInvalidData(
                    $this->providerMetadata()->getName(),
                    "output[{$index}]",
                    'The value must be an associative array.'
                );
            }

            $candidate = $this->parseOutputItemToCandidate($outputItem, $index);
            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        $id = isset($responseData['id']) && is_string($responseData['id']) ? $responseData['id'] : '';

        if (isset($responseData['usage']) && is_array($responseData['usage'])) {
            $usage = $responseData['usage'];
            $tokenUsage = new TokenUsage(
                $usage['input_tokens'] ?? 0,
                $usage['output_tokens'] ?? 0,
                $usage['total_tokens'] ?? (($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0))
            );
        } else {
            $tokenUsage = new TokenUsage(0, 0, 0);
        }

        // Use any other data from the response as provider-specific response metadata.
        $additionalData = $responseData;
        unset($additionalData['id'], $additionalData['output'], $additionalData['usage']);

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
     * Parses a single output item from the API response into a Candidate object.
     *
     * @since n.e.x.t
     *
     * @param OutputItemData $outputItem The output item data from the API response.
     * @param int $index The index of the output item in the output array.
     * @return Candidate|null The parsed candidate, or null if the output item should be skipped.
     */
    protected function parseOutputItemToCandidate(array $outputItem, int $index): ?Candidate
    {
        $type = $outputItem['type'] ?? '';

        // Handle image_generation_call output type.
        if ($type === 'image_generation_call') {
            return $this->parseImageGenerationCallToCandidate($outputItem, $index);
        }

        // Skip other output types.
        return null;
    }

    /**
     * Parses an image_generation_call output item into a Candidate object.
     *
     * @since n.e.x.t
     *
     * @param ImageGenerationCallData $outputItem The output item data.
     * @param int $index The index of the output item.
     * @return Candidate The parsed candidate.
     */
    protected function parseImageGenerationCallToCandidate(array $outputItem, int $index): Candidate
    {
        if (!isset($outputItem['result']) || !is_string($outputItem['result'])) {
            throw ResponseException::fromMissingData(
                $this->providerMetadata()->getName(),
                "output[{$index}].result"
            );
        }

        // The result is base64-encoded image data.
        $base64Data = $outputItem['result'];

        // Determine MIME type from config or default to PNG.
        $config = $this->getConfig();
        $mimeType = $config->getOutputMimeType() ?? 'image/png';

        $imageFile = new File($base64Data, $mimeType);
        $parts = [new MessagePart($imageFile)];
        $message = new Message(MessageRoleEnum::model(), $parts);

        return new Candidate($message, FinishReasonEnum::stop());
    }
}

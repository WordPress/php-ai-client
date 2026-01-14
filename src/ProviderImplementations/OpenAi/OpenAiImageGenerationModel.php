<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\OpenAi;

use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleImageGenerationModel;

/**
 * Class for an OpenAI image generation model using the Images API.
 *
 * This uses the Images API directly to generate images with GPT image models
 * (gpt-image-1, etc.) and DALL-E models (dall-e-2, dall-e-3).
 *
 * @since 0.1.0
 */
class OpenAiImageGenerationModel extends AbstractOpenAiCompatibleImageGenerationModel
{
    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request {
        return new Request(
            $method,
            OpenAiProvider::url($path),
            $headers,
            $data,
            $this->getRequestOptions()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected function prepareGenerateImageParams(array $prompt): array
    {
        $params = parent::prepareGenerateImageParams($prompt);
        $modelId = $this->metadata()->getId();

        // GPT image models use output_format, DALL-E uses response_format.
        if ($this->isGptImageModel($modelId)) {
            // For GPT image models, convert response_format to the appropriate format.
            // The parent sets response_format, but GPT models don't need it.
            unset($params['response_format']);
        }

        return $params;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected function prepareSizeParam(?MediaOrientationEnum $orientation, ?string $aspectRatio): string
    {
        $modelId = $this->metadata()->getId();

        if ($this->isGptImageModel($modelId)) {
            return $this->prepareGptImageSizeParam($orientation, $aspectRatio);
        }

        return $this->prepareDalleSizeParam($modelId, $orientation, $aspectRatio);
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected function getResultId(array $responseData): string
    {
        // The Images API returns `created` timestamp instead of `id`.
        return isset($responseData['created']) && is_int($responseData['created'])
            ? 'img-' . $responseData['created']
            : '';
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
}

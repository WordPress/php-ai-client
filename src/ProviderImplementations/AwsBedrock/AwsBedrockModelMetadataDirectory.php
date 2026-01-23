<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\AwsBedrock;

use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\Util\ResponseUtil;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

/**
 * Class for the AWS Bedrock model metadata directory.
 *
 * @since n.e.x.t
 *
 * @phpstan-type ModelSummary array{
 *     modelArn?: string,
 *     modelId: string,
 *     modelName?: string,
 *     providerName?: string,
 *     inputModalities?: list<string>,
 *     outputModalities?: list<string>,
 *     responseStreamingSupported?: bool,
 *     customizationsSupported?: list<string>,
 *     inferenceTypesSupported?: list<string>,
 *     modelLifecycle?: array{status?: string}
 * }
 *
 * @phpstan-type ModelsResponseData array{
 *     modelSummaries?: list<ModelSummary>
 * }
 */
class AwsBedrockModelMetadataDirectory extends AbstractApiBasedModelMetadataDirectory
{
    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getRequestAuthentication(): RequestAuthenticationInterface
    {
        /*
         * Since we're calling the AWS Bedrock API here, we need to use the Bedrock specific
         * API key authentication class.
         */
        $requestAuthentication = parent::getRequestAuthentication();
        if (!$requestAuthentication instanceof ApiKeyRequestAuthentication) {
            return $requestAuthentication;
        }
        return new AwsBedrockApiKeyRequestAuthentication($requestAuthentication->getApiKey());
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return array<string, ModelMetadata> Map of model ID to model metadata.
     */
    protected function sendListModelsRequest(): array
    {
        $request = new Request(
            HttpMethodEnum::GET(),
            AwsBedrockProvider::controlPlaneUrl('foundation-models'),
            ['Content-Type' => 'application/json']
        );

        $request = $this->getRequestAuthentication()->authenticateRequest($request);
        $response = $this->getHttpTransporter()->send($request);

        ResponseUtil::throwIfNotSuccessful($response);

        return $this->parseResponseToModelMetadataList($response);
    }

    /**
     * Parses the API response to a list of model metadata.
     *
     * @since n.e.x.t
     *
     * @param Response $response The HTTP response from the list models API.
     * @return array<string, ModelMetadata> Map of model ID to model metadata.
     * @throws ResponseException If the response data is invalid or missing required fields.
     */
    protected function parseResponseToModelMetadataList(Response $response): array
    {
        /** @var ModelsResponseData $responseData */
        $responseData = $response->getData();

        if (!isset($responseData['modelSummaries']) || !is_array($responseData['modelSummaries'])) {
            throw ResponseException::fromMissingData('AWS Bedrock', 'modelSummaries');
        }

        $models = [];

        foreach ($responseData['modelSummaries'] as $modelData) {
            if (!isset($modelData['modelId'])) {
                continue;
            }

            $modelId = $modelData['modelId'];
            $modelName = $modelData['modelName'] ?? $modelId;

            // Infer capabilities from model data
            $capabilities = $this->inferCapabilities($modelData);

            // Define supported options based on model capabilities
            $options = $this->getSupportedOptions($modelData);

            $models[$modelId] = new ModelMetadata(
                $modelId,
                $modelName,
                $capabilities,
                $options
            );
        }

        return $models;
    }

    /**
     * Infers model capabilities from the model data.
     *
     * @since n.e.x.t
     *
     * @param ModelSummary $modelData The model data from the API.
     * @return list<CapabilityEnum> The inferred capabilities.
     */
    protected function inferCapabilities(array $modelData): array
    {
        $capabilities = [];

        // Check if model supports ON_DEMAND inference (text generation)
        $inferenceTypes = $modelData['inferenceTypesSupported'] ?? [];
        if (in_array('ON_DEMAND', $inferenceTypes, true)) {
            $capabilities[] = CapabilityEnum::textGeneration();
            $capabilities[] = CapabilityEnum::chatHistory();
        }

        // Check for image generation models (e.g., Stability AI)
        $outputModalities = $modelData['outputModalities'] ?? [];
        if (in_array('IMAGE', $outputModalities, true)) {
            $capabilities[] = CapabilityEnum::imageGeneration();
        }

        // Default to text generation if no capabilities detected
        if (empty($capabilities)) {
            $capabilities[] = CapabilityEnum::textGeneration();
        }

        return $capabilities;
    }

    /**
     * Gets the supported options for a model based on its capabilities.
     *
     * @since n.e.x.t
     *
     * @param ModelSummary $modelData The model data from the API.
     * @return list<SupportedOption> The supported options.
     */
    protected function getSupportedOptions(array $modelData): array
    {
        // Base options supported by most Bedrock models via Converse API
        $options = [
            new SupportedOption(OptionEnum::systemInstruction()),
            new SupportedOption(OptionEnum::maxTokens()),
            new SupportedOption(OptionEnum::temperature()),
            new SupportedOption(OptionEnum::topP()),
            new SupportedOption(OptionEnum::stopSequences()),
            new SupportedOption(OptionEnum::customOptions()),
        ];

        // Check if model supports multimodal input
        $inputModalities = $modelData['inputModalities'] ?? [];
        if (in_array('TEXT', $inputModalities, true) && in_array('IMAGE', $inputModalities, true)) {
            $options[] = new SupportedOption(
                OptionEnum::inputModalities(),
                [
                    [ModalityEnum::text()],
                    [ModalityEnum::text(), ModalityEnum::image()],
                ]
            );
        } elseif (in_array('TEXT', $inputModalities, true)) {
            $options[] = new SupportedOption(
                OptionEnum::inputModalities(),
                [[ModalityEnum::text()]]
            );
        }

        // Check output modalities
        $outputModalities = $modelData['outputModalities'] ?? [];
        if (in_array('TEXT', $outputModalities, true)) {
            $options[] = new SupportedOption(
                OptionEnum::outputModalities(),
                [[ModalityEnum::text()]]
            );
        }

        // Tool support for models that support it (most Converse API models)
        if (in_array('ON_DEMAND', $modelData['inferenceTypesSupported'] ?? [], true)) {
            $options[] = new SupportedOption(OptionEnum::functionDeclarations());
        }

        return $options;
    }
}

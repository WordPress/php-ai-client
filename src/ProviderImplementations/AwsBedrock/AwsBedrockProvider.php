<?php

declare(strict_types=1);

namespace WordPress\AiClient\ProviderImplementations\AwsBedrock;

use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Class for the AWS Bedrock provider.
 *
 * @since n.e.x.t
 */
class AwsBedrockProvider extends AbstractApiProvider
{
    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected static function baseUrl(): string
    {
        return 'https://bedrock-runtime.us-east-1.amazonaws.com';
    }

    /**
     * Constructs a URL for the given path and optional region.
     *
     * @since n.e.x.t
     *
     * @param string      $path   The path to append to the base URL. Default empty string.
     * @param string|null $region The AWS region to use, or null for default (us-east-1).
     * @return string The constructed URL.
     */
    public static function url(string $path = '', ?string $region = null): string
    {
        $region = $region ?? 'us-east-1';
        $baseUrl = "https://bedrock-runtime.{$region}.amazonaws.com";

        if ($path === '') {
            return $baseUrl;
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        $capabilities = $modelMetadata->getSupportedCapabilities();
        foreach ($capabilities as $capability) {
            if ($capability->isTextGeneration()) {
                return new AwsBedrockTextGenerationModel($modelMetadata, $providerMetadata);
            }
        }

        throw new RuntimeException(
            'Unsupported model capabilities: ' . implode(', ', $capabilities)
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected static function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'aws-bedrock',
            'AWS Bedrock',
            ProviderTypeEnum::cloud(),
            'https://console.aws.amazon.com/bedrock/',
            RequestAuthenticationMethod::apiKey()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        // Check valid API access by attempting to list models.
        return new ListModelsApiBasedProviderAvailability(
            static::modelMetadataDirectory()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new AwsBedrockModelMetadataDirectory();
    }
}

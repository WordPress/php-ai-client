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
     * The default AWS region to use.
     *
     * @var string Default AWS region.
     */
    public const DEFAULT_REGION = 'us-east-1';

    /**
     * The environment variable name for the AWS region.
     *
     * @var string The environment variable name.
     */
    public const ENV_REGION = 'AWS_BEDROCK_REGION';

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    protected static function baseUrl(): string
    {
        return static::controlPlaneUrl();
    }

    /**
     * Constructs a control plane URL for the given path and optional region.
     *
     * @since n.e.x.t
     *
     * @param string      $path   The path to append to the base URL. Default empty string.
     * @param string|null $region The AWS region to use, or null for default.
     * @return string The constructed URL.
     */
    public static function controlPlaneUrl(string $path = '', ?string $region = null): string
    {
        $region = static::resolveRegion($region);
        $baseUrl = "https://bedrock.{$region}.amazonaws.com";

        if ($path === '') {
            return $baseUrl;
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Constructs a runtime URL for the given path and optional region.
     *
     * @since n.e.x.t
     *
     * @param string      $path   The path to append to the base URL. Default empty string.
     * @param string|null $region The AWS region to use, or null for default.
     * @return string The constructed URL.
     */
    public static function runtimeUrl(string $path = '', ?string $region = null): string
    {
        $region = static::resolveRegion($region);
        $baseUrl = "https://bedrock-runtime.{$region}.amazonaws.com";

        if ($path === '') {
            return $baseUrl;
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Constructs a control plane URL for the given path and optional region.
     *
     * @since n.e.x.t
     *
     * @param string      $path   The path to append to the base URL. Default empty string.
     * @param string|null $region The AWS region to use, or null for default.
     * @return string The constructed URL.
     */
    public static function url(string $path = '', ?string $region = null): string
    {
        return static::controlPlaneUrl($path, $region);
    }

    /**
     * Resolves the AWS region from an explicit value, environment variable, or default.
     *
     * @since n.e.x.t
     *
     * @param string|null $region The explicit region to use, if provided.
     * @return string The resolved region.
     */
    public static function resolveRegion(?string $region = null): string
    {
        if (is_string($region) && $region !== '') {
            return $region;
        }

        $envRegion = getenv(self::ENV_REGION);
        if ($envRegion === false && defined(self::ENV_REGION)) {
            $envRegion = constant(self::ENV_REGION);
        }

        if (is_string($envRegion) && $envRegion !== '') {
            return $envRegion;
        }

        return self::DEFAULT_REGION;
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

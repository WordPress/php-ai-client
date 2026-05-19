<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\Contracts\ProviderWithRequestAuthenticationInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;

/**
 * Mock provider with custom request authentication for testing purposes.
 */
class MockCustomAuthProvider extends MockProvider implements ProviderWithRequestAuthenticationInterface
{
    /**
     * @var RequestAuthenticationInterface|null Custom request authentication instance.
     */
    private static ?RequestAuthenticationInterface $requestAuthentication = null;

    /**
     * {@inheritDoc}
     */
    public static function metadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'mock-custom-auth',
            'Mock Custom Auth Provider',
            ProviderTypeEnum::cloud()
        );
    }

    /**
     * {@inheritDoc}
     */
    public static function requestAuthentication(): ?RequestAuthenticationInterface
    {
        return static::$requestAuthentication;
    }

    /**
     * {@inheritDoc}
     */
    public static function model(string $modelId, ?ModelConfig $modelConfig = null): ModelInterface
    {
        $modelMetadata = static::modelMetadataDirectory()->getModelMetadata($modelId);
        $config = $modelConfig ?? new ModelConfig();

        return new MockCustomAuthModel($modelMetadata, $config);
    }

    /**
     * Sets the request authentication for testing.
     *
     * @param RequestAuthenticationInterface|null $requestAuthentication The request authentication instance.
     */
    public static function setRequestAuthentication(?RequestAuthenticationInterface $requestAuthentication): void
    {
        static::$requestAuthentication = $requestAuthentication;
    }

    /**
     * Resets static state for testing.
     */
    public static function reset(): void
    {
        parent::reset();
        static::$requestAuthentication = null;
    }
}

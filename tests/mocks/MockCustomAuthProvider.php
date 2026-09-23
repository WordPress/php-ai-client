<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;

/**
 * Mock provider with bearer token authentication for testing purposes.
 */
class MockCustomAuthProvider extends MockProvider
{
    /**
     * {@inheritDoc}
     */
    public static function metadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'mock-custom-auth',
            'Mock Custom Auth Provider',
            ProviderTypeEnum::cloud(),
            null,
            RequestAuthenticationMethod::bearerToken()
        );
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
     * Resets static state for testing.
     */
    public static function reset(): void
    {
        parent::reset();
    }
}

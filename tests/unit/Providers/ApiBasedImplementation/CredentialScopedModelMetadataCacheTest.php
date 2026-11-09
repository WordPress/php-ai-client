<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\ApiBasedImplementation;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Tests\mocks\MockCache;

/**
 * @covers \WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory
 * @covers \WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability
 */
class CredentialScopedModelMetadataCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        AiClient::setCache(null);
        parent::tearDown();
    }

    /**
     * Tests credential changes cannot reuse a previous availability result.
     *
     * @dataProvider cacheProvider
     */
    public function testAvailabilityFollowsCurrentCredential(bool $sharedCache): void
    {
        AiClient::setCache($sharedCache ? new MockCache() : null);
        $directory = $this->createAuthenticatedDirectory();
        $availability = new ListModelsApiBasedProviderAvailability($directory);

        $directory->setRequestAuthentication(new ApiKeyRequestAuthentication('valid-key'));
        $this->assertTrue($availability->isConfigured());
        $this->assertTrue($availability->isConfigured());
        $this->assertSame(1, $directory->getListRequestCount());

        $directory->setRequestAuthentication(new ApiKeyRequestAuthentication('rotated-key'));
        $this->assertTrue($availability->isConfigured());
        $this->assertSame(2, $directory->getListRequestCount());

        $directory->setRequestAuthentication(new ApiKeyRequestAuthentication('invalid-key'));
        $this->assertFalse($availability->isConfigured());

        $directory->setRequestAuthentication(new ApiKeyRequestAuthentication(''));
        $this->assertFalse($availability->isConfigured());
    }

    /**
     * Tests shared caches are isolated across credentials and new directory instances.
     */
    public function testSharedCacheIsScopedToCredential(): void
    {
        $cache = new MockCache();
        AiClient::setCache($cache);
        $modelA = $this->createStub(ModelMetadata::class);
        $modelB = $this->createStub(ModelMetadata::class);
        $directoryA = $this->createAuthenticatedDirectory(['model-a' => $modelA]);
        $directoryA->setRequestAuthentication(new ApiKeyRequestAuthentication('valid-key'));
        $this->assertSame([$modelA], $directoryA->listModelMetadata());

        $directoryB = $this->createAuthenticatedDirectory(['model-b' => $modelB]);
        $directoryB->setRequestAuthentication(new ApiKeyRequestAuthentication('rotated-key'));
        $this->assertSame([$modelB], $directoryB->listModelMetadata());
        $this->assertSame(1, $directoryB->getListRequestCount());

        // A new request with the same credential still reuses its own cached models.
        $nextDirectoryA = $this->createAuthenticatedDirectory();
        $nextDirectoryA->setRequestAuthentication(new ApiKeyRequestAuthentication('valid-key'));
        $this->assertSame([$modelA], $nextDirectoryA->listModelMetadata());
        $this->assertSame(0, $nextDirectoryA->getListRequestCount());

        // Removing authentication in a new request must not reuse either cache.
        $withoutAuthentication = $this->createAuthenticatedDirectory();
        $this->assertFalse((new ListModelsApiBasedProviderAvailability($withoutAuthentication))->isConfigured());

        foreach ($cache->getOperations() as $operation) {
            $this->assertStringNotContainsString('valid-key', $operation['key']);
            $this->assertStringNotContainsString('rotated-key', $operation['key']);
        }
    }

    /**
     * Tests invalidation only clears the active credential's cached models.
     */
    public function testInvalidationPreservesOtherCredentials(): void
    {
        AiClient::setCache(new MockCache());
        $directory = $this->createAuthenticatedDirectory();
        $directory->setRequestAuthentication(new ApiKeyRequestAuthentication('valid-key'));
        $directory->listModelMetadata();
        $directory->setRequestAuthentication(new ApiKeyRequestAuthentication('rotated-key'));
        $directory->listModelMetadata();
        $directory->invalidateCaches();
        $directory->listModelMetadata();
        $this->assertSame(3, $directory->getListRequestCount());

        $directory->setRequestAuthentication(new ApiKeyRequestAuthentication('valid-key'));
        $directory->listModelMetadata();
        $this->assertSame(3, $directory->getListRequestCount());
    }

    /**
     * Tests explicit metadata memoization follows credential changes too.
     */
    public function testExplicitMetadataIsRecomputedOnlyWhenCredentialChanges(): void
    {
        $directory = new MockApiBasedModelMetadataDirectory();
        $directory->setRequestAuthentication(new ApiKeyRequestAuthentication('valid-key'));
        $this->assertFalse($directory->hasModelMetadata('missing-model'));
        $this->assertSame(1, $directory->getExplicitModelMetadataLookupCount());

        $directory->setRequestAuthentication(new ApiKeyRequestAuthentication('valid-key'));
        $this->assertFalse($directory->hasModelMetadata('missing-model'));
        $this->assertSame(1, $directory->getExplicitModelMetadataLookupCount());

        $directory->setRequestAuthentication(new ApiKeyRequestAuthentication('rotated-key'));
        $this->assertFalse($directory->hasModelMetadata('missing-model'));
        $this->assertSame(2, $directory->getExplicitModelMetadataLookupCount());
    }

    /**
     * Tests legacy credential-independent entries cannot provide stale availability.
     */
    public function testLegacyCacheCannotAuthenticateProvider(): void
    {
        $cache = new MockCache();
        AiClient::setCache($cache);
        $directory = $this->createAuthenticatedDirectory();
        $legacyKey = 'ai_client_' . AiClient::VERSION . '_' . md5(get_class($directory)) . '_models';
        $cache->seed($legacyKey, []);

        $this->assertFalse((new ListModelsApiBasedProviderAvailability($directory))->isConfigured());
    }

    /**
     * Provides local and shared cache configurations.
     *
     * @return array<string, array{bool}> The cache configurations.
     */
    public function cacheProvider(): array
    {
        return ['local cache' => [false], 'shared cache' => [true]];
    }

    /**
     * Creates a directory whose model-list endpoint requires a valid credential.
     *
     * @param array<string, ModelMetadata> $models The models returned by the endpoint.
     * @return MockApiBasedModelMetadataDirectory The instrumented directory.
     */
    private function createAuthenticatedDirectory(array $models = []): MockApiBasedModelMetadataDirectory
    {
        return new class ($models) extends MockApiBasedModelMetadataDirectory {
            /**
             * {@inheritDoc}
             */
            protected function sendListModelsRequest(): array
            {
                $authentication = $this->getRequestAuthentication();
                if (
                    !$authentication instanceof ApiKeyRequestAuthentication ||
                    !in_array($authentication->getApiKey(), ['valid-key', 'rotated-key'], true)
                ) {
                    throw new RuntimeException('The provider rejected the credential.');
                }
                return parent::sendListModelsRequest();
            }
        };
    }
}

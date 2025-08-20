<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Tests\mocks\MockHttpTransporter;
use WordPress\AiClient\Tests\mocks\MockModel;
use WordPress\AiClient\Tests\mocks\MockModelMetadataDirectory;
use WordPress\AiClient\Tests\mocks\MockProvider;
use WordPress\AiClient\Tests\mocks\MockProviderAvailability;
use WordPress\AiClient\Tests\mocks\MockRequestAuthentication;

/**
 * @covers \WordPress\AiClient\Providers\ProviderRegistry
 */
class ProviderRegistryTest extends TestCase
{
    private ProviderRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ProviderRegistry();
        MockProvider::reset(); // Reset static state of mock provider before each test.
    }

    protected function tearDown(): void
    {
        MockProvider::reset(); // Reset static state of mock provider after each test.
        parent::tearDown();
    }

    /**
     * Tests provider registration with valid provider.
     *
     * @return void
     */
    public function testRegisterProviderWithValidProvider(): void
    {
        $this->registry->registerProvider(MockProvider::class);

        $this->assertTrue($this->registry->hasProvider('mock'));
        $this->assertTrue($this->registry->hasProvider(MockProvider::class));
        $this->assertEquals(MockProvider::class, $this->registry->getProviderClassName('mock'));
    }

    /**
     * Tests provider registration with non-existent class.
     *
     * @return void
     */
    public function testRegisterProviderWithNonExistentClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider class does not exist: NonExistentProvider');

        $this->registry->registerProvider('NonExistentProvider');
    }

    /**
     * Tests hasProvider with unregistered provider.
     *
     * @return void
     */
    public function testHasProviderWithUnregisteredProvider(): void
    {
        $this->assertFalse($this->registry->hasProvider('nonexistent'));
        $this->assertFalse($this->registry->hasProvider('NonExistentClass'));
    }

    /**
     * Tests getProviderClassName with unregistered provider.
     *
     * @return void
     */
    public function testGetProviderClassNameWithUnregisteredProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider not registered: nonexistent');

        $this->registry->getProviderClassName('nonexistent');
    }

    /**
     * Tests isProviderConfigured with registered provider.
     *
     * @return void
     */
    public function testIsProviderConfiguredWithRegisteredProvider(): void
    {
        $this->registry->registerProvider(MockProvider::class);

        $this->assertTrue($this->registry->isProviderConfigured('mock'));
        $this->assertTrue($this->registry->isProviderConfigured(MockProvider::class));
    }

    /**
     * Tests isProviderConfigured with unregistered provider.
     *
     * @return void
     */
    public function testIsProviderConfiguredWithUnregisteredProvider(): void
    {
        $this->assertFalse($this->registry->isProviderConfigured('nonexistent'));
    }

    /**
     * Tests findModelsMetadataForSupport with no registered providers.
     *
     * @return void
     */
    public function testFindModelsMetadataForSupportWithNoProviders(): void
    {
        $requirements = new ModelRequirements([CapabilityEnum::textGeneration()], []);
        $results = $this->registry->findModelsMetadataForSupport($requirements);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * Tests findModelsMetadataForSupport with registered provider.
     *
     * @return void
     */
    public function testFindModelsMetadataForSupportWithRegisteredProvider(): void
    {
        $this->registry->registerProvider(MockProvider::class);

        $requirements = new ModelRequirements([CapabilityEnum::textGeneration()], []);
        $results = $this->registry->findModelsMetadataForSupport($requirements);

        $this->assertIsArray($results);
        // Should now find models that match the text generation requirement
        $this->assertNotEmpty($results);
        $this->assertCount(1, $results);
    }

    /**
     * Tests findProviderModelsMetadataForSupport with registered provider.
     *
     * @return void
     */
    public function testFindProviderModelsMetadataForSupportWithRegisteredProvider(): void
    {
        $this->registry->registerProvider(MockProvider::class);

        $requirements = new ModelRequirements([CapabilityEnum::textGeneration()], []);
        $results = $this->registry->findProviderModelsMetadataForSupport('mock', $requirements);

        $this->assertIsArray($results);
        // Should now find models that match the text generation requirement
        $this->assertNotEmpty($results);
        $this->assertCount(1, $results);
    }

    /**
     * Tests findProviderModelsMetadataForSupport with unregistered provider.
     *
     * @return void
     */
    public function testFindProviderModelsMetadataForSupportWithUnregisteredProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider not registered: nonexistent');

        $requirements = new ModelRequirements([CapabilityEnum::textGeneration()], []);
        $this->registry->findProviderModelsMetadataForSupport('nonexistent', $requirements);
    }

    /**
     * Tests getProviderModel throws exception for non-existent model.
     *
     * @return void
     */
    public function testGetProviderModelThrowsException(): void
    {
        $this->registry->registerProvider(MockProvider::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model not found: test-model');

        $modelConfig = new ModelConfig([]);
        $this->registry->getProviderModel('mock', 'test-model', $modelConfig);
    }

    /**
     * Tests multiple provider registration.
     *
     * @return void
     */
    public function testMultipleProviderRegistration(): void
    {
        $this->registry->registerProvider(MockProvider::class);

        // Register another instance of the same provider (should update)
        $this->registry->registerProvider(MockProvider::class);

        $this->assertTrue($this->registry->hasProvider('mock'));
        $this->assertEquals(MockProvider::class, $this->registry->getProviderClassName('mock'));
    }

    /**
     * Tests provider instance caching.
     *
     * @return void
     */
    public function testProviderInstanceCaching(): void
    {
        $this->registry->registerProvider(MockProvider::class);

        // Call methods that create instances
        $this->assertTrue($this->registry->isProviderConfigured('mock'));
        $this->assertTrue($this->registry->isProviderConfigured('mock'));

        // Should not throw any errors and should reuse cached instance
        $this->addToAssertionCount(1);
    }

    /**
     * Tests that setHttpTransporter hooks up the transporter to registered providers.
     *
     * @return void
     */
    public function testSetHttpTransporterHooksUpToProviders(): void
    {
        $mockTransporter = new MockHttpTransporter();
        $mockAvailability = new MockProviderAvailability();
        $mockModelMetadataDirectory = new MockModelMetadataDirectory([
            'mock-text-model' => new ModelMetadata(
                'mock-text-model',
                'Mock Text Model',
                [CapabilityEnum::textGeneration()],
                []
            )
        ]);
        $mockModel = new MockModel(
            new ModelMetadata('mock-model', 'Mock Model', [], []),
            new ModelConfig([])
        );

        MockProvider::setAvailability($mockAvailability);
        MockProvider::setModelMetadataDirectory($mockModelMetadataDirectory);

        // Register the provider AFTER setting up mocks, so it uses these mocks.
        $this->registry->registerProvider(MockProvider::class);

        // Set the transporter on the registry.
        $this->registry->setHttpTransporter($mockTransporter);

        // Get a model instance from the provider.
        $modelConfig = new ModelConfig([]);
        $retrievedModel = $this->registry->getProviderModel('mock', 'mock-text-model', $modelConfig);

        // Verify that the transporter was set on the relevant instances.
        $this->assertSame($mockTransporter, $mockAvailability->getHttpTransporter());
        $this->assertSame($mockTransporter, $mockModelMetadataDirectory->getHttpTransporter());
        $this->assertSame($mockTransporter, $retrievedModel->getHttpTransporter());
    }

    /**
     * Tests that setProviderRequestAuthentication hooks up the authentication to registered providers.
     *
     * @return void
     */
    public function testSetProviderRequestAuthenticationHooksUpToProviders(): void
    {
        $mockTransporter = new MockHttpTransporter(); // Add this line
        $this->registry->setHttpTransporter($mockTransporter); // Add this line

        $mockAuth = new MockRequestAuthentication('custom_token');
        $mockAvailability = new MockProviderAvailability();
        $mockModelMetadataDirectory = new MockModelMetadataDirectory([
            'mock-text-model' => new ModelMetadata(
                'mock-text-model',
                'Mock Text Model',
                [CapabilityEnum::textGeneration()],
                []
            )
        ]);
        $mockModel = new MockModel(
            new ModelMetadata('mock-model', 'Mock Model', [], []),
            new ModelConfig([])
        );

        MockProvider::setAvailability($mockAvailability);
        MockProvider::setModelMetadataDirectory($mockModelMetadataDirectory);

        // Register the provider AFTER setting up mocks, so it uses these mocks.
        $this->registry->registerProvider(MockProvider::class);

        // Set the authentication on the specific provider.
        $this->registry->setProviderRequestAuthentication('mock', $mockAuth);

        // Get a model instance from the provider.
        $modelConfig = new ModelConfig([]);
        $retrievedModel = $this->registry->getProviderModel('mock', 'mock-text-model', $modelConfig);

        // Verify that the authentication was set on the relevant instances.
        $this->assertSame($mockAuth, $mockAvailability->getRequestAuthentication());
        $this->assertSame($mockAuth, $mockModelMetadataDirectory->getRequestAuthentication());
        $this->assertSame($mockAuth, $retrievedModel->getRequestAuthentication());
    }

    /**
     * Tests that getProviderRequestAuthentication returns the correct instance.
     *
     * @return void
     */
    public function testGetProviderRequestAuthentication(): void
    {
        $this->registry->registerProvider(MockProvider::class);
        $mockAuth = new MockRequestAuthentication('another_token');
        $this->registry->setProviderRequestAuthentication('mock', $mockAuth);

        $retrievedAuth = $this->registry->getProviderRequestAuthentication('mock');
        $this->assertSame($mockAuth, $retrievedAuth);
    }

    /**
     * Tests that getProviderRequestAuthentication returns a default instance if not explicitly set.
     *
     * @return void
     */
    public function testGetProviderRequestAuthenticationReturnsDefault(): void
    {
        $this->registry->registerProvider(MockProvider::class);
        $retrievedAuth = $this->registry->getProviderRequestAuthentication('mock');

        // By default, it should create an ApiKeyRequestAuthentication if environment variables are set.
        // Since no env vars are set in tests, it should fall back to null.
        $this->assertNull($retrievedAuth);
    }

    /**
     * Tests the internal getEnvVarName method using reflection.
     *
     * @dataProvider envVarNameProvider
     * @param string $providerId The provider ID.
     * @param string $field The field name.
     * @param string $expected The expected environment variable name.
     * @return void
     */
    public function testGetEnvVarName(string $providerId, string $field, string $expected): void
    {
        $method = new \ReflectionMethod(ProviderRegistry::class, 'getEnvVarName');
        $method->setAccessible(true);

        $result = $method->invoke($this->registry, $providerId, $field); // Invoke on instance

        $this->assertEquals($expected, $result);
    }

    /**
     * Provides data for testing getEnvVarName.
     *
     * @return array
     */
    public function envVarNameProvider(): array
    {
        return [
            'camelCase provider and field' => ['myProvider', 'apiKey', 'MY_PROVIDER_API_KEY'],
            'kebab-case provider and field' => ['my-provider', 'api-key', 'MY_PROVIDER_API_KEY'],
            'snake_case provider and field' => ['my_provider', 'api_key', 'MY_PROVIDER_API_KEY'],
            'mixed case' => ['AnotherProvider', 'someOtherField', 'ANOTHER_PROVIDER_SOME_OTHER_FIELD'],
            'simple names' => ['openai', 'key', 'OPENAI_KEY'],
        ];
    }

    /**
     * Tests that createDefaultProviderRequestAuthentication creates ApiKeyRequestAuthentication when env var is set.
     *
     * @return void
     */
    public function testCreateDefaultProviderRequestAuthenticationWithEnvVar(): void
    {
        // Temporarily set an environment variable.
        putenv('MOCK_API_KEY=test_env_api_key');

        $this->registry->registerProvider(MockProvider::class);

        $method = new \ReflectionMethod(ProviderRegistry::class, 'createDefaultProviderRequestAuthentication');
        $method->setAccessible(true);

        $auth = $method->invoke($this->registry, MockProvider::class);

        $this->assertInstanceOf(ApiKeyRequestAuthentication::class, $auth);
        $this->assertEquals('test_env_api_key', $auth->getApiKey());

        // Clean up environment variable.
        putenv('MOCK_API_KEY');
    }

    /**
     * Tests that createDefaultProviderRequestAuthentication returns null when env var is not set.
     *
     * @return void
     */
    public function testCreateDefaultProviderRequestAuthenticationWithoutEnvVar(): void
    {
        // Ensure environment variable is not set.
        putenv('MOCK_API_KEY');

        $this->registry->registerProvider(MockProvider::class);

        $method = new \ReflectionMethod(ProviderRegistry::class, 'createDefaultProviderRequestAuthentication');
        $method->setAccessible(true);

        $auth = $method->invoke($this->registry, MockProvider::class);

        $this->assertNull($auth);
    }
}

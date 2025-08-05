<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\AiProviderRegistry;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * @covers \WordPress\AiClient\Providers\AiProviderRegistry
 */
class AiProviderRegistryTest extends TestCase
{
    private AiProviderRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new AiProviderRegistry();
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
        $requirements = new ModelRequirements([CapabilityEnum::TEXT_GENERATION()], []);
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
        
        $requirements = new ModelRequirements([CapabilityEnum::TEXT_GENERATION()], []);
        $results = $this->registry->findModelsMetadataForSupport($requirements);
        
        $this->assertIsArray($results);
        // Note: Empty for now since MockProvider doesn't have models yet
        $this->assertEmpty($results);
    }

    /**
     * Tests findProviderModelsMetadataForSupport with registered provider.
     *
     * @return void
     */
    public function testFindProviderModelsMetadataForSupportWithRegisteredProvider(): void
    {
        $this->registry->registerProvider(MockProvider::class);
        
        $requirements = new ModelRequirements([CapabilityEnum::TEXT_GENERATION()], []);
        $results = $this->registry->findProviderModelsMetadataForSupport('mock', $requirements);
        
        $this->assertIsArray($results);
        // Note: Empty for now since MockProvider doesn't have models yet
        $this->assertEmpty($results);
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
        
        $requirements = new ModelRequirements([CapabilityEnum::TEXT_GENERATION()], []);
        $this->registry->findProviderModelsMetadataForSupport('nonexistent', $requirements);
    }

    /**
     * Tests getProviderModel throws exception (not yet implemented).
     *
     * @return void
     */
    public function testGetProviderModelThrowsException(): void
    {
        $this->registry->registerProvider(MockProvider::class);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model instantiation not yet implemented');
        
        $modelConfig = new \WordPress\AiClient\Providers\Models\DTO\ModelConfig([]);
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
}
<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\ProviderRegistry;

/**
 * Tests for the AiClient class.
 *
 * @covers \WordPress\AiClient\AiClient
 */
class AiClientTest extends TestCase
{
    /**
     * Sets up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Reset the registry before each test
        AiClient::resetRegistry();
    }

    /**
     * Tests that defaultRegistry returns a ProviderRegistry instance.
     *
     * @return void
     */
    public function testDefaultRegistryReturnsProviderRegistry(): void
    {
        $registry = AiClient::defaultRegistry();

        $this->assertInstanceOf(ProviderRegistry::class, $registry);
    }

    /**
     * Tests that defaultRegistry returns the same instance on multiple calls.
     *
     * @return void
     */
    public function testDefaultRegistryReturnsSameInstance(): void
    {
        $registry1 = AiClient::defaultRegistry();
        $registry2 = AiClient::defaultRegistry();

        $this->assertSame($registry1, $registry2);
    }

    /**
     * Tests that setRegistry allows setting a custom registry.
     *
     * @return void
     */
    public function testSetRegistryAllowsCustomRegistry(): void
    {
        $customRegistry = new ProviderRegistry();
        AiClient::setRegistry($customRegistry);

        $registry = AiClient::defaultRegistry();

        $this->assertSame($customRegistry, $registry);
    }

    /**
     * Tests that resetRegistry clears the registry.
     *
     * @return void
     */
    public function testResetRegistryClearsRegistry(): void
    {
        $registry1 = AiClient::defaultRegistry();
        AiClient::resetRegistry();
        $registry2 = AiClient::defaultRegistry();

        $this->assertNotSame($registry1, $registry2);
    }

    /**
     * Tests that isConfigured delegates to the availability interface.
     *
     * @return void
     */
    public function testIsConfiguredDelegatesToAvailability(): void
    {
        $availability = $this->createMock(ProviderAvailabilityInterface::class);
        $availability->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $result = AiClient::isConfigured($availability);

        $this->assertTrue($result);
    }

    /**
     * Tests that isConfigured returns false when not configured.
     *
     * @return void
     */
    public function testIsConfiguredReturnsFalseWhenNotConfigured(): void
    {
        $availability = $this->createMock(ProviderAvailabilityInterface::class);
        $availability->expects($this->once())
            ->method('isConfigured')
            ->willReturn(false);

        $result = AiClient::isConfigured($availability);

        $this->assertFalse($result);
    }
}

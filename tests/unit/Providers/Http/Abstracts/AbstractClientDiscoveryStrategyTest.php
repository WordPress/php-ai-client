<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\Abstracts;

use Http\Discovery\ClassDiscovery;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use WordPress\AiClient\Tests\mocks\ConcreteClientDiscoveryStrategy;

/**
 * @covers \WordPress\AiClient\Providers\Http\Abstracts\AbstractClientDiscoveryStrategy
 */
class AbstractClientDiscoveryStrategyTest extends TestCase
{
    /**
     * @var string[] Original discovery strategies to restore after each test.
     */
    private array $originalStrategies;

    /**
     * Saves the original discovery strategies before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var string[] $strategies */
        $strategies = ClassDiscovery::getStrategies();
        $this->originalStrategies = $strategies;
    }

    /**
     * Restores discovery strategies and resets static state after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ClassDiscovery::setStrategies($this->originalStrategies);
        ConcreteClientDiscoveryStrategy::reset();

        parent::tearDown();
    }

    /**
     * Tests that getCandidates returns a client candidate for PSR-18 ClientInterface type.
     *
     * @covers ::getCandidates
     *
     * @return void
     */
    public function testGetCandidatesReturnsClientCandidateForClientInterfaceType(): void
    {
        // Act
        $candidates = ConcreteClientDiscoveryStrategy::getCandidates(ClientInterface::class);

        // Assert
        $this->assertIsArray($candidates);
        $this->assertCount(1, $candidates);
        $this->assertArrayHasKey('class', $candidates[0]);
        $this->assertIsCallable($candidates[0]['class']);
    }

    /**
     * Tests that the client candidate closure invokes createClient with a Psr17Factory.
     *
     * @covers ::getCandidates
     *
     * @return void
     */
    public function testClientCandidateClosurePassesPsr17FactoryToCreateClient(): void
    {
        // Arrange
        $mockClient = new MockClient();
        ConcreteClientDiscoveryStrategy::setClientToReturn($mockClient);

        $candidates = ConcreteClientDiscoveryStrategy::getCandidates(ClientInterface::class);

        // Act
        $result = $candidates[0]['class']();

        // Assert
        $this->assertSame($mockClient, $result);
        $this->assertInstanceOf(Psr17Factory::class, ConcreteClientDiscoveryStrategy::getLastPsr17Factory());
    }

    /**
     * Tests that getCandidates returns Psr17Factory for PSR-17 request factory type.
     *
     * @covers ::getCandidates
     *
     * @return void
     */
    public function testGetCandidatesReturnsPsr17FactoryForRequestFactoryInterface(): void
    {
        // Act
        $candidates = ConcreteClientDiscoveryStrategy::getCandidates(
            'Psr\Http\Message\RequestFactoryInterface'
        );

        // Assert
        $this->assertIsArray($candidates);
        $this->assertCount(1, $candidates);
        $this->assertSame(Psr17Factory::class, $candidates[0]['class']);
    }

    /**
     * Tests that getCandidates returns Psr17Factory for PSR-17 response factory type.
     *
     * @covers ::getCandidates
     *
     * @return void
     */
    public function testGetCandidatesReturnsPsr17FactoryForResponseFactoryInterface(): void
    {
        // Act
        $candidates = ConcreteClientDiscoveryStrategy::getCandidates(
            'Psr\Http\Message\ResponseFactoryInterface'
        );

        // Assert
        $this->assertCount(1, $candidates);
        $this->assertSame(Psr17Factory::class, $candidates[0]['class']);
    }

    /**
     * Tests that getCandidates returns Psr17Factory for PSR-17 stream factory type.
     *
     * @covers ::getCandidates
     *
     * @return void
     */
    public function testGetCandidatesReturnsPsr17FactoryForStreamFactoryInterface(): void
    {
        // Act
        $candidates = ConcreteClientDiscoveryStrategy::getCandidates(
            'Psr\Http\Message\StreamFactoryInterface'
        );

        // Assert
        $this->assertCount(1, $candidates);
        $this->assertSame(Psr17Factory::class, $candidates[0]['class']);
    }

    /**
     * Tests that getCandidates returns Psr17Factory for PSR-17 URI factory type.
     *
     * @covers ::getCandidates
     *
     * @return void
     */
    public function testGetCandidatesReturnsPsr17FactoryForUriFactoryInterface(): void
    {
        // Act
        $candidates = ConcreteClientDiscoveryStrategy::getCandidates(
            'Psr\Http\Message\UriFactoryInterface'
        );

        // Assert
        $this->assertCount(1, $candidates);
        $this->assertSame(Psr17Factory::class, $candidates[0]['class']);
    }

    /**
     * Tests that getCandidates returns Psr17Factory for PSR-17 server request factory type.
     *
     * @covers ::getCandidates
     *
     * @return void
     */
    public function testGetCandidatesReturnsPsr17FactoryForServerRequestFactoryInterface(): void
    {
        // Act
        $candidates = ConcreteClientDiscoveryStrategy::getCandidates(
            'Psr\Http\Message\ServerRequestFactoryInterface'
        );

        // Assert
        $this->assertCount(1, $candidates);
        $this->assertSame(Psr17Factory::class, $candidates[0]['class']);
    }

    /**
     * Tests that getCandidates returns Psr17Factory for PSR-17 upload file factory type.
     *
     * @covers ::getCandidates
     *
     * @return void
     */
    public function testGetCandidatesReturnsPsr17FactoryForUploadedFileFactoryInterface(): void
    {
        // Act
        $candidates = ConcreteClientDiscoveryStrategy::getCandidates(
            'Psr\Http\Message\UploadedFileFactoryInterface'
        );

        // Assert
        $this->assertCount(1, $candidates);
        $this->assertSame(Psr17Factory::class, $candidates[0]['class']);
    }

    /**
     * Tests that getCandidates returns an empty array for an unknown type.
     *
     * @covers ::getCandidates
     *
     * @return void
     */
    public function testGetCandidatesReturnsEmptyArrayForUnknownType(): void
    {
        // Act
        $candidates = ConcreteClientDiscoveryStrategy::getCandidates('Some\Unknown\Interface');

        // Assert
        $this->assertIsArray($candidates);
        $this->assertCount(0, $candidates);
    }

    /**
     * Tests that init registers the strategy with Psr18ClientDiscovery.
     *
     * @covers ::init
     *
     * @return void
     */
    public function testInitRegistersStrategyWithDiscovery(): void
    {
        // Arrange
        $strategiesBefore = ClassDiscovery::getStrategies();

        // Act
        ConcreteClientDiscoveryStrategy::init();

        // Assert
        $strategiesAfter = ClassDiscovery::getStrategies();
        $this->assertContains(ConcreteClientDiscoveryStrategy::class, $strategiesAfter);
        $this->assertNotContains(ConcreteClientDiscoveryStrategy::class, $strategiesBefore);
    }
}

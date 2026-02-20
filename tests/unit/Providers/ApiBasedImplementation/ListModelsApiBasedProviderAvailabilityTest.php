<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\ApiBasedImplementation;

use Exception;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;

/**
 * @covers \WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability
 */
class ListModelsApiBasedProviderAvailabilityTest extends TestCase
{
    /**
     * @var ModelMetadataDirectoryInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $modelMetadataDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelMetadataDirectory = $this->createMock(ModelMetadataDirectoryInterface::class);
    }

    /**
     * Tests isConfigured() method when listing models succeeds.
     *
     * @return void
     */
    public function testIsConfiguredReturnsTrueOnSuccess(): void
    {
        $this->modelMetadataDirectory
            ->expects($this->once())
            ->method('listModelMetadata')
            ->willReturn([]);

        $availability = new ListModelsApiBasedProviderAvailability($this->modelMetadataDirectory);

        $this->assertTrue($availability->isConfigured());
    }

    /**
     * Tests isConfigured() method when listing models throws an exception.
     *
     * @return void
     */
    public function testIsConfiguredReturnsFalseOnException(): void
    {
        $this->modelMetadataDirectory
            ->expects($this->once())
            ->method('listModelMetadata')
            ->willThrowException(new Exception('API error'));

        $availability = new ListModelsApiBasedProviderAvailability($this->modelMetadataDirectory);

        $this->assertFalse($availability->isConfigured());
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\AbstractProvider;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Tests\mocks\MockAbstractProvider;
use WordPress\AiClient\Tests\mocks\MockModel;
use WordPress\AiClient\Tests\mocks\MockModelMetadataDirectory;
use WordPress\AiClient\Tests\mocks\MockProviderAvailability;

/**
 * @covers \WordPress\AiClient\Providers\AbstractProvider
 */
class AbstractProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        MockAbstractProvider::reset();

        // Clear static caches in AbstractProvider to ensure isolated tests.
        $reflectionClass = new \ReflectionClass(AbstractProvider::class);

        $metadataCacheProperty = $reflectionClass->getProperty('metadataCache');
        $metadataCacheProperty->setAccessible(true);
        $metadataCacheProperty->setValue(null, []);

        $availabilityCacheProperty = $reflectionClass->getProperty('availabilityCache');
        $availabilityCacheProperty->setAccessible(true);
        $availabilityCacheProperty->setValue(null, []);

        $modelMetadataDirectoryCacheProperty = $reflectionClass->getProperty('modelMetadataDirectoryCache');
        $modelMetadataDirectoryCacheProperty->setAccessible(true);
        $modelMetadataDirectoryCacheProperty->setValue(null, []);
    }

    /**
     * Tests the metadata() method.
     *
     * @return void
     */
    public function testMetadata(): void
    {
        $providerMetadata = $this->createMock(ProviderMetadata::class);
        MockAbstractProvider::$mockProviderMetadata = $providerMetadata;

        // Call metadata twice to ensure caching works
        $result1 = MockAbstractProvider::metadata();
        $result2 = MockAbstractProvider::metadata();

        $this->assertSame($providerMetadata, $result1);
        $this->assertSame($providerMetadata, $result2);
    }

    /**
     * Tests the model() method without ModelConfig.
     *
     * @return void
     */
    public function testModelWithoutModelConfig(): void
    {
        $modelId = 'test-model';
        $modelMetadata = $this->createMock(ModelMetadata::class);
        $providerMetadata = $this->createMock(ProviderMetadata::class);
        $model = $this->createMock(ModelInterface::class); // Use ModelInterface for the mock
        $mockModelMetadataDirectory = $this->createMock(ModelMetadataDirectoryInterface::class);

        // Set expectations on the mock that will be used by MockAbstractProvider
        $mockModelMetadataDirectory->expects($this->once())
                                   ->method('getModelMetadata')
                                   ->with($modelId)
                                   ->willReturn($modelMetadata);

        MockAbstractProvider::$mockProviderMetadata = $providerMetadata;
        MockAbstractProvider::$mockModelMetadataDirectory = $mockModelMetadataDirectory;
        MockAbstractProvider::$mockModel = $model;

        $model->expects($this->never())->method('setConfig');

        $result = MockAbstractProvider::model($modelId);

        $this->assertSame($model, $result);
    }

    /**
     * Tests the model() method with ModelConfig.
     *
     * @return void
     */
    public function testModelWithModelConfig(): void
    {
        $modelId = 'test-model';
        $modelConfig = $this->createMock(ModelConfig::class);
        $modelMetadata = $this->createMock(ModelMetadata::class);
        $providerMetadata = $this->createMock(ProviderMetadata::class);
        $model = $this->createMock(ModelInterface::class); // Use ModelInterface for the mock
        $mockModelMetadataDirectory = $this->createMock(ModelMetadataDirectoryInterface::class);

        // Set expectations on the mock that will be used by MockAbstractProvider
        $mockModelMetadataDirectory->expects($this->once())
                                   ->method('getModelMetadata')
                                   ->with($modelId)
                                   ->willReturn($modelMetadata);

        MockAbstractProvider::$mockProviderMetadata = $providerMetadata;
        MockAbstractProvider::$mockModelMetadataDirectory = $mockModelMetadataDirectory;
        MockAbstractProvider::$mockModel = $model;

        $model->expects($this->once())->method('setConfig')->with($modelConfig);

        $result = MockAbstractProvider::model($modelId, $modelConfig);

        $this->assertSame($model, $result);
    }

    /**
     * Tests the availability() method.
     *
     * @return void
     */
    public function testAvailability(): void
    {
        $providerAvailability = $this->createMock(ProviderAvailabilityInterface::class);
        MockAbstractProvider::$mockProviderAvailability = $providerAvailability;

        // Call availability twice to ensure caching works
        $result1 = MockAbstractProvider::availability();
        $result2 = MockAbstractProvider::availability();

        $this->assertSame($providerAvailability, $result1);
        $this->assertSame($providerAvailability, $result2);
    }

    /**
     * Tests the modelMetadataDirectory() method.
     *
     * @return void
     */
    public function testModelMetadataDirectory(): void
    {
        $modelMetadataDirectory = $this->createMock(ModelMetadataDirectoryInterface::class);
        MockAbstractProvider::$mockModelMetadataDirectory = $modelMetadataDirectory;

        // Call modelMetadataDirectory twice to ensure caching works
        $result1 = MockAbstractProvider::modelMetadataDirectory();
        $result2 = MockAbstractProvider::modelMetadataDirectory();

        $this->assertSame($modelMetadataDirectory, $result1);
        $this->assertSame($modelMetadataDirectory, $result2);
    }

    /**
     * Tests that the caches are reset between tests for different concrete provider classes.
     *
     * @return void
     */
    public function testCachesArePerConcreteClass(): void
    {
        // Create two distinct anonymous classes extending AbstractProvider
        $mockProviderClass1 = new class extends AbstractProvider {
            protected static function createModel(
                ModelMetadata $modelMetadata,
                ProviderMetadata $providerMetadata
            ): ModelInterface {
                return new MockModel();
            }
            protected static function createProviderMetadata(): ProviderMetadata
            {
                return new ProviderMetadata('mock-provider-1', 'Mock Provider 1');
            }
            protected static function createProviderAvailability(): ProviderAvailabilityInterface
            {
                return new MockProviderAvailability();
            }
            protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
            {
                return new MockModelMetadataDirectory();
            }
        };

        $mockProviderClass2 = new class extends AbstractProvider {
            protected static function createModel(
                ModelMetadata $modelMetadata,
                ProviderMetadata $providerMetadata
            ): ModelInterface {
                return new MockModel();
            }
            protected static function createProviderMetadata(): ProviderMetadata
            {
                return new ProviderMetadata('mock-provider-2', 'Mock Provider 2');
            }
            protected static function createProviderAvailability(): ProviderAvailabilityInterface
            {
                return new MockProviderAvailability();
            }
            protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
            {
                return new MockModelMetadataDirectory();
            }
        };

        // Get metadata for the first provider
        $metadata1_1 = $mockProviderClass1::metadata();
        $metadata1_2 = $mockProviderClass1::metadata(); // Should be cached

        // Get metadata for the second provider
        $metadata2_1 = $mockProviderClass2::metadata();
        $metadata2_2 = $mockProviderClass2::metadata(); // Should be cached

        // Assert that the first provider's metadata is consistent and distinct from the second
        $this->assertSame($metadata1_1, $metadata1_2);
        $this->assertEquals('mock-provider-1', $metadata1_1->getId());
        $this->assertNotSame($metadata1_1, $metadata2_1); // Ensure they are different instances

        // Assert that the second provider's metadata is consistent
        $this->assertSame($metadata2_1, $metadata2_2);
        $this->assertEquals('mock-provider-2', $metadata2_1->getId());

        // Repeat for availability
        $availability1_1 = $mockProviderClass1::availability();
        $availability1_2 = $mockProviderClass1::availability();
        $availability2_1 = $mockProviderClass2::availability();
        $availability2_2 = $mockProviderClass2::availability();

        $this->assertSame($availability1_1, $availability1_2);
        $this->assertNotSame($availability1_1, $availability2_1);
        $this->assertSame($availability2_1, $availability2_2);

        // Repeat for modelMetadataDirectory
        $modelMetadataDirectory1_1 = $mockProviderClass1::modelMetadataDirectory();
        $modelMetadataDirectory1_2 = $mockProviderClass1::modelMetadataDirectory();
        $modelMetadataDirectory2_1 = $mockProviderClass2::modelMetadataDirectory();
        $modelMetadataDirectory2_2 = $mockProviderClass2::modelMetadataDirectory();

        $this->assertSame($modelMetadataDirectory1_1, $modelMetadataDirectory1_2);
        $this->assertNotSame($modelMetadataDirectory1_1, $modelMetadataDirectory2_1);
        $this->assertSame($modelMetadataDirectory2_1, $modelMetadataDirectory2_2);
    }
}

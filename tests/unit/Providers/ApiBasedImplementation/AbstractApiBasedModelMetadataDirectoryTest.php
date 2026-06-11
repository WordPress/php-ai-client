<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\ApiBasedImplementation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Tests\mocks\MockCache;

/**
 * @covers \WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModelMetadataDirectory
 */
class AbstractApiBasedModelMetadataDirectoryTest extends TestCase
{
    /**
     * @var array<string, ModelMetadata>
     */
    private array $mockModels;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockModels = [
            'model-1' => $this->createStub(ModelMetadata::class),
            'model-2' => $this->createStub(ModelMetadata::class),
        ];
    }

    protected function tearDown(): void
    {
        AiClient::setCache(null);

        parent::tearDown();
    }

    /**
     * Tests listModelMetadata() method.
     *
     * @return void
     */
    public function testListModelMetadata(): void
    {
        $directory = new MockApiBasedModelMetadataDirectory($this->mockModels);
        $models = $directory->listModelMetadata();

        $this->assertIsArray($models);
        $this->assertCount(2, $models);
        $this->assertContains($this->mockModels['model-1'], $models);
        $this->assertContains($this->mockModels['model-2'], $models);
    }

    /**
     * Tests hasModelMetadata() method.
     *
     * @return void
     */
    public function testHasModelMetadata(): void
    {
        $directory = new MockApiBasedModelMetadataDirectory($this->mockModels);

        $this->assertTrue($directory->hasModelMetadata('model-1'));
        $this->assertFalse($directory->hasModelMetadata('non-existent-model'));
    }

    /**
     * Tests getModelMetadata() method.
     *
     * @return void
     */
    public function testGetModelMetadata(): void
    {
        $directory = new MockApiBasedModelMetadataDirectory($this->mockModels);

        $this->assertSame($this->mockModels['model-1'], $directory->getModelMetadata('model-1'));
    }

    /**
     * Tests getModelMetadata() returns explicit metadata without listing models.
     *
     * @return void
     */
    public function testGetModelMetadataReturnsExplicitMetadataWithoutListingModels(): void
    {
        $explicitModelMetadata = $this->createStub(ModelMetadata::class);
        $explicitModelMetadata->method('getId')->willReturn('explicit-model');
        $directory = new MockApiBasedModelMetadataDirectory([], $explicitModelMetadata);

        $this->assertSame($explicitModelMetadata, $directory->getModelMetadata('explicit-model'));
        $this->assertSame(0, $directory->getListRequestCount());
    }

    /**
     * Tests explicit model metadata is preferred over cached metadata.
     *
     * @return void
     */
    public function testGetModelMetadataPrefersExplicitMetadataOverCachedMetadata(): void
    {
        $cache = new MockCache();
        $cacheKey = 'ai_client_' . AiClient::VERSION . '_' . md5(MockApiBasedModelMetadataDirectory::class) . '_models';
        $cachedModelMetadata = $this->createStub(ModelMetadata::class);
        $cachedModelMetadata->method('getId')->willReturn('explicit-model');
        $cache->seed($cacheKey, ['explicit-model' => $cachedModelMetadata]);
        AiClient::setCache($cache);

        $explicitModelMetadata = $this->createStub(ModelMetadata::class);
        $explicitModelMetadata->method('getId')->willReturn('explicit-model');
        $directory = new MockApiBasedModelMetadataDirectory([], $explicitModelMetadata);

        $this->assertSame($explicitModelMetadata, $directory->getModelMetadata('explicit-model'));
        $this->assertSame(0, $directory->getListRequestCount());
    }

    /**
     * Tests cached metadata misses can still fall back to explicit metadata.
     *
     * @return void
     */
    public function testGetModelMetadataUsesExplicitMetadataAfterCachedMetadataMiss(): void
    {
        $cache = new MockCache();
        $cacheKey = 'ai_client_' . AiClient::VERSION . '_' . md5(MockApiBasedModelMetadataDirectory::class) . '_models';
        $otherModelMetadata = $this->createStub(ModelMetadata::class);
        $otherModelMetadata->method('getId')->willReturn('other-model');
        $cache->seed($cacheKey, ['other-model' => $otherModelMetadata]);
        AiClient::setCache($cache);

        $explicitModelMetadata = $this->createStub(ModelMetadata::class);
        $explicitModelMetadata->method('getId')->willReturn('explicit-model');
        $directory = new MockApiBasedModelMetadataDirectory([], $explicitModelMetadata);

        $this->assertSame($explicitModelMetadata, $directory->getModelMetadata('explicit-model'));
        $this->assertSame(0, $directory->getListRequestCount());
    }

    /**
     * Tests hasModelMetadata() returns true for explicit metadata without listing models.
     *
     * @return void
     */
    public function testHasModelMetadataReturnsTrueForExplicitMetadataWithoutListingModels(): void
    {
        $explicitModelMetadata = $this->createStub(ModelMetadata::class);
        $explicitModelMetadata->method('getId')->willReturn('explicit-model');
        $directory = new MockApiBasedModelMetadataDirectory([], $explicitModelMetadata);

        $this->assertTrue($directory->hasModelMetadata('explicit-model'));
        $this->assertSame(0, $directory->getListRequestCount());
    }

    /**
     * Tests listModelMetadata() applies explicit metadata overrides to listed models.
     *
     * @return void
     */
    public function testListModelMetadataAppliesExplicitMetadataOverrides(): void
    {
        $listedModelMetadata = $this->createStub(ModelMetadata::class);
        $listedModelMetadata->method('getId')->willReturn('explicit-model');

        $explicitModelMetadata = $this->createStub(ModelMetadata::class);
        $explicitModelMetadata->method('getId')->willReturn('explicit-model');

        $directory = new MockApiBasedModelMetadataDirectory(
            ['explicit-model' => $listedModelMetadata],
            $explicitModelMetadata
        );

        $models = $directory->listModelMetadata();

        $this->assertCount(1, $models);
        $this->assertSame($explicitModelMetadata, $models[0]);
        $this->assertSame($explicitModelMetadata, $directory->getModelMetadata('explicit-model'));
        $this->assertSame(1, $directory->getExplicitModelMetadataLookupCount());
    }

    /**
     * Tests listModelMetadata() applies explicit metadata overrides to cached models.
     *
     * @return void
     */
    public function testListModelMetadataAppliesExplicitMetadataOverridesToCachedModels(): void
    {
        $cache = new MockCache();
        $cacheKey = 'ai_client_' . AiClient::VERSION . '_' . md5(MockApiBasedModelMetadataDirectory::class) . '_models';

        $cachedModelMetadata = $this->createStub(ModelMetadata::class);
        $cachedModelMetadata->method('getId')->willReturn('explicit-model');
        $cache->seed($cacheKey, ['explicit-model' => $cachedModelMetadata]);
        AiClient::setCache($cache);

        $explicitModelMetadata = $this->createStub(ModelMetadata::class);
        $explicitModelMetadata->method('getId')->willReturn('explicit-model');
        $directory = new MockApiBasedModelMetadataDirectory([], $explicitModelMetadata);

        $models = $directory->listModelMetadata();

        $this->assertCount(1, $models);
        $this->assertSame($explicitModelMetadata, $models[0]);
        $this->assertSame(0, $directory->getListRequestCount());
    }

    /**
     * Tests explicit model metadata is memoized for a directory instance.
     *
     * @return void
     */
    public function testExplicitModelMetadataIsMemoized(): void
    {
        $explicitModelMetadata = $this->createStub(ModelMetadata::class);
        $explicitModelMetadata->method('getId')->willReturn('explicit-model');
        $directory = new MockApiBasedModelMetadataDirectory([], $explicitModelMetadata);

        $this->assertSame($explicitModelMetadata, $directory->getModelMetadata('explicit-model'));
        $this->assertSame($explicitModelMetadata, $directory->getModelMetadata('explicit-model'));
        $this->assertTrue($directory->hasModelMetadata('explicit-model'));
        $this->assertSame(1, $directory->getExplicitModelMetadataLookupCount());
        $this->assertSame(0, $directory->getListRequestCount());
    }

    /**
     * Tests getModelMetadata() method with non-existent model.
     *
     * @return void
     */
    public function testGetModelMetadataThrowsExceptionForNonExistentModel(): void
    {
        $directory = new MockApiBasedModelMetadataDirectory($this->mockModels);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No model with ID non-existent-model was found in the provider');

        $directory->getModelMetadata('non-existent-model');
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\ApiBasedImplementation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

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

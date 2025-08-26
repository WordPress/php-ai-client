<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\ApiBasedImplementation;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * @covers \WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiBasedModel
 */
class AbstractApiBasedModelTest extends TestCase
{
    /**
     * @var ModelMetadata
     */
    private $modelMetadata;

    /**
     * @var ProviderMetadata
     */
    private $providerMetadata;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelMetadata = $this->createStub(ModelMetadata::class);
        $this->providerMetadata = $this->createStub(ProviderMetadata::class);
    }

    /**
     * Tests the constructor and initial state.
     *
     * @return void
     */
    public function testConstructorAndInitialState(): void
    {
        $model = new MockApiBasedModel($this->modelMetadata, $this->providerMetadata);

        $this->assertSame($this->modelMetadata, $model->metadata());
        $this->assertSame($this->providerMetadata, $model->providerMetadata());
        $this->assertInstanceOf(ModelConfig::class, $model->getConfig());
        $this->assertEquals(['customOptions' => []], $model->getConfig()->toArray());
    }

    /**
     * Tests the metadata() method.
     *
     * @return void
     */
    public function testMetadata(): void
    {
        $model = new MockApiBasedModel($this->modelMetadata, $this->providerMetadata);
        $this->assertSame($this->modelMetadata, $model->metadata());
    }

    /**
     * Tests the providerMetadata() method.
     *
     * @return void
     */
    public function testProviderMetadata(): void
    {
        $model = new MockApiBasedModel($this->modelMetadata, $this->providerMetadata);
        $this->assertSame($this->providerMetadata, $model->providerMetadata());
    }

    /**
     * Tests the setConfig() and getConfig() methods.
     *
     * @return void
     */
    public function testSetConfigAndGetConfig(): void
    {
        $model = new MockApiBasedModel($this->modelMetadata, $this->providerMetadata);
        $initialConfig = $model->getConfig();

        $newConfig = ModelConfig::fromArray(['temperature' => 0.7]);
        $model->setConfig($newConfig);

        $this->assertSame($newConfig, $model->getConfig());
        $this->assertNotSame($initialConfig, $model->getConfig());
        $this->assertEquals(['temperature' => 0.7, 'customOptions' => []], $model->getConfig()->toArray());
    }
}

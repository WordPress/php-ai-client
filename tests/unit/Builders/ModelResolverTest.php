<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Builders\ModelResolver;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\ApiBasedImplementation\Contracts\ApiBasedModelInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Tests\mocks\MockModelMetadataDirectory;
use WordPress\AiClient\Tests\mocks\MockProvider;

/**
 * Tests model resolution shared by fluent builders.
 *
 * @covers \WordPress\AiClient\Builders\ModelResolver
 */
class ModelResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        MockProvider::reset();
        parent::tearDown();
    }

    public function testExplicitModelReceivesMergedConfiguration(): void
    {
        $modelConfig = new ModelConfig();
        $modelConfig->setMaxTokens(100);
        $model = $this->createModel($modelConfig);
        $resolver = new ModelResolver($this->createRegistry());
        $resolver->getModelConfig()->setTemperature(0.4);
        $resolver->usingModel($model);

        $resolved = $resolver->resolve($this->requirements());

        $this->assertSame($model, $resolved);
        $this->assertSame(100, $resolved->getConfig()->getMaxTokens());
        $this->assertSame(0.4, $resolved->getConfig()->getTemperature());
    }

    public function testAppliesRequestOptionsToAnExplicitApiModel(): void
    {
        $model = $this->createApiModel();
        $options = new RequestOptions();
        $options->setTimeout(3.5);
        $resolver = new ModelResolver($this->createRegistry());
        $resolver->usingModel($model);
        $resolver->usingRequestOptions($options);

        $resolved = $resolver->resolve($this->requirements());

        $this->assertSame($options, $resolved->getRequestOptions());
    }

    public function testFiltersDiscoveryToTheRequestedProviderAndHonorsPreferenceOrder(): void
    {
        MockProvider::setModelMetadataDirectory(new MockModelMetadataDirectory([
            'first' => $this->metadata('first'),
            'preferred' => $this->metadata('preferred'),
        ]));
        $registry = new ProviderRegistry();
        $registry->registerProvider(MockProvider::class);
        $resolver = new ModelResolver($registry);
        $resolver->usingProvider('mock');
        $resolver->usingModelPreference('missing', 'preferred', 'first');

        $resolved = $resolver->resolve($this->requirements());

        $this->assertSame('mock', $resolved->providerMetadata()->getId());
        $this->assertSame('preferred', $resolved->metadata()->getId());
    }

    public function testReportsNoModelForTheSelectedProvider(): void
    {
        MockProvider::setModelMetadataDirectory(new MockModelMetadataDirectory([]));
        $registry = new ProviderRegistry();
        $registry->registerProvider(MockProvider::class);
        $resolver = new ModelResolver($registry);
        $resolver->usingProvider('mock');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'No models found for provider "mock" that support text_generation for this prompt.'
        );

        $resolver->resolve($this->requirements(), 'prompt');
    }

    public function testReportsCapabilityNeutralRequirementsWithoutAnUndefinedOffset(): void
    {
        $resolver = new ModelResolver(new ProviderRegistry());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No models found that meet the requested requirements.');

        $resolver->resolve(new ModelRequirements([], []));
    }

    public function testChecksSupportUsingTheConfiguredProvider(): void
    {
        MockProvider::setModelMetadataDirectory(new MockModelMetadataDirectory([
            'text' => $this->metadata('text'),
        ]));
        $registry = new ProviderRegistry();
        $registry->registerProvider(MockProvider::class);
        $resolver = new ModelResolver($registry);
        $resolver->usingProvider('mock');

        $this->assertTrue($resolver->hasCandidate($this->requirements()));
        $this->assertFalse($resolver->hasCandidate(new ModelRequirements([CapabilityEnum::embeddingGeneration()], [])));
    }

    public function testCloneKeepsConfigurationAndRequestOptionsIndependent(): void
    {
        $options = new RequestOptions();
        $options->setTimeout(1.0);
        $resolver = new ModelResolver(new ProviderRegistry());
        $resolver->getModelConfig()->setDimensions(64);
        $resolver->usingRequestOptions($options);
        $clone = clone $resolver;
        $clone->getModelConfig()->setDimensions(128);
        $clone->getRequestOptions()->setTimeout(2.0);

        $this->assertSame(64, $resolver->getModelConfig()->getDimensions());
        $this->assertSame(1.0, $resolver->getRequestOptions()->getTimeout());
        $this->assertSame(128, $clone->getModelConfig()->getDimensions());
        $this->assertSame(2.0, $clone->getRequestOptions()->getTimeout());
    }

    private function requirements(): ModelRequirements
    {
        return new ModelRequirements([CapabilityEnum::textGeneration()], []);
    }

    private function metadata(string $id): ModelMetadata
    {
        return new ModelMetadata($id, $id, [CapabilityEnum::textGeneration()], []);
    }

    private function createRegistry(): ProviderRegistry
    {
        $registry = new ProviderRegistry();
        $registry->registerProvider(MockProvider::class);
        return $registry;
    }

    private function createModel(ModelConfig $config): ModelInterface
    {
        return new class ($config) implements ModelInterface {
            private ModelConfig $config;

            public function __construct(ModelConfig $config)
            {
                $this->config = $config;
            }

            public function metadata(): ModelMetadata
            {
                return new ModelMetadata('explicit', 'Explicit', [CapabilityEnum::textGeneration()], []);
            }

            public function providerMetadata(): ProviderMetadata
            {
                return new ProviderMetadata('mock', 'Mock', ProviderTypeEnum::cloud());
            }

            public function setConfig(ModelConfig $config): void
            {
                $this->config = $config;
            }

            public function getConfig(): ModelConfig
            {
                return $this->config;
            }
        };
    }

    private function createApiModel(): ApiBasedModelInterface
    {
        return new class () implements ApiBasedModelInterface {
            private ModelConfig $config;
            private ?RequestOptions $requestOptions = null;

            public function __construct()
            {
                $this->config = new ModelConfig();
            }

            public function metadata(): ModelMetadata
            {
                return new ModelMetadata('api', 'API', [CapabilityEnum::textGeneration()], []);
            }

            public function providerMetadata(): ProviderMetadata
            {
                return new ProviderMetadata('mock', 'Mock', ProviderTypeEnum::cloud());
            }

            public function setConfig(ModelConfig $config): void
            {
                $this->config = $config;
            }

            public function getConfig(): ModelConfig
            {
                return $this->config;
            }

            public function setRequestOptions(RequestOptions $requestOptions): void
            {
                $this->requestOptions = $requestOptions;
            }

            public function getRequestOptions(): ?RequestOptions
            {
                return $this->requestOptions;
            }
        };
    }
}

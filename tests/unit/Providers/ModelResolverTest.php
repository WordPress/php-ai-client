<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\ApiBasedImplementation\Contracts\ApiBasedModelInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\DTO\ProviderModelsMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\ModelResolver;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Providers\ModelResolver
 */
class ModelResolverTest extends TestCase
{
    use MockModelCreationTrait;

    /**
     * @var ProviderRegistry&\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = $this->createMock(ProviderRegistry::class);
    }

    /**
     * Builds text generation requirements for resolution tests.
     *
     * @return ModelRequirements
     */
    private function textRequirements(): ModelRequirements
    {
        return new ModelRequirements([CapabilityEnum::textGeneration()], []);
    }

    /**
     * Reads a private property value from the resolver.
     *
     * @param ModelResolver $resolver The resolver to inspect.
     * @param string $propertyName The property name.
     * @return mixed The property value.
     */
    private function getResolverProperty(ModelResolver $resolver, string $propertyName)
    {
        $reflection = new \ReflectionClass($resolver);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($resolver);
    }

    /**
     * Tests getModel returns null before any model is set.
     *
     * @return void
     */
    public function testGetModelReturnsNullByDefault(): void
    {
        $resolver = new ModelResolver($this->registry);

        $this->assertNull($resolver->getModel());
    }

    /**
     * Tests setModel stores the model for retrieval.
     *
     * @return void
     */
    public function testSetModelStoresModel(): void
    {
        $model = $this->createMock(ModelInterface::class);

        $resolver = new ModelResolver($this->registry);
        $resolver->setModel($model);

        $this->assertSame($model, $resolver->getModel());
    }

    /**
     * Tests setModelPreferences rejects an empty argument list.
     *
     * @return void
     */
    public function testSetModelPreferencesRejectsEmptyList(): void
    {
        $resolver = new ModelResolver($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one model preference must be provided.');

        $resolver->setModelPreferences();
    }

    /**
     * Tests setModelPreferences rejects a malformed tuple.
     *
     * @return void
     */
    public function testSetModelPreferencesRejectsInvalidTuple(): void
    {
        $resolver = new ModelResolver($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model preference tuple must contain model identifier and provider ID.');

        $resolver->setModelPreferences(['only-one-element']);
    }

    /**
     * Tests setModelPreferences rejects an empty identifier string.
     *
     * @return void
     */
    public function testSetModelPreferencesRejectsEmptyIdentifier(): void
    {
        $resolver = new ModelResolver($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model preference identifiers cannot be empty.');

        $resolver->setModelPreferences('   ');
    }

    /**
     * Tests setModelPreferences rejects an invalid preference type.
     *
     * @return void
     */
    public function testSetModelPreferencesRejectsInvalidType(): void
    {
        $resolver = new ModelResolver($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model preferences must be model identifiers');

        /** @phpstan-ignore-next-line Intentionally passing an invalid type. */
        $resolver->setModelPreferences(123);
    }

    /**
     * Tests resolve returns the explicitly set model and binds its dependencies.
     *
     * @return void
     */
    public function testResolveWithExplicitModel(): void
    {
        $result = $this->createTestResult('Explicit model');
        $metadata = $this->createTestTextModelMetadata('explicit-model');
        $model = $this->createMockTextGenerationModel($result, $metadata);

        $this->registry->expects($this->once())
            ->method('bindModelDependencies')
            ->with($model);

        $this->registry->expects($this->never())
            ->method('findModelsMetadataForSupport');

        $config = new ModelConfig();

        $resolver = new ModelResolver($this->registry);
        $resolver->setModel($model);

        $resolved = $resolver->resolve($this->textRequirements(), $config);

        $this->assertSame($model, $resolved);
        $this->assertSame($config, $model->getConfig());
    }

    /**
     * Tests resolve throws with a clear message when no candidate models exist.
     *
     * @return void
     */
    public function testResolveThrowsWhenNoCandidates(): void
    {
        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->willReturn([]);

        $resolver = new ModelResolver($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No models found that support text_generation.');

        $resolver->resolve($this->textRequirements(), new ModelConfig());
    }

    /**
     * Tests resolve reports empty requirements without inventing a capability.
     *
     * @return void
     */
    public function testResolveThrowsWhenRequirementsAreEmpty(): void
    {
        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->willReturn([]);

        $resolver = new ModelResolver($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No models found that meet the requested requirements.');

        $resolver->resolve(new ModelRequirements([], []), new ModelConfig());
    }

    /**
     * Tests resolve includes the provider in the message when a provider is set.
     *
     * @return void
     */
    public function testResolveThrowsWhenNoCandidatesWithProvider(): void
    {
        $this->registry->expects($this->once())
            ->method('findProviderModelsMetadataForSupport')
            ->willReturn([]);

        $this->registry->method('getProviderId')->willReturn('test-provider');

        $resolver = new ModelResolver($this->registry);
        $resolver->setProvider('test-provider');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'No models found for provider "test-provider" that support text_generation.'
        );

        $resolver->resolve($this->textRequirements(), new ModelConfig());
    }

    /**
     * Tests resolve honors model preferences in priority order.
     *
     * @return void
     */
    public function testResolvePrefersMatchingPreference(): void
    {
        $result = $this->createTestResult('Preferred');
        $firstMeta = $this->createTestTextModelMetadata('first-model');
        $secondMeta = $this->createTestTextModelMetadata('second-model');
        $providerMetadata = new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud());
        $model = $this->createMockTextGenerationModel($result, $secondMeta);

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->willReturn([new ProviderModelsMetadata($providerMetadata, [$firstMeta, $secondMeta])]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('mock', 'second-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $resolver = new ModelResolver($this->registry);
        $resolver->setModelPreferences('second-model');

        $resolved = $resolver->resolve($this->textRequirements(), new ModelConfig());

        $this->assertSame($model, $resolved);
    }

    /**
     * Tests resolve falls back to the first candidate when no preference matches.
     *
     * @return void
     */
    public function testResolveFallsBackToFirstCandidate(): void
    {
        $result = $this->createTestResult('Fallback');
        $firstMeta = $this->createTestTextModelMetadata('first-model');
        $providerMetadata = new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud());
        $model = $this->createMockTextGenerationModel($result, $firstMeta);

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->willReturn([new ProviderModelsMetadata($providerMetadata, [$firstMeta])]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('mock', 'first-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $resolver = new ModelResolver($this->registry);

        $resolved = $resolver->resolve($this->textRequirements(), new ModelConfig());

        $this->assertSame($model, $resolved);
    }

    /**
     * Tests resolve restricts candidate lookup to the configured provider.
     *
     * @return void
     */
    public function testResolveWithProviderScopedLookup(): void
    {
        $result = $this->createTestResult('Provider scoped');
        $metadata = $this->createTestTextModelMetadata('scoped-model');
        $model = $this->createMockTextGenerationModel($result, $metadata);

        $this->registry->expects($this->once())
            ->method('findProviderModelsMetadataForSupport')
            ->with('test-provider', $this->isInstanceOf(ModelRequirements::class))
            ->willReturn([$metadata]);

        $this->registry->expects($this->once())
            ->method('getProviderId')
            ->with('test-provider')
            ->willReturn('test-provider');

        $this->registry->expects($this->never())
            ->method('findModelsMetadataForSupport');

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('test-provider', 'scoped-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $resolver = new ModelResolver($this->registry);
        $resolver->setProvider('test-provider');

        $resolved = $resolver->resolve($this->textRequirements(), new ModelConfig());

        $this->assertSame($model, $resolved);
    }

    /**
     * Tests resolve binds request options to API-based models.
     *
     * @return void
     */
    public function testResolveBindsRequestOptionsToExplicitModel(): void
    {
        $requestOptions = new RequestOptions();
        $requestOptions->setTimeout(42.0);

        $model = $this->createMock(ApiBasedModelInterface::class);
        $model->expects($this->once())
            ->method('setRequestOptions')
            ->with($requestOptions);

        $this->registry->method('bindModelDependencies');

        $resolver = new ModelResolver($this->registry);
        $resolver->setModel($model);
        $resolver->setRequestOptions($requestOptions);

        $resolver->resolve($this->textRequirements(), new ModelConfig());
    }

    /**
     * Tests isSupported checks the explicitly set model against requirements.
     *
     * @return void
     */
    public function testIsSupportedWithExplicitModel(): void
    {
        $result = $this->createTestResult('Supported');
        $metadata = $this->createTestTextModelMetadata('supported-model');
        $model = $this->createMockTextGenerationModel($result, $metadata);

        $this->registry->expects($this->never())
            ->method('findModelsMetadataForSupport');

        $resolver = new ModelResolver($this->registry);
        $resolver->setModel($model);

        $this->assertTrue($resolver->isSupported($this->textRequirements()));
    }

    /**
     * Tests isSupported returns true when the registry finds supporting models.
     *
     * @return void
     */
    public function testIsSupportedViaRegistry(): void
    {
        $metadata = $this->createTestTextModelMetadata('registry-model');
        $providerMetadata = new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud());

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->willReturn([new ProviderModelsMetadata($providerMetadata, [$metadata])]);

        $resolver = new ModelResolver($this->registry);

        $this->assertTrue($resolver->isSupported($this->textRequirements()));
    }

    /**
     * Tests isSupported returns false when no registered models qualify.
     *
     * @return void
     */
    public function testIsSupportedReturnsFalseWhenNoModels(): void
    {
        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->willReturn([]);

        $resolver = new ModelResolver($this->registry);

        $this->assertFalse($resolver->isSupported($this->textRequirements()));
    }

    /**
     * Tests isSupported returns false when the registry throws.
     *
     * @return void
     */
    public function testIsSupportedReturnsFalseOnException(): void
    {
        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->willThrowException(new InvalidArgumentException('boom'));

        $resolver = new ModelResolver($this->registry);

        $this->assertFalse($resolver->isSupported($this->textRequirements()));
    }

    /**
     * Tests cloning creates an independent request options instance.
     *
     * @return void
     */
    public function testCloneCreatesIndependentRequestOptions(): void
    {
        $requestOptions = new RequestOptions();
        $requestOptions->setTimeout(30.0);

        $original = new ModelResolver($this->registry);
        $original->setRequestOptions($requestOptions);

        $cloned = clone $original;

        $originalOptions = $this->getResolverProperty($original, 'requestOptions');
        $clonedOptions = $this->getResolverProperty($cloned, 'requestOptions');

        $this->assertNotSame($originalOptions, $clonedOptions);
        $this->assertEquals($originalOptions->getTimeout(), $clonedOptions->getTimeout());
    }

    /**
     * Tests cloning leaves null request options untouched.
     *
     * @return void
     */
    public function testCloneWorksWithNullRequestOptions(): void
    {
        $original = new ModelResolver($this->registry);

        $cloned = clone $original;

        $this->assertNull($this->getResolverProperty($cloned, 'requestOptions'));
    }
}

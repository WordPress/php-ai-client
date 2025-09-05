<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\ProviderImplementations\OpenAi\OpenAiProvider;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\AiClient
 */
class AiClientTest extends TestCase
{
    use MockModelCreationTrait;

    protected function setUp(): void
    {
        // Tests use dependency injection - registry instances passed directly to methods
    }


    protected function tearDown(): void
    {
        // Tests use dependency injection - registry instances passed directly to methods
    }

    /**
     * Creates a mock registry that returns empty results for model discovery.
     *
     * @return ProviderRegistry The mock registry.
     */
    private function createMockEmptyRegistry(): ProviderRegistry
    {
        $mockRegistry = $this->createMock(ProviderRegistry::class);
        $mockRegistry
            ->expects($this->any())
            ->method('findModelsMetadataForSupport')
            ->willReturn([]);

        return $mockRegistry;
    }

    /**
     * Tests default registry getter.
     */
    public function testDefaultRegistry(): void
    {
        // Test that default registry is created as ProviderRegistry instance
        $registry = AiClient::defaultRegistry();
        $this->assertInstanceOf(
            ProviderRegistry::class,
            $registry,
            'Default registry should be a ProviderRegistry instance'
        );

        // Test that the same instance is returned on subsequent calls
        $sameRegistry = AiClient::defaultRegistry();
        $this->assertSame(
            $registry,
            $sameRegistry,
            'Default registry should return the same instance (singleton pattern)'
        );

        // Registry dependency injection is tested by passing custom registries to individual methods
    }

    /**
     * Tests message method throws exception when MessageBuilder is not available.
     */
    public function testMessageThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'MessageBuilder is not yet available. This method depends on builder infrastructure. ' .
            'Use direct generation methods (generateTextResult, generateImageResult, etc.) for now.'
        );

        AiClient::message('Test message');
    }

    /**
     * Tests generateTextResult with string prompt and provided model.
     */
    public function testGenerateTextResultWithStringAndModel(): void
    {
        $prompt = 'Generate text';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);
        $registry = $this->createRegistryWithMockProvider();

        $result = AiClient::generateTextResult($prompt, $mockModel, $registry);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateTextResult throws exception for model without text generation interface.
     */
    public function testGenerateTextResultWithInvalidModel(): void
    {
        $prompt = 'Generate text';
        $invalidModel = $this->createMockUnsupportedModel('invalid-text-model');
        $registry = $this->createRegistryWithMockProvider();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "invalid-text-model" does not support text generation.');

        AiClient::generateTextResult($prompt, $invalidModel, $registry);
    }

    /**
     * Tests generateImageResult with string prompt and provided model.
     */
    public function testGenerateImageResultWithStringAndModel(): void
    {
        $prompt = 'Generate image';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockImageGenerationModel($expectedResult);
        $registry = $this->createRegistryWithMockProvider();

        $result = AiClient::generateImageResult($prompt, $mockModel, $registry);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateImageResult throws exception for model without image generation interface.
     */
    public function testGenerateImageResultWithInvalidModel(): void
    {
        $prompt = 'Generate image';
        $invalidModel = $this->createMockUnsupportedModel('invalid-image-model');
        $registry = $this->createRegistryWithMockProvider();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "invalid-image-model" does not support image generation.');

        AiClient::generateImageResult($prompt, $invalidModel, $registry);
    }


    /**
     * Tests generateTextResult with Message object.
     */
    public function testGenerateTextResultWithMessage(): void
    {
        $messagePart = new MessagePart('Test message');
        $message = new UserMessage([$messagePart]);
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);
        $registry = $this->createRegistryWithMockProvider();

        $result = AiClient::generateTextResult($message, $mockModel, $registry);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateTextResult with MessagePart object.
     */
    public function testGenerateTextResultWithMessagePart(): void
    {
        $messagePart = new MessagePart('Test message part');
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);
        $registry = $this->createRegistryWithMockProvider();

        $result = AiClient::generateTextResult($messagePart, $mockModel, $registry);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateTextResult with array of Messages.
     */
    public function testGenerateTextResultWithMessageArray(): void
    {
        $messagePart1 = new MessagePart('First message');
        $messagePart2 = new MessagePart('Second message');
        $message1 = new UserMessage([$messagePart1]);
        $message2 = new UserMessage([$messagePart2]);
        $messages = [$message1, $message2];

        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);
        $registry = $this->createRegistryWithMockProvider();

        $result = AiClient::generateTextResult($messages, $mockModel, $registry);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateTextResult with array of MessageParts.
     */
    public function testGenerateTextResultWithMessagePartArray(): void
    {
        $messagePart1 = new MessagePart('First part');
        $messagePart2 = new MessagePart('Second part');
        $messageParts = [$messagePart1, $messagePart2];

        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);
        $registry = $this->createRegistryWithMockProvider();

        $result = AiClient::generateTextResult($messageParts, $mockModel, $registry);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests isConfigured method returns true when provider availability is configured.
     */
    public function testIsConfiguredReturnsTrueWhenProviderIsConfigured(): void
    {
        $mockAvailability = $this->createMock(ProviderAvailabilityInterface::class);
        $mockAvailability->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        $result = AiClient::isConfigured($mockAvailability);

        $this->assertTrue($result);
    }

    /**
     * Tests isConfigured method returns false when provider availability is not configured.
     */
    public function testIsConfiguredReturnsFalseWhenProviderIsNotConfigured(): void
    {
        $mockAvailability = $this->createMock(ProviderAvailabilityInterface::class);
        $mockAvailability->expects($this->once())
            ->method('isConfigured')
            ->willReturn(false);

        $result = AiClient::isConfigured($mockAvailability);

        $this->assertFalse($result);
    }

    /**
     * Tests isConfigured method with provider ID string leverages registry.
     */
    public function testIsConfiguredWithProviderIdString(): void
    {
        $mockRegistry = $this->createMock(ProviderRegistry::class);
        $mockRegistry->expects($this->once())
            ->method('isProviderConfigured')
            ->with('openai')
            ->willReturn(true);

        $result = AiClient::isConfigured('openai', $mockRegistry);

        $this->assertTrue($result);
    }

    /**
     * Tests isConfigured method with provider class name leverages registry.
     */
    public function testIsConfiguredWithProviderClassName(): void
    {
        $mockRegistry = $this->createMock(ProviderRegistry::class);
        $mockRegistry->expects($this->once())
            ->method('isProviderConfigured')
            ->with(OpenAiProvider::class)
            ->willReturn(false);

        $result = AiClient::isConfigured(OpenAiProvider::class, $mockRegistry);

        $this->assertFalse($result);
    }

    /**
     * Tests isConfigured method with provider ID uses default registry when none provided.
     */
    public function testIsConfiguredWithProviderIdUsesDefaultRegistry(): void
    {
        // This test will use the actual default registry since we can't easily mock static methods
        // The default registry should have providers registered, so we test the delegation path
        $result = AiClient::isConfigured('openai');

        // The result will be false because no actual API keys are configured in tests,
        // but the important thing is that no exception is thrown and the registry delegation works
        $this->assertIsBool($result);
    }

    /**
     * Tests isConfigured method with provider class name uses default registry when none provided.
     */
    public function testIsConfiguredWithProviderClassNameUsesDefaultRegistry(): void
    {
        // This test will use the actual default registry since we can't easily mock static methods
        // The default registry should have providers registered, so we test the delegation path
        $result = AiClient::isConfigured(OpenAiProvider::class);

        // The result will be false because no actual API keys are configured in tests,
        // but the important thing is that no exception is thrown and the registry delegation works
        $this->assertIsBool($result);
    }

    /**
     * Tests isConfigured method throws exception for invalid parameter types.
     */
    public function testIsConfiguredThrowsExceptionForInvalidParameterTypes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Parameter must be a ProviderAvailabilityInterface instance, provider ID string, or provider class name. ' .
            'Received: integer'
        );

        AiClient::isConfigured(123);
    }

    /**
     * Data provider for invalid isConfigured parameter types.
     *
     * @return array<string, array{mixed, string}>
     */
    public function invalidIsConfiguredParameterTypesProvider(): array
    {
        return [
            'integer parameter' => [123, 'integer'],
            'array parameter' => [['invalid_array'], 'array'],
            'object parameter' => [new \stdClass(), 'stdClass'],
            'boolean parameter' => [true, 'boolean'],
            'null parameter' => [null, 'NULL'],
        ];
    }

    /**
     * Tests that isConfigured rejects all invalid parameter types consistently.
     *
     * @dataProvider invalidIsConfiguredParameterTypesProvider
     * @param mixed $invalidParam
     */
    public function testIsConfiguredRejectsInvalidParameterTypes($invalidParam, string $expectedType): void
    {
        try {
            AiClient::isConfigured($invalidParam);
            $this->fail("Expected InvalidArgumentException for isConfigured with $expectedType");
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString(
                'Parameter must be a ProviderAvailabilityInterface instance, provider ID string, or provider class name.',
                $e->getMessage(),
                "isConfigured should reject invalid parameter type: $expectedType"
            );
            $this->assertStringContainsString(
                "Received: $expectedType",
                $e->getMessage(),
                "isConfigured should include received type in error message"
            );
        }
    }

    /**
     * Tests backward compatibility - isConfigured still works with ProviderAvailabilityInterface.
     */
    public function testIsConfiguredBackwardCompatibility(): void
    {
        // Test that the original interface-based approach still works exactly as before
        $mockAvailability = $this->createMock(ProviderAvailabilityInterface::class);
        $mockAvailability->expects($this->once())
            ->method('isConfigured')
            ->willReturn(true);

        // Should work without registry parameter
        $result = AiClient::isConfigured($mockAvailability);
        $this->assertTrue($result);

        // Should work with registry parameter (but registry should be ignored for interface input)
        $mockRegistry = $this->createMock(ProviderRegistry::class);
        $mockRegistry->expects($this->never())
            ->method('isProviderConfigured'); // Registry should not be called for interface input

        $mockAvailability2 = $this->createMock(ProviderAvailabilityInterface::class);
        $mockAvailability2->expects($this->once())
            ->method('isConfigured')
            ->willReturn(false);

        $result2 = AiClient::isConfigured($mockAvailability2, $mockRegistry);
        $this->assertFalse($result2);
    }

    /**
     * Tests generateResult delegates to generateTextResult when model supports text generation.
     */
    public function testGenerateResultDelegatesToTextGeneration(): void
    {
        $prompt = 'Test prompt';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);
        $registry = $this->createRegistryWithMockProvider();

        $result = AiClient::generateResult($prompt, $mockModel, $registry);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateResult delegates to generateImageResult when model supports image generation.
     */
    public function testGenerateResultDelegatesToImageGeneration(): void
    {
        $prompt = 'Generate image prompt';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockImageGenerationModel($expectedResult);
        $registry = $this->createRegistryWithMockProvider();

        $result = AiClient::generateResult($prompt, $mockModel, $registry);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateResult with null model delegates to PromptBuilder.
     */
    public function testGenerateResultWithNullModelDelegatesToPromptBuilder(): void
    {
        $prompt = 'Test prompt for auto-discovery';

        // This should delegate to PromptBuilder's intelligent discovery
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No models found that support the required capabilities');

        AiClient::generateResult($prompt, null, $this->createMockEmptyRegistry());
    }

    /**
     * Tests generateResult with text generation model.
     */
    public function testGenerateResultWithTextGenerationModel(): void
    {
        $prompt = 'Generate text content';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockTextGenerationModel($expectedResult);
        $registry = $this->createRegistryWithMockProvider();

        $result = AiClient::generateResult($prompt, $mockModel, $registry);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests generateResult with image generation model.
     */
    public function testGenerateResultWithImageGenerationModel(): void
    {
        $prompt = 'Generate an image';
        $expectedResult = $this->createTestResult();
        $mockModel = $this->createMockImageGenerationModel($expectedResult);
        $registry = $this->createRegistryWithMockProvider();

        $result = AiClient::generateResult($prompt, $mockModel, $registry);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * Tests that generateResult accepts ModelConfig and delegates to PromptBuilder.
     */
    public function testGenerateResultWithModelConfigDelegatesToPromptBuilder(): void
    {
        $prompt = 'Test prompt with config';
        $config = new ModelConfig();
        $config->setTemperature(0.8);
        $config->setMaxTokens(100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No models found that support the required capabilities');

        AiClient::generateResult($prompt, $config, $this->createMockEmptyRegistry());
    }

    /**
     * Tests that traditional API methods accept ModelConfig.
     */
    public function testTraditionalMethodsAcceptModelConfig(): void
    {
        $prompt = 'Test prompt';
        $config = new ModelConfig();
        $config->setTemperature(0.5);

        // Test all traditional methods accept ModelConfig
        $methods = [
            'generateTextResult',
            'generateImageResult',
            'convertTextToSpeechResult',
            'generateSpeechResult'
        ];

        $mockRegistry = $this->createMockEmptyRegistry();

        foreach ($methods as $method) {
            try {
                AiClient::$method($prompt, $config, $mockRegistry);
                $this->fail("Expected InvalidArgumentException for $method");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('No models found that support', $e->getMessage());
            }
        }
    }

    /**
     * Tests that invalid parameter types are rejected with proper error message.
     */
    public function testInvalidParameterTypeThrowsException(): void
    {
        $prompt = 'Test prompt';
        $invalidParam = 'invalid_string_parameter';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Parameter must be a ModelInterface instance \(specific model\)/');
        $this->expectExceptionMessageMatches('/Received: string/');

        AiClient::generateResult($prompt, $invalidParam);
    }

    /**
     * Data provider for invalid parameter types.
     *
     * @return array<string, array{mixed, string}>
     */
    public function invalidParameterTypesProvider(): array
    {
        return [
            'string parameter' => ['invalid_string', 'string'],
            'integer parameter' => [123, 'integer'],
            'array parameter' => [['invalid_array'], 'array'],
            'object parameter' => [new \stdClass(), 'stdClass'],
            'boolean parameter' => [true, 'boolean'],
        ];
    }

    /**
     * Data provider for AiClient methods that accept model/config parameters.
     *
     * @return array<string, array{string}>
     */
    public function aiClientMethodsProvider(): array
    {
        return [
            'generateResult' => ['generateResult'],
            'generateTextResult' => ['generateTextResult'],
            'generateImageResult' => ['generateImageResult'],
            'convertTextToSpeechResult' => ['convertTextToSpeechResult'],
            'generateSpeechResult' => ['generateSpeechResult'],
        ];
    }

    /**
     * Tests that all methods reject invalid parameter types consistently.
     *
     * @dataProvider invalidParameterTypesProvider
     * @param mixed $invalidParam
     */
    public function testAllMethodsRejectInvalidParameterTypes($invalidParam, string $expectedType): void
    {
        $prompt = 'Test prompt';
        $methods = $this->aiClientMethodsProvider();

        foreach ($methods as [$method]) {
            try {
                AiClient::$method($prompt, $invalidParam);
                $this->fail("Expected InvalidArgumentException for $method with $expectedType");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'Parameter must be a ModelInterface instance (specific model)',
                    $e->getMessage(),
                    "Method $method should reject invalid parameter type: $expectedType"
                );
                $this->assertStringContainsString(
                    "Received: $expectedType",
                    $e->getMessage(),
                    "Method $method should include received type in error message"
                );
            }
        }
    }

    /**
     * Tests that all methods accept null parameter (default auto-discovery).
     *
     * @dataProvider aiClientMethodsProvider
     */
    public function testAllMethodsAcceptNullParameter(string $method): void
    {
        $prompt = 'Test prompt for null parameter';

        try {
            AiClient::$method($prompt, null, $this->createMockEmptyRegistry());
            $this->fail("Expected InvalidArgumentException for $method with null (no providers)");
        } catch (\InvalidArgumentException $e) {
            // Should delegate to PromptBuilder and fail due to no providers
            $this->assertStringContainsString(
                'No models found that support',
                $e->getMessage(),
                "Method $method should accept null and delegate to PromptBuilder"
            );
        }
    }

    /**
     * Tests ModelConfig with various parameter combinations.
     */
    public function testModelConfigWithVariousParameters(): void
    {
        // Test different ModelConfig configurations
        $mockRegistry = $this->createMockEmptyRegistry();
        $configurations = [
            // Basic temperature setting
            function () {
                $config = new ModelConfig();
                $config->setTemperature(0.7);
                return $config;
            },
            // Max tokens setting
            function () {
                $config = new ModelConfig();
                $config->setMaxTokens(500);
                return $config;
            },
            // Combined settings
            function () {
                $config = new ModelConfig();
                $config->setTemperature(0.5);
                $config->setMaxTokens(200);
                return $config;
            },
        ];

        $prompt = 'Test prompt with various configs';

        foreach ($configurations as $index => $configFunction) {
            $config = $configFunction();

            try {
                AiClient::generateResult($prompt, $config, $mockRegistry);
                $this->fail("Expected InvalidArgumentException for configuration $index");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'No models found that support the required capabilities',
                    $e->getMessage(),
                    "Configuration $index should delegate to PromptBuilder properly"
                );
            }
        }
    }

    /**
     * Tests empty ModelConfig parameter.
     */
    public function testEmptyModelConfig(): void
    {
        $prompt = 'Test with empty config';
        $emptyConfig = new ModelConfig();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No models found that support the required capabilities');

        AiClient::generateResult($prompt, $emptyConfig, $this->createMockEmptyRegistry());
    }

    /**
     * Tests that ModelConfig is properly passed to PromptBuilder methods.
     */
    public function testModelConfigPassedToAllMethods(): void
    {
        $prompt = 'Test prompt';
        $config = new ModelConfig();
        $config->setTemperature(0.8);

        $methods = [
            'generateResult',
            'generateTextResult',
            'generateImageResult',
            'convertTextToSpeechResult',
            'generateSpeechResult'
        ];

        $mockRegistry = $this->createMockEmptyRegistry();

        foreach ($methods as $method) {
            try {
                AiClient::$method($prompt, $config, $mockRegistry);
                $this->fail("Expected InvalidArgumentException for $method with ModelConfig");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'No models found that support',
                    $e->getMessage(),
                    "Method $method should accept ModelConfig and delegate to PromptBuilder"
                );
            }
        }
    }

    /**
     * Tests validateModelOrConfigParameter helper method via reflection.
     */
    public function testValidateModelOrConfigParameterHelper(): void
    {
        $reflection = new \ReflectionClass(AiClient::class);
        $method = $reflection->getMethod('validateModelOrConfigParameter');
        $method->setAccessible(true);

        // Test valid parameters (should not throw)
        $validParams = [
            null,
            $this->createMockTextGenerationModel($this->createTestResult()),
            new ModelConfig(),
        ];

        foreach ($validParams as $param) {
            // Valid parameters should not throw exceptions
            $method->invoke(null, $param);
            // If we reach here, no exception was thrown (which is what we expect)
            $this->assertTrue(true, 'Valid parameter should not throw exception');
        }

        // Test invalid parameters (should throw)
        $invalidParams = [
            'string',
            123,
            [],
            new \stdClass(),
            true,
        ];

        foreach ($invalidParams as $param) {
            try {
                $method->invoke(null, $param);
                $this->fail('Invalid parameter should throw exception');
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString(
                    'Parameter must be a ModelInterface instance (specific model)',
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * Tests that validation helper is properly integrated in public methods.
     */
    public function testValidationHelperIntegration(): void
    {
        $prompt = 'Integration test prompt';

        // Test that validation is called for invalid parameters
        try {
            $invalidParam = 'invalid';
            AiClient::generateResult($prompt, $invalidParam);
            $this->fail('Should have thrown InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Parameter must be a ModelInterface', $e->getMessage());
        }
    }

    /**
     * Tests that getConfiguredPromptBuilder helper is properly integrated.
     */
    public function testGetConfiguredPromptBuilderHelperIntegration(): void
    {
        $prompt = 'Integration test prompt';

        // Test that getConfiguredPromptBuilder is called with null
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/No models found that support/');
        AiClient::generateResult($prompt, null, $this->createMockEmptyRegistry());
    }
}

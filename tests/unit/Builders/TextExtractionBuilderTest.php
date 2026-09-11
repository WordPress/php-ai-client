<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use WordPress\AiClient\Builders\TextExtractionBuilder;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Events\AfterExtractTextEvent;
use WordPress\AiClient\Events\BeforeExtractTextEvent;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\DTO\ProviderModelsMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Builders\TextExtractionBuilder
 */
class TextExtractionBuilderTest extends TestCase
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
     * Reads the configured document from a builder.
     *
     * @param TextExtractionBuilder $builder The builder to inspect.
     * @return File|null The configured document.
     */
    private function getDocument(TextExtractionBuilder $builder): ?File
    {
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('document');
        $property->setAccessible(true);

        /** @var File|null $document */
        $document = $property->getValue($builder);

        return $document;
    }

    /**
     * Reads the model configuration from a builder.
     *
     * @param TextExtractionBuilder $builder The builder to inspect.
     * @return ModelConfig The model configuration.
     */
    private function getModelConfig(TextExtractionBuilder $builder): ModelConfig
    {
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty('modelConfig');
        $property->setAccessible(true);

        /** @var ModelConfig $config */
        $config = $property->getValue($builder);

        return $config;
    }

    /**
     * Tests the constructor accepts an initial document string.
     *
     * @return void
     */
    public function testConstructorWithDocumentString(): void
    {
        $builder = new TextExtractionBuilder($this->registry, 'https://example.com/document.pdf');

        $document = $this->getDocument($builder);
        $this->assertInstanceOf(File::class, $document);
        $this->assertSame('application/pdf', $document->getMimeType());
    }

    /**
     * Tests the constructor without a document leaves it unset.
     *
     * @return void
     */
    public function testConstructorWithoutDocument(): void
    {
        $builder = new TextExtractionBuilder($this->registry);

        $this->assertNull($this->getDocument($builder));
    }

    /**
     * Tests withDocument accepts a File instance.
     *
     * @return void
     */
    public function testWithDocumentAcceptsFile(): void
    {
        $file = new File('https://example.com/scan.png', 'image/png');

        $builder = new TextExtractionBuilder($this->registry);
        $builder->withDocument($file);

        $this->assertSame($file, $this->getDocument($builder));
    }

    /**
     * Tests withDocument uses the explicit MIME type for a string input.
     *
     * @return void
     */
    public function testWithDocumentUsesExplicitMimeType(): void
    {
        $builder = new TextExtractionBuilder($this->registry);
        $builder->withDocument('https://arxiv.org/pdf/1805.04770', 'application/pdf');

        $document = $this->getDocument($builder);
        $this->assertInstanceOf(File::class, $document);
        $this->assertSame('application/pdf', $document->getMimeType());
    }

    /**
     * Tests withDocument rejects an empty string.
     *
     * @return void
     */
    public function testWithDocumentRejectsEmptyString(): void
    {
        $builder = new TextExtractionBuilder($this->registry);

        // Validation is left to File, so that all builders reject the same inputs identically.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file provided.');

        $builder->withDocument('   ');
    }

    /**
     * Tests withDocument rejects file types text extraction cannot consume.
     *
     * @return void
     */
    public function testWithDocumentRejectsUnsupportedMimeType(): void
    {
        $builder = new TextExtractionBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Text extraction supports image and document files, got "audio/mpeg".');

        $builder->withDocument('https://example.com/podcast.mp3');
    }

    /**
     * Tests withDocument accepts image and document files.
     *
     * @return void
     */
    public function testWithDocumentAcceptsImagesAndDocuments(): void
    {
        $builder = new TextExtractionBuilder($this->registry);

        $builder->withDocument('https://example.com/scan.png');
        $this->assertSame('image/png', $this->getDocument($builder)->getMimeType());

        $builder->withDocument('https://example.com/report.pdf');
        $this->assertSame('application/pdf', $this->getDocument($builder)->getMimeType());
    }

    /**
     * Tests withDocument rejects an unsupported input type.
     *
     * @return void
     */
    public function testWithDocumentRejectsUnsupportedType(): void
    {
        $builder = new TextExtractionBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Document must be a File instance or a string.');

        /** @phpstan-ignore-next-line Intentionally passing an invalid type. */
        $builder->withDocument(123);
    }

    /**
     * Tests extractTextResult returns the model result.
     *
     * @return void
     */
    public function testExtractTextResultWithModel(): void
    {
        $result = $this->createTestTextExtractionResult(['# Page one']);
        $model = $this->createMockTextExtractionModel($result);

        $builder = new TextExtractionBuilder($this->registry, 'https://example.com/document.pdf');
        $builder->usingModel($model);

        $this->assertSame($result, $builder->extractTextResult());
    }

    /**
     * Tests extractText joins the extracted pages into a markdown string.
     *
     * @return void
     */
    public function testExtractTextReturnsMarkdown(): void
    {
        $result = $this->createTestTextExtractionResult(['# Page one', 'Page two.']);
        $model = $this->createMockTextExtractionModel($result);

        $builder = new TextExtractionBuilder($this->registry, 'https://example.com/document.pdf');
        $builder->usingModel($model);

        $this->assertSame($result->toMarkdown(), $builder->extractText());
        $this->assertStringContainsString('# Page one', $builder->extractText());
        $this->assertStringContainsString('Page two.', $builder->extractText());
    }

    /**
     * Tests extractTextResult throws when no document is configured.
     *
     * @return void
     */
    public function testExtractTextResultThrowsWithoutDocument(): void
    {
        $builder = new TextExtractionBuilder($this->registry);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot extract text without a document. Add one using withDocument().');

        $builder->extractTextResult();
    }

    /**
     * Tests extractTextResult throws for a model that does not support text extraction.
     *
     * @return void
     */
    public function testExtractTextResultThrowsForUnsupportedModel(): void
    {
        $metadata = $this->createMock(ModelMetadata::class);
        $metadata->method('getId')->willReturn('test-model');

        $model = $this->createMock(ModelInterface::class);
        $model->method('metadata')->willReturn($metadata);

        $builder = new TextExtractionBuilder($this->registry, 'https://example.com/document.pdf');
        $builder->usingModel($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "test-model" does not support text extraction.');

        $builder->extractTextResult();
    }

    /**
     * Tests model selection requires the text extraction capability and document input modality.
     *
     * @return void
     */
    public function testModelSelectionUsesDocumentModality(): void
    {
        $result = $this->createTestTextExtractionResult();
        $modelMetadata = $this->createTestTextExtractionModelMetadata('ocr-model');
        $model = $this->createMockTextExtractionModel($result, $modelMetadata);
        $providerMetadata = new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud());

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->with($this->callback(static function (ModelRequirements $requirements): bool {
                $hasCapability = false;
                foreach ($requirements->getRequiredCapabilities() as $capability) {
                    if ($capability->isTextExtraction()) {
                        $hasCapability = true;
                    }
                }

                foreach ($requirements->getRequiredOptions() as $requiredOption) {
                    if ($requiredOption->getName()->isInputModalities()) {
                        return $hasCapability && [ModalityEnum::document()] == $requiredOption->getValue();
                    }
                }

                return false;
            }))
            ->willReturn([new ProviderModelsMetadata($providerMetadata, [$modelMetadata])]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('mock', 'ocr-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $builder = new TextExtractionBuilder($this->registry, 'https://example.com/document.pdf');

        $this->assertSame($result, $builder->extractTextResult());
    }

    /**
     * Tests model selection uses the image input modality for image documents.
     *
     * @return void
     */
    public function testModelSelectionUsesImageModalityForImages(): void
    {
        $result = $this->createTestTextExtractionResult();
        $modelMetadata = $this->createTestTextExtractionModelMetadata('ocr-model');
        $model = $this->createMockTextExtractionModel($result, $modelMetadata);
        $providerMetadata = new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud());

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->with($this->callback(static function (ModelRequirements $requirements): bool {
                foreach ($requirements->getRequiredOptions() as $requiredOption) {
                    if ($requiredOption->getName()->isInputModalities()) {
                        return [ModalityEnum::image()] == $requiredOption->getValue();
                    }
                }

                return false;
            }))
            ->willReturn([new ProviderModelsMetadata($providerMetadata, [$modelMetadata])]);

        $this->registry->method('getProviderModel')->willReturn($model);

        $builder = new TextExtractionBuilder($this->registry, 'https://example.com/scan.png');

        $this->assertSame($result, $builder->extractTextResult());
    }

    /**
     * Tests isSupported returns false when no document is configured.
     *
     * @return void
     */
    public function testIsSupportedReturnsFalseWithoutDocument(): void
    {
        $builder = new TextExtractionBuilder($this->registry);

        $this->assertFalse($builder->isSupported());
    }

    /**
     * Tests isSupported returns false when no suitable models exist.
     *
     * @return void
     */
    public function testIsSupportedReturnsFalseWhenNoModels(): void
    {
        $this->registry->method('findModelsMetadataForSupport')->willReturn([]);

        $builder = new TextExtractionBuilder($this->registry, 'https://example.com/document.pdf');

        $this->assertFalse($builder->isSupported());
    }

    /**
     * Tests isSupported returns true when a suitable model exists.
     *
     * @return void
     */
    public function testIsSupportedReturnsTrueWhenModelAvailable(): void
    {
        $modelMetadata = $this->createTestTextExtractionModelMetadata();
        $providerMetadata = new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud());

        $this->registry->method('findModelsMetadataForSupport')
            ->willReturn([new ProviderModelsMetadata($providerMetadata, [$modelMetadata])]);

        $builder = new TextExtractionBuilder($this->registry, 'https://example.com/document.pdf');

        $this->assertTrue($builder->isSupported());
    }

    /**
     * Tests cloning deep-copies the document and configuration.
     *
     * @return void
     */
    public function testCloneDeepCopiesDocumentAndConfig(): void
    {
        $original = new TextExtractionBuilder($this->registry, 'https://example.com/document.pdf');

        $cloned = clone $original;

        $this->assertNotSame($this->getDocument($original), $this->getDocument($cloned));
        $this->assertNotSame($this->getModelConfig($original), $this->getModelConfig($cloned));
    }

    /**
     * Tests cloning a builder without a document.
     *
     * @return void
     */
    public function testCloneWithoutDocument(): void
    {
        $original = new TextExtractionBuilder($this->registry);

        $cloned = clone $original;

        $this->assertNull($this->getDocument($cloned));
        $this->assertNotSame($this->getModelConfig($original), $this->getModelConfig($cloned));
    }

    /**
     * Tests the builder dispatches lifecycle events around extraction.
     *
     * @return void
     */
    public function testExtractTextResultDispatchesLifecycleEvents(): void
    {
        $result = $this->createTestTextExtractionResult(['# Page one']);
        $model = $this->createMockTextExtractionModel($result);

        $events = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$events): object {
                $events[] = $event;
                return $event;
            });

        $builder = new TextExtractionBuilder(
            $this->registry,
            'https://example.com/document.pdf',
            $dispatcher
        );
        $builder->usingModel($model);
        $builder->extractTextResult();

        $this->assertInstanceOf(BeforeExtractTextEvent::class, $events[0]);
        $this->assertInstanceOf(AfterExtractTextEvent::class, $events[1]);

        $this->assertSame('application/pdf', $events[0]->getDocument()->getMimeType());
        $this->assertSame($model, $events[0]->getModel());
        $this->assertTrue($events[0]->getCapability()->isTextExtraction());

        $this->assertSame($result, $events[1]->getResult());
        $this->assertTrue($events[1]->getCapability()->isTextExtraction());
    }

    /**
     * Tests the builder works without an event dispatcher.
     *
     * @return void
     */
    public function testExtractTextResultWorksWithoutEventDispatcher(): void
    {
        $result = $this->createTestTextExtractionResult(['# Page one']);
        $model = $this->createMockTextExtractionModel($result);

        $builder = new TextExtractionBuilder($this->registry, 'https://example.com/document.pdf');
        $builder->usingModel($model);

        $this->assertSame($result, $builder->extractTextResult());
    }
}

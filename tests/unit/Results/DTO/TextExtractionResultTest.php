<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\BoundingBox;
use WordPress\AiClient\Results\DTO\ExtractedImage;
use WordPress\AiClient\Results\DTO\ExtractedPage;
use WordPress\AiClient\Results\DTO\PageDimensions;
use WordPress\AiClient\Results\DTO\TextExtractionResult;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * @covers \WordPress\AiClient\Results\DTO\TextExtractionResult
 * @covers \WordPress\AiClient\Results\DTO\ExtractedPage
 * @covers \WordPress\AiClient\Results\DTO\ExtractedImage
 * @covers \WordPress\AiClient\Results\DTO\BoundingBox
 * @covers \WordPress\AiClient\Results\DTO\PageDimensions
 */
class TextExtractionResultTest extends TestCase
{
    private function createTextExtractionResult(): TextExtractionResult
    {
        $image = new ExtractedImage(
            'img-0.jpeg',
            new File(base64_encode('fake-image-data'), 'image/jpeg'),
            new BoundingBox(0.17, 0.1, 0.65, 0.2)
        );

        $pages = [
            new ExtractedPage(1, "# Heading\n\nFirst page.", [$image], new PageDimensions(1700, 2200, 200)),
            new ExtractedPage(2, 'Second page.'),
        ];

        return new TextExtractionResult(
            'extraction-result-id',
            $pages,
            new TokenUsage(0, 0, 0),
            new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud()),
            new ModelMetadata(
                'mock-ocr-model',
                'Mock OCR Model',
                [CapabilityEnum::textExtraction()],
                []
            ),
            ['raw' => ['usage_info' => ['pages_processed' => 2]]]
        );
    }

    public function testGetters(): void
    {
        $result = $this->createTextExtractionResult();

        $this->assertSame('extraction-result-id', $result->getId());
        $this->assertContainsOnlyInstancesOf(ExtractedPage::class, $result->getPages());
        $this->assertSame(2, $result->getPageCount());
        $this->assertSame(0, $result->getTokenUsage()->getTotalTokens());
        $this->assertSame('mock', $result->getProviderMetadata()->getId());
        $this->assertSame('mock-ocr-model', $result->getModelMetadata()->getId());
        $this->assertArrayHasKey('raw', $result->getAdditionalData());
    }

    public function testPageGetters(): void
    {
        $page = $this->createTextExtractionResult()->getPages()[0];

        $this->assertSame(1, $page->getPageNumber());
        $this->assertSame("# Heading\n\nFirst page.", $page->getMarkdown());
        $this->assertCount(1, $page->getImages());
        $this->assertNotNull($page->getDimensions());
        $this->assertSame(1700, $page->getDimensions()->getWidth());
        $this->assertSame(2200, $page->getDimensions()->getHeight());
        $this->assertSame(200, $page->getDimensions()->getDpi());

        $image = $page->getImages()[0];
        $this->assertSame('img-0.jpeg', $image->getId());
        $this->assertNotNull($image->getFile());
        $this->assertNotNull($image->getBoundingBox());
        $this->assertSame(0.17, $image->getBoundingBox()->getLeft());
        $this->assertSame(0.1, $image->getBoundingBox()->getTop());
        $this->assertSame(0.65, $image->getBoundingBox()->getWidth());
        $this->assertSame(0.2, $image->getBoundingBox()->getHeight());
    }

    public function testToMarkdownJoinsPagesInOrder(): void
    {
        $result = $this->createTextExtractionResult();

        $this->assertSame("# Heading\n\nFirst page.\n\nSecond page.", $result->toMarkdown());
        $this->assertSame($result->toMarkdown(), $result->toText());
    }

    public function testToArrayFromArrayRoundtrip(): void
    {
        $result = $this->createTextExtractionResult();

        $roundtripped = TextExtractionResult::fromArray($result->toArray());

        $this->assertEquals($result->toArray(), $roundtripped->toArray());
    }

    public function testConstructorRejectsEmptyPages(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TextExtractionResult(
            'id',
            [],
            new TokenUsage(0, 0, 0),
            new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud()),
            new ModelMetadata('mock-ocr-model', 'Mock OCR Model', [], [])
        );
    }

    public function testConstructorRejectsNonExtractedPageEntries(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All pages must be ExtractedPage instances.');

        new TextExtractionResult(
            'id',
            /** @phpstan-ignore-next-line Intentionally passing invalid page entries. */
            ['not a page'],
            new TokenUsage(0, 0, 0),
            new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud()),
            new ModelMetadata('mock-ocr-model', 'Mock OCR Model', [], [])
        );
    }

    public function testPageDimensionsRejectsNonPositiveValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page dimensions must be positive integers.');

        new PageDimensions(0, 2200);
    }

    public function testExtractedPageRejectsZeroPageNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ExtractedPage(0, 'content');
    }

    public function testBoundingBoxRejectsOutOfRangeValues(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BoundingBox(0.0, 0.0, 1.5, 0.5);
    }

    public function testDeepClone(): void
    {
        $result = $this->createTextExtractionResult();
        $clone = clone $result;

        $this->assertEquals($result->toArray(), $clone->toArray());
        $this->assertNotSame($result->getPages()[0], $clone->getPages()[0]);
        $this->assertNotSame(
            $result->getPages()[0]->getImages()[0],
            $clone->getPages()[0]->getImages()[0]
        );
    }

    public function testJsonSchemaListsRequiredKeys(): void
    {
        $schema = TextExtractionResult::getJsonSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertContains(TextExtractionResult::KEY_ID, $schema['required']);
        $this->assertContains(TextExtractionResult::KEY_PAGES, $schema['required']);
    }
}

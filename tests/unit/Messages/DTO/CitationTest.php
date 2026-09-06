<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Messages\DTO\Citation;

/**
 * @covers \WordPress\AiClient\Messages\DTO\Citation
 */
class CitationTest extends TestCase
{
    /**
     * Tests creating Citation with URL only.
     *
     * @return void
     */
    public function testCreateWithUrlOnly(): void
    {
        $citation = new Citation('https://example.com/doc');

        $this->assertEquals('https://example.com/doc', $citation->getUrl());
        $this->assertNull($citation->getDocumentIndex());
        $this->assertNull($citation->getTitle());
        $this->assertNull($citation->getStartIndex());
        $this->assertNull($citation->getEndIndex());
        $this->assertNull($citation->getQuotedText());
    }

    /**
     * Tests creating Citation with document index only.
     *
     * @return void
     */
    public function testCreateWithDocumentIndexOnly(): void
    {
        $citation = new Citation(null, 2);

        $this->assertNull($citation->getUrl());
        $this->assertEquals(2, $citation->getDocumentIndex());
        $this->assertNull($citation->getTitle());
    }

    /**
     * Tests creating Citation with all fields populated.
     *
     * @return void
     */
    public function testCreateWithAllFields(): void
    {
        $citation = new Citation(
            'https://example.com/doc',
            null,
            'Example Document',
            10,
            50,
            'The quoted passage from the source.'
        );

        $this->assertEquals('https://example.com/doc', $citation->getUrl());
        $this->assertNull($citation->getDocumentIndex());
        $this->assertEquals('Example Document', $citation->getTitle());
        $this->assertEquals(10, $citation->getStartIndex());
        $this->assertEquals(50, $citation->getEndIndex());
        $this->assertEquals('The quoted passage from the source.', $citation->getQuotedText());
    }

    /**
     * Tests creating Citation with document index and span.
     *
     * @return void
     */
    public function testCreateWithDocumentIndexAndSpan(): void
    {
        $citation = new Citation(null, 0, 'Report', 5, 20);

        $this->assertNull($citation->getUrl());
        $this->assertEquals(0, $citation->getDocumentIndex());
        $this->assertEquals('Report', $citation->getTitle());
        $this->assertEquals(5, $citation->getStartIndex());
        $this->assertEquals(20, $citation->getEndIndex());
        $this->assertNull($citation->getQuotedText());
    }

    /**
     * Tests creating Citation with equal start and end index.
     *
     * @return void
     */
    public function testCreateWithEqualStartAndEndIndex(): void
    {
        $citation = new Citation('https://example.com', null, null, 10, 10);

        $this->assertEquals(10, $citation->getStartIndex());
        $this->assertEquals(10, $citation->getEndIndex());
    }

    /**
     * Tests creating Citation with zero start and end index.
     *
     * @return void
     */
    public function testCreateWithZeroIndices(): void
    {
        $citation = new Citation('https://example.com', null, null, 0, 0);

        $this->assertEquals(0, $citation->getStartIndex());
        $this->assertEquals(0, $citation->getEndIndex());
    }

    /**
     * Tests that negative documentIndex throws exception.
     *
     * @return void
     */
    public function testNegativeDocumentIndexThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Citation documentIndex must be non-negative, got -1.');

        new Citation(null, -1);
    }

    /**
     * Tests that negative startIndex throws exception.
     *
     * @return void
     */
    public function testNegativeStartIndexThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Citation startIndex must be non-negative, got -1.');

        new Citation('https://example.com', null, null, -1, 10);
    }

    /**
     * Tests that negative endIndex throws exception.
     *
     * @return void
     */
    public function testNegativeEndIndexThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Citation endIndex must be non-negative, got -5.');

        new Citation('https://example.com', null, null, 0, -5);
    }

    /**
     * Tests that startIndex greater than endIndex throws exception.
     *
     * @return void
     */
    public function testStartIndexGreaterThanEndIndexThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Citation startIndex (20) must be less than or equal to endIndex (10).'
        );

        new Citation('https://example.com', null, null, 20, 10);
    }

    /**
     * Tests that startIndex without endIndex does not throw.
     *
     * @return void
     */
    public function testStartIndexWithoutEndIndex(): void
    {
        $citation = new Citation('https://example.com', null, null, 5, null);

        $this->assertEquals(5, $citation->getStartIndex());
        $this->assertNull($citation->getEndIndex());
    }

    /**
     * Tests that endIndex without startIndex does not throw.
     *
     * @return void
     */
    public function testEndIndexWithoutStartIndex(): void
    {
        $citation = new Citation('https://example.com', null, null, null, 20);

        $this->assertNull($citation->getStartIndex());
        $this->assertEquals(20, $citation->getEndIndex());
    }

    /**
     * Tests JSON schema has all expected properties.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = Citation::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey(Citation::KEY_URL, $schema['properties']);
        $this->assertArrayHasKey(Citation::KEY_DOCUMENT_INDEX, $schema['properties']);
        $this->assertArrayHasKey(Citation::KEY_TITLE, $schema['properties']);
        $this->assertArrayHasKey(Citation::KEY_START_INDEX, $schema['properties']);
        $this->assertArrayHasKey(Citation::KEY_END_INDEX, $schema['properties']);
        $this->assertArrayHasKey(Citation::KEY_QUOTED_TEXT, $schema['properties']);
        $this->assertFalse($schema['additionalProperties']);
        // No required fields — all are nullable.
        $this->assertArrayNotHasKey('required', $schema);
    }

    /**
     * Tests JSON schema index properties include minimum constraint.
     *
     * @return void
     */
    public function testJsonSchemaIndexMinimumConstraint(): void
    {
        $schema = Citation::getJsonSchema();

        $this->assertEquals(0, $schema['properties'][Citation::KEY_DOCUMENT_INDEX]['minimum']);
        $this->assertEquals(0, $schema['properties'][Citation::KEY_START_INDEX]['minimum']);
        $this->assertEquals(0, $schema['properties'][Citation::KEY_END_INDEX]['minimum']);
    }

    /**
     * Tests toArray with URL only.
     *
     * @return void
     */
    public function testToArrayWithUrlOnly(): void
    {
        $citation = new Citation('https://example.com/doc');
        $array = $citation->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey(Citation::KEY_URL, $array);
        $this->assertEquals('https://example.com/doc', $array[Citation::KEY_URL]);
        $this->assertArrayNotHasKey(Citation::KEY_DOCUMENT_INDEX, $array);
        $this->assertArrayNotHasKey(Citation::KEY_TITLE, $array);
        $this->assertArrayNotHasKey(Citation::KEY_START_INDEX, $array);
        $this->assertArrayNotHasKey(Citation::KEY_END_INDEX, $array);
        $this->assertArrayNotHasKey(Citation::KEY_QUOTED_TEXT, $array);
    }

    /**
     * Tests toArray with document index only.
     *
     * @return void
     */
    public function testToArrayWithDocumentIndexOnly(): void
    {
        $citation = new Citation(null, 3);
        $array = $citation->toArray();

        $this->assertArrayNotHasKey(Citation::KEY_URL, $array);
        $this->assertArrayHasKey(Citation::KEY_DOCUMENT_INDEX, $array);
        $this->assertEquals(3, $array[Citation::KEY_DOCUMENT_INDEX]);
    }

    /**
     * Tests toArray with all fields populated.
     *
     * @return void
     */
    public function testToArrayWithAllFields(): void
    {
        $citation = new Citation(
            'https://example.com/doc',
            1,
            'Doc Title',
            10,
            50,
            'Quoted passage'
        );
        $array = $citation->toArray();

        $this->assertEquals('https://example.com/doc', $array[Citation::KEY_URL]);
        $this->assertEquals(1, $array[Citation::KEY_DOCUMENT_INDEX]);
        $this->assertEquals('Doc Title', $array[Citation::KEY_TITLE]);
        $this->assertEquals(10, $array[Citation::KEY_START_INDEX]);
        $this->assertEquals(50, $array[Citation::KEY_END_INDEX]);
        $this->assertEquals('Quoted passage', $array[Citation::KEY_QUOTED_TEXT]);
    }

    /**
     * Tests toArray omits null fields.
     *
     * @return void
     */
    public function testToArrayOmitsNullFields(): void
    {
        $citation = new Citation('https://example.com', null, 'Title');
        $array = $citation->toArray();

        $this->assertArrayHasKey(Citation::KEY_URL, $array);
        $this->assertArrayHasKey(Citation::KEY_TITLE, $array);
        $this->assertArrayNotHasKey(Citation::KEY_DOCUMENT_INDEX, $array);
        $this->assertArrayNotHasKey(Citation::KEY_START_INDEX, $array);
        $this->assertArrayNotHasKey(Citation::KEY_END_INDEX, $array);
        $this->assertArrayNotHasKey(Citation::KEY_QUOTED_TEXT, $array);
    }

    /**
     * Tests fromArray with URL only.
     *
     * @return void
     */
    public function testFromArrayWithUrlOnly(): void
    {
        $array = [
            Citation::KEY_URL => 'https://example.com/doc',
        ];

        $citation = Citation::fromArray($array);

        $this->assertEquals('https://example.com/doc', $citation->getUrl());
        $this->assertNull($citation->getDocumentIndex());
        $this->assertNull($citation->getTitle());
        $this->assertNull($citation->getStartIndex());
        $this->assertNull($citation->getEndIndex());
        $this->assertNull($citation->getQuotedText());
    }

    /**
     * Tests fromArray with document index only.
     *
     * @return void
     */
    public function testFromArrayWithDocumentIndexOnly(): void
    {
        $array = [
            Citation::KEY_DOCUMENT_INDEX => 2,
        ];

        $citation = Citation::fromArray($array);

        $this->assertNull($citation->getUrl());
        $this->assertEquals(2, $citation->getDocumentIndex());
    }

    /**
     * Tests fromArray with all fields.
     *
     * @return void
     */
    public function testFromArrayWithAllFields(): void
    {
        $array = [
            Citation::KEY_URL => 'https://example.com/doc',
            Citation::KEY_DOCUMENT_INDEX => 1,
            Citation::KEY_TITLE => 'Doc Title',
            Citation::KEY_START_INDEX => 10,
            Citation::KEY_END_INDEX => 50,
            Citation::KEY_QUOTED_TEXT => 'Quoted passage',
        ];

        $citation = Citation::fromArray($array);

        $this->assertEquals('https://example.com/doc', $citation->getUrl());
        $this->assertEquals(1, $citation->getDocumentIndex());
        $this->assertEquals('Doc Title', $citation->getTitle());
        $this->assertEquals(10, $citation->getStartIndex());
        $this->assertEquals(50, $citation->getEndIndex());
        $this->assertEquals('Quoted passage', $citation->getQuotedText());
    }

    /**
     * Tests fromArray with empty array creates all-null citation.
     *
     * @return void
     */
    public function testFromArrayWithEmptyArray(): void
    {
        $citation = Citation::fromArray([]);

        $this->assertNull($citation->getUrl());
        $this->assertNull($citation->getDocumentIndex());
        $this->assertNull($citation->getTitle());
        $this->assertNull($citation->getStartIndex());
        $this->assertNull($citation->getEndIndex());
        $this->assertNull($citation->getQuotedText());
    }

    /**
     * Tests round-trip array transformation with URL and span.
     *
     * @return void
     */
    public function testArrayRoundTripWithUrlAndSpan(): void
    {
        $original = new Citation('https://example.com/doc', null, 'Doc Title', 5, 25, 'some text');
        $array = $original->toArray();
        $restored = Citation::fromArray($array);

        $this->assertEquals($original->getUrl(), $restored->getUrl());
        $this->assertEquals($original->getDocumentIndex(), $restored->getDocumentIndex());
        $this->assertEquals($original->getTitle(), $restored->getTitle());
        $this->assertEquals($original->getStartIndex(), $restored->getStartIndex());
        $this->assertEquals($original->getEndIndex(), $restored->getEndIndex());
        $this->assertEquals($original->getQuotedText(), $restored->getQuotedText());
    }

    /**
     * Tests round-trip array transformation with document index.
     *
     * @return void
     */
    public function testArrayRoundTripWithDocumentIndex(): void
    {
        $original = new Citation(null, 4, 'Internal Doc', 0, 100);
        $array = $original->toArray();
        $restored = Citation::fromArray($array);

        $this->assertEquals($original->getUrl(), $restored->getUrl());
        $this->assertEquals($original->getDocumentIndex(), $restored->getDocumentIndex());
        $this->assertEquals($original->getTitle(), $restored->getTitle());
        $this->assertEquals($original->getStartIndex(), $restored->getStartIndex());
        $this->assertEquals($original->getEndIndex(), $restored->getEndIndex());
        $this->assertNull($restored->getQuotedText());
    }

    /**
     * Tests round-trip array transformation with minimal citation.
     *
     * @return void
     */
    public function testArrayRoundTripMinimal(): void
    {
        $original = new Citation('https://example.com');
        $array = $original->toArray();
        $restored = Citation::fromArray($array);

        $this->assertEquals($original->getUrl(), $restored->getUrl());
        $this->assertNull($restored->getDocumentIndex());
        $this->assertNull($restored->getTitle());
        $this->assertNull($restored->getStartIndex());
        $this->assertNull($restored->getEndIndex());
        $this->assertNull($restored->getQuotedText());
    }
}

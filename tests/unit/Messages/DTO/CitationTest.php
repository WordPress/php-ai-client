<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\Citation;

/**
 * @covers \WordPress\AiClient\Messages\DTO\Citation
 */
class CitationTest extends TestCase
{
    /**
     * Tests creating Citation with URI only.
     *
     * @return void
     */
    public function testCreateWithUriOnly(): void
    {
        $uri = 'https://example.com/doc';
        $citation = new Citation($uri);

        $this->assertEquals($uri, $citation->getUri());
        $this->assertNull($citation->getTitle());
    }

    /**
     * Tests creating Citation with URI and title.
     *
     * @return void
     */
    public function testCreateWithUriAndTitle(): void
    {
        $uri = 'https://example.com/doc';
        $title = 'Example Document';
        $citation = new Citation($uri, $title);

        $this->assertEquals($uri, $citation->getUri());
        $this->assertEquals($title, $citation->getTitle());
    }

    /**
     * Tests JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = Citation::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey(Citation::KEY_URI, $schema['properties']);
        $this->assertArrayHasKey(Citation::KEY_TITLE, $schema['properties']);
        $this->assertEquals(['uri'], $schema['required']);
        $this->assertFalse($schema['additionalProperties']);
    }

    /**
     * Tests array transformation with URI only.
     *
     * @return void
     */
    public function testToArrayWithUriOnly(): void
    {
        $citation = new Citation('https://example.com/doc');
        $json = $citation->toArray();

        $this->assertIsArray($json);
        $this->assertArrayHasKey(Citation::KEY_URI, $json);
        $this->assertEquals('https://example.com/doc', $json[Citation::KEY_URI]);
        $this->assertArrayNotHasKey(Citation::KEY_TITLE, $json);
    }

    /**
     * Tests array transformation with URI and title.
     *
     * @return void
     */
    public function testToArrayWithUriAndTitle(): void
    {
        $citation = new Citation('https://example.com/doc', 'Doc Title');
        $json = $citation->toArray();

        $this->assertIsArray($json);
        $this->assertArrayHasKey(Citation::KEY_URI, $json);
        $this->assertArrayHasKey(Citation::KEY_TITLE, $json);
        $this->assertEquals('https://example.com/doc', $json[Citation::KEY_URI]);
        $this->assertEquals('Doc Title', $json[Citation::KEY_TITLE]);
    }

    /**
     * Tests fromArray with URI only.
     *
     * @return void
     */
    public function testFromArrayWithUriOnly(): void
    {
        $json = [
            Citation::KEY_URI => 'https://example.com/doc',
        ];

        $citation = Citation::fromArray($json);

        $this->assertEquals('https://example.com/doc', $citation->getUri());
        $this->assertNull($citation->getTitle());
    }

    /**
     * Tests fromArray with URI and title.
     *
     * @return void
     */
    public function testFromArrayWithUriAndTitle(): void
    {
        $json = [
            Citation::KEY_URI => 'https://example.com/doc',
            Citation::KEY_TITLE => 'Doc Title',
        ];

        $citation = Citation::fromArray($json);

        $this->assertEquals('https://example.com/doc', $citation->getUri());
        $this->assertEquals('Doc Title', $citation->getTitle());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $original = new Citation('https://example.com/doc', 'Doc Title');
        $array = $original->toArray();
        $restored = Citation::fromArray($array);

        $this->assertEquals($original->getUri(), $restored->getUri());
        $this->assertEquals($original->getTitle(), $restored->getTitle());
    }
}

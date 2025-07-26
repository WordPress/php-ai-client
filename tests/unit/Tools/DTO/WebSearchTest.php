<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Tools\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Tools\DTO\WebSearch;

/**
 * @covers \WordPress\AiClient\Tools\DTO\WebSearch
 */
class WebSearchTest extends TestCase
{
    /**
     * Tests creating WebSearch with both allowed and disallowed domains.
     *
     * @return void
     */
    public function testCreateWithBothAllowedAndDisallowedDomains(): void
    {
        $allowedDomains = ['wikipedia.org', 'docs.python.org', 'php.net'];
        $disallowedDomains = ['spam.com', 'malware.site', 'phishing.net'];
        
        $webSearch = new WebSearch($allowedDomains, $disallowedDomains);
        
        $this->assertEquals($allowedDomains, $webSearch->getAllowedDomains());
        $this->assertEquals($disallowedDomains, $webSearch->getDisallowedDomains());
    }

    /**
     * Tests creating WebSearch with only allowed domains.
     *
     * @return void
     */
    public function testCreateWithOnlyAllowedDomains(): void
    {
        $allowedDomains = ['example.com', 'test.org'];
        
        $webSearch = new WebSearch($allowedDomains);
        
        $this->assertEquals($allowedDomains, $webSearch->getAllowedDomains());
        $this->assertEquals([], $webSearch->getDisallowedDomains());
    }

    /**
     * Tests creating WebSearch with only disallowed domains.
     *
     * @return void
     */
    public function testCreateWithOnlyDisallowedDomains(): void
    {
        $disallowedDomains = ['bad.com', 'blocked.org'];
        
        $webSearch = new WebSearch([], $disallowedDomains);
        
        $this->assertEquals([], $webSearch->getAllowedDomains());
        $this->assertEquals($disallowedDomains, $webSearch->getDisallowedDomains());
    }

    /**
     * Tests creating WebSearch with no domain restrictions.
     *
     * @return void
     */
    public function testCreateWithNoDomainRestrictions(): void
    {
        $webSearch = new WebSearch();
        
        $this->assertEquals([], $webSearch->getAllowedDomains());
        $this->assertEquals([], $webSearch->getDisallowedDomains());
    }

    /**
     * Tests WebSearch with various domain formats.
     *
     * @return void
     */
    public function testWithVariousDomainFormats(): void
    {
        $allowedDomains = [
            'example.com',
            'subdomain.example.com',
            'deep.subdomain.example.com',
            'example.co.uk',
            'example.org',
            'localhost',
            '192.168.1.1',
            'example-with-dash.com',
            'UPPERCASE.COM'
        ];
        
        $webSearch = new WebSearch($allowedDomains);
        
        $this->assertEquals($allowedDomains, $webSearch->getAllowedDomains());
    }

    /**
     * Tests WebSearch with duplicate domains.
     *
     * @return void
     */
    public function testWithDuplicateDomains(): void
    {
        $allowedDomains = ['example.com', 'test.org', 'example.com'];
        $disallowedDomains = ['bad.com', 'bad.com', 'worse.com'];
        
        $webSearch = new WebSearch($allowedDomains, $disallowedDomains);
        
        // Note: WebSearch doesn't deduplicate - that's up to the implementation
        $this->assertEquals($allowedDomains, $webSearch->getAllowedDomains());
        $this->assertEquals($disallowedDomains, $webSearch->getDisallowedDomains());
    }

    /**
     * Tests JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = WebSearch::getJsonSchema();
        
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        
        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('allowedDomains', $schema['properties']);
        $this->assertArrayHasKey('disallowedDomains', $schema['properties']);
        
        // Check allowedDomains property
        $allowedSchema = $schema['properties']['allowedDomains'];
        $this->assertEquals('array', $allowedSchema['type']);
        $this->assertArrayHasKey('items', $allowedSchema);
        $this->assertEquals('string', $allowedSchema['items']['type']);
        $this->assertArrayHasKey('description', $allowedSchema);
        
        // Check disallowedDomains property
        $disallowedSchema = $schema['properties']['disallowedDomains'];
        $this->assertEquals('array', $disallowedSchema['type']);
        $this->assertArrayHasKey('items', $disallowedSchema);
        $this->assertEquals('string', $disallowedSchema['items']['type']);
        $this->assertArrayHasKey('description', $disallowedSchema);
        
        // Check required fields (should be empty array)
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals([], $schema['required']);
    }

    /**
     * Tests WebSearch with empty strings in arrays.
     *
     * @return void
     */
    public function testWithEmptyStringsInArrays(): void
    {
        $allowedDomains = ['example.com', '', 'test.org'];
        $disallowedDomains = ['', 'bad.com', ''];
        
        $webSearch = new WebSearch($allowedDomains, $disallowedDomains);
        
        $this->assertEquals($allowedDomains, $webSearch->getAllowedDomains());
        $this->assertEquals($disallowedDomains, $webSearch->getDisallowedDomains());
    }

    /**
     * Tests WebSearch with single domain in each list.
     *
     * @return void
     */
    public function testWithSingleDomainInEachList(): void
    {
        $webSearch = new WebSearch(['trusted.com'], ['untrusted.com']);
        
        $this->assertCount(1, $webSearch->getAllowedDomains());
        $this->assertCount(1, $webSearch->getDisallowedDomains());
        $this->assertEquals('trusted.com', $webSearch->getAllowedDomains()[0]);
        $this->assertEquals('untrusted.com', $webSearch->getDisallowedDomains()[0]);
    }

    /**
     * Tests WebSearch with many domains.
     *
     * @return void
     */
    public function testWithManyDomains(): void
    {
        $allowedDomains = [];
        $disallowedDomains = [];
        
        // Create 100 allowed domains
        for ($i = 0; $i < 100; $i++) {
            $allowedDomains[] = "allowed-domain-$i.com";
        }
        
        // Create 50 disallowed domains
        for ($i = 0; $i < 50; $i++) {
            $disallowedDomains[] = "blocked-domain-$i.com";
        }
        
        $webSearch = new WebSearch($allowedDomains, $disallowedDomains);
        
        $this->assertCount(100, $webSearch->getAllowedDomains());
        $this->assertCount(50, $webSearch->getDisallowedDomains());
        $this->assertEquals('allowed-domain-0.com', $webSearch->getAllowedDomains()[0]);
        $this->assertEquals('allowed-domain-99.com', $webSearch->getAllowedDomains()[99]);
        $this->assertEquals('blocked-domain-0.com', $webSearch->getDisallowedDomains()[0]);
        $this->assertEquals('blocked-domain-49.com', $webSearch->getDisallowedDomains()[49]);
    }

    /**
     * Tests WebSearch implements WithJsonSchemaInterface.
     *
     * @return void
     */
    public function testImplementsWithJsonSchemaInterface(): void
    {
        $webSearch = new WebSearch();
        
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface::class,
            $webSearch
        );
    }

    /**
     * Tests creating multiple WebSearch instances.
     *
     * @return void
     */
    public function testMultipleInstances(): void
    {
        $webSearch1 = new WebSearch(['a.com'], ['b.com']);
        $webSearch2 = new WebSearch(['c.com'], ['d.com']);
        $webSearch3 = new WebSearch(['a.com'], ['b.com']);
        
        // Different instances
        $this->assertNotSame($webSearch1, $webSearch2);
        $this->assertNotSame($webSearch1, $webSearch3);
        
        // Different content
        $this->assertNotEquals($webSearch1->getAllowedDomains(), $webSearch2->getAllowedDomains());
        $this->assertNotEquals($webSearch1->getDisallowedDomains(), $webSearch2->getDisallowedDomains());
        
        // Same content but different instances
        $this->assertEquals($webSearch1->getAllowedDomains(), $webSearch3->getAllowedDomains());
        $this->assertEquals($webSearch1->getDisallowedDomains(), $webSearch3->getDisallowedDomains());
    }

    /**
     * Tests WebSearch with common domain patterns.
     *
     * @return void
     */
    public function testWithCommonDomainPatterns(): void
    {
        $allowedDomains = [
            // News sites
            'cnn.com',
            'bbc.co.uk',
            'reuters.com',
            
            // Documentation sites
            'docs.microsoft.com',
            'developer.mozilla.org',
            'stackoverflow.com',
            
            // Academic sites
            'arxiv.org',
            'scholar.google.com',
            'pubmed.ncbi.nlm.nih.gov'
        ];
        
        $disallowedDomains = [
            // Social media
            'facebook.com',
            'twitter.com',
            'instagram.com',
            
            // Video platforms
            'youtube.com',
            'vimeo.com',
            'tiktok.com'
        ];
        
        $webSearch = new WebSearch($allowedDomains, $disallowedDomains);
        
        $this->assertCount(9, $webSearch->getAllowedDomains());
        $this->assertCount(6, $webSearch->getDisallowedDomains());
        $this->assertContains('stackoverflow.com', $webSearch->getAllowedDomains());
        $this->assertContains('youtube.com', $webSearch->getDisallowedDomains());
    }
}
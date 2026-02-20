<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\Collections;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\Collections\HeadersCollection;

/**
 * Tests for HeadersCollection class.
 *
 * @covers \WordPress\AiClient\Providers\Http\Collections\HeadersCollection
 */
class HeadersCollectionTest extends TestCase
{
    /**
     * Tests constructor with initial headers.
     *
     * @return void
     */
    public function testConstructorWithHeaders(): void
    {
        $headers = new HeadersCollection([
            'Content-Type' => 'application/json',
            'X-Custom' => ['value1', 'value2'],
        ]);

        $this->assertEquals(['application/json'], $headers->get('Content-Type'));
        $this->assertEquals(['value1', 'value2'], $headers->get('X-Custom'));
    }

    /**
     * Tests constructor with comma-separated string values.
     *
     * @return void
     */
    public function testConstructorWithCommaSeparatedString(): void
    {
        $headers = new HeadersCollection([
            'Accept' => 'application/json, text/html, application/xml',
            'Cache-Control' => 'no-cache, no-store',
        ]);

        $this->assertEquals(['application/json', 'text/html', 'application/xml'], $headers->get('Accept'));
        $this->assertEquals(['no-cache', 'no-store'], $headers->get('Cache-Control'));
    }

    /**
     * Tests case-insensitive header access.
     *
     * @return void
     */
    public function testCaseInsensitiveAccess(): void
    {
        $headers = new HeadersCollection(['Content-Type' => 'application/json']);

        $this->assertEquals(['application/json'], $headers->get('Content-Type'));
        $this->assertEquals(['application/json'], $headers->get('content-type'));
        $this->assertEquals(['application/json'], $headers->get('CONTENT-TYPE'));
    }

    /**
     * Tests that original header casing is preserved.
     *
     * @return void
     */
    public function testPreservesOriginalCasing(): void
    {
        $headers = new HeadersCollection(['Content-Type' => 'application/json']);

        $all = $headers->getAll();
        $this->assertArrayHasKey('Content-Type', $all);
        $this->assertArrayNotHasKey('content-type', $all);
    }

    /**
     * Tests case-insensitive header replacement.
     *
     * @return void
     */
    public function testCaseInsensitiveReplacement(): void
    {
        $headers = new HeadersCollection(['Content-Type' => 'application/json']);

        // withHeader with different casing should replace existing with new casing
        $newHeaders = $headers->withHeader('content-type', 'text/html');

        $this->assertEquals(['text/html'], $newHeaders->get('Content-Type'));
        $this->assertEquals(['text/html'], $newHeaders->get('content-type'));

        // New casing should be used
        $all = $newHeaders->getAll();
        $this->assertArrayHasKey('content-type', $all);
        $this->assertArrayNotHasKey('Content-Type', $all);
    }

    /**
     * Tests getAsString method.
     *
     * @return void
     */
    public function testGetAsString(): void
    {
        $headers = new HeadersCollection([
            'Accept' => ['application/json', 'application/xml'],
            'Content-Type' => 'text/html',
        ]);

        $this->assertEquals('application/json, application/xml', $headers->getAsString('Accept'));
        $this->assertEquals('text/html', $headers->getAsString('Content-Type'));
        $this->assertNull($headers->getAsString('Non-Existent'));
    }

    /**
     * Tests has method.
     *
     * @return void
     */
    public function testHas(): void
    {
        $headers = new HeadersCollection(['Content-Type' => 'application/json']);

        $this->assertTrue($headers->has('Content-Type'));
        $this->assertTrue($headers->has('content-type'));
        $this->assertTrue($headers->has('CONTENT-TYPE'));
        $this->assertFalse($headers->has('Accept'));
    }

    /**
     * Tests withHeader immutability.
     *
     * @return void
     */
    public function testWithHeaderImmutability(): void
    {
        $original = new HeadersCollection(['Content-Type' => 'application/json']);

        $new = $original->withHeader('Accept', 'text/html');

        $this->assertNotSame($original, $new);
        $this->assertNull($original->get('Accept'));
        $this->assertEquals(['text/html'], $new->get('Accept'));
        $this->assertEquals(['application/json'], $original->get('Content-Type'));
    }

    /**
     * Tests withHeader with comma-separated string.
     *
     * @return void
     */
    public function testWithHeaderCommaSeparatedString(): void
    {
        $headers = new HeadersCollection();
        $new = $headers->withHeader('Accept', 'application/json, text/html, application/xml');

        $this->assertEquals(['application/json', 'text/html', 'application/xml'], $new->get('Accept'));
    }
}

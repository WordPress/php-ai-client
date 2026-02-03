<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\DTO\Response;

/**
 * @covers \WordPress\AiClient\Providers\Http\DTO\Response
 */
class ResponseTest extends TestCase
{
    /**
     * Tests that cloning creates an independent headers reference.
     *
     * @return void
     */
    public function testCloneCreatesDifferentHeadersReference(): void
    {
        $original = new Response(
            200,
            ['Content-Type' => 'application/json', 'X-Test' => 'value'],
            '{"key": "value"}'
        );

        $cloned = clone $original;

        // Headers should be equivalent but independent
        $this->assertEquals($original->getHeaders(), $cloned->getHeaders());
        $this->assertTrue($cloned->hasHeader('Content-Type'));
        $this->assertTrue($cloned->hasHeader('X-Test'));

        // Verify other properties are preserved
        $this->assertEquals($original->getStatusCode(), $cloned->getStatusCode());
        $this->assertEquals($original->getBody(), $cloned->getBody());
    }

    /**
     * Tests that cloning preserves all response data.
     *
     * @return void
     */
    public function testClonePreservesResponseData(): void
    {
        $original = new Response(
            201,
            ['Location' => 'https://example.com/resource/1'],
            'Created'
        );

        $cloned = clone $original;

        $this->assertEquals(201, $cloned->getStatusCode());
        $this->assertEquals(['https://example.com/resource/1'], $cloned->getHeader('Location'));
        $this->assertEquals('Created', $cloned->getBody());
        $this->assertTrue($cloned->isSuccessful());
    }

    /**
     * Tests that cloning works correctly with null body.
     *
     * @return void
     */
    public function testCloneWorksWithNullBody(): void
    {
        $original = new Response(
            204,
            ['X-Request-Id' => 'abc123']
        );

        $cloned = clone $original;

        $this->assertNull($cloned->getBody());
        $this->assertEquals(204, $cloned->getStatusCode());
        $this->assertTrue($cloned->hasHeader('X-Request-Id'));
    }
}

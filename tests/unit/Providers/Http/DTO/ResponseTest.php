<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\DTO;

use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Tests\mocks\ChunkStream;

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

    /**
     * Tests that a stream body is returned as-is by getStream.
     *
     * @return void
     */
    public function testGetStreamReturnsTheStreamBody(): void
    {
        $stream = new ChunkStream(['{"ok":true}']);
        $response = new Response(200, [], $stream);

        $this->assertSame($stream, $response->getStream());
    }

    /**
     * Tests that a streamed body is read into a string by getBody.
     *
     * @return void
     */
    public function testStreamedBodyIsReadByGetBody(): void
    {
        $response = new Response(200, [], new ChunkStream(['{"ok":', 'true}']));

        $this->assertSame('{"ok":true}', $response->getBody());
    }

    /**
     * Tests that a streamed JSON body is decoded by getData.
     *
     * @return void
     */
    public function testStreamedBodyIsDecodedByGetData(): void
    {
        $response = new Response(200, [], new ChunkStream(['{"ok":true}']));

        $this->assertSame(['ok' => true], $response->getData());
    }

    /**
     * Tests that a buffered body is wrapped in a stream by getStream.
     *
     * @return void
     */
    public function testBufferedBodyIsWrappedInStreamByGetStream(): void
    {
        $response = new Response(200, [], 'hello');

        $this->assertSame('hello', (string) $response->getStream());
    }

    /**
     * Tests that a seekable stream body is rewound before being read.
     *
     * @return void
     */
    public function testSeekableStreamBodyIsRewoundBeforeRead(): void
    {
        $stream = Utils::streamFor('{"ok":true}');
        $stream->read(2);

        $response = new Response(200, [], $stream);

        $this->assertSame('{"ok":true}', $response->getBody());
    }

    /**
     * Tests that toArray serializes the body, reading a streamed body when needed.
     *
     * @return void
     */
    public function testToArraySerializesStreamedBody(): void
    {
        $response = new Response(200, ['X-Test' => 'value'], new ChunkStream(['streamed body']));

        $array = $response->toArray();

        $this->assertSame(200, $array[Response::KEY_STATUS_CODE]);
        $this->assertSame('streamed body', $array[Response::KEY_BODY]);
    }
}

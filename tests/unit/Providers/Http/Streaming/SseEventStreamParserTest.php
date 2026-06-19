<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\Streaming;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Tests\mocks\ChunkStream;
use WordPress\AiClient\Providers\Http\Streaming\ServerSentEvent;
use WordPress\AiClient\Providers\Http\Streaming\SseEventStreamParser;

/**
 * Test for SseEventStreamParser class.
 *
 * @covers \WordPress\AiClient\Providers\Http\Streaming\SseEventStreamParser
 */
class SseEventStreamParserTest extends TestCase
{
    /**
     * Parses the given chunks into a list of events.
     *
     * @param list<string> $chunks The byte chunks to feed to the parser.
     * @return list<ServerSentEvent> The parsed events.
     */
    private function parse(array $chunks): array
    {
        return iterator_to_array((new SseEventStreamParser())->parse(new ChunkStream($chunks)), false);
    }

    /**
     * Parses a single body string into a list of events.
     *
     * @param string $body The full body.
     * @return list<ServerSentEvent> The parsed events.
     */
    private function parseBody(string $body): array
    {
        return $this->parse([$body]);
    }

    /**
     * Maps events to their data payloads.
     *
     * @param list<ServerSentEvent> $events The events.
     * @return list<string> The data payloads.
     */
    private function dataOf(array $events): array
    {
        return array_map(static fn (ServerSentEvent $e): string => $e->getData(), $events);
    }

    /**
     * Tests field parsing across colons, NUL bytes, casing, and line endings.
     *
     * @return void
     */
    public function testFieldParsing(): void
    {
        $body = "data:\x00\n"
            . "data:  2\r"
            . "Data:1\n"
            . "data\x00:2\n"
            . "data:1\r"
            . "\x00data:4\n"
            . "da-ta:3\r"
            . "data_5\n"
            . "data:3\r"
            . "data:\r\n"
            . " data:32\n"
            . "data:4\n"
            . "\n";

        $events = $this->parseBody($body);

        $this->assertCount(1, $events);
        $this->assertSame("\x00\n 2\n1\n3\n\n4", $events[0]->getData());
    }

    /**
     * Tests the data field: empty values, missing colons, and accumulation.
     *
     * @return void
     */
    public function testDataFieldVariants(): void
    {
        $events = $this->parseBody("data:\n\ndata\ndata\n\ndata:test\n\n");

        $this->assertSame(['', "\n", 'test'], $this->dataOf($events));
    }

    /**
     * Tests that a custom event name is carried, and the default is "message".
     *
     * @return void
     */
    public function testCustomEventName(): void
    {
        $events = $this->parseBody("event:test\ndata:x\n\ndata:x\n\n");

        $this->assertCount(2, $events);
        $this->assertSame('test', $events[0]->getEvent());
        $this->assertSame('message', $events[1]->getEvent());
    }

    /**
     * Tests that an empty event field falls back to the default "message" type.
     *
     * @return void
     */
    public function testEmptyEventNameDefaultsToMessage(): void
    {
        $events = $this->parseBody("event: \ndata:data\n\n");

        $this->assertCount(1, $events);
        $this->assertSame('message', $events[0]->getEvent());
        $this->assertSame('data', $events[0]->getData());
    }

    /**
     * Tests that comment and unknown lines are ignored among data lines.
     *
     * @return void
     */
    public function testCommentsIgnored(): void
    {
        $long = str_repeat('x', 16);
        $body = "data:1\r"
            . ":\x00\n"
            . ":\r\n"
            . "data:2\n"
            . ':' . $long . "\r"
            . "data:3\n"
            . ":data:fail\r"
            . ':' . $long . "\n"
            . "data:4\n"
            . "\n";

        $events = $this->parseBody($body);

        $this->assertCount(1, $events);
        $this->assertSame("1\n2\n3\n4", $events[0]->getData());
    }

    /**
     * Tests that unknown fields, leading-space field names, and comments are skipped.
     *
     * @return void
     */
    public function testUnknownFieldsIgnored(): void
    {
        $body = "data:test\n"
            . " data\n"
            . "data\n"
            . "foobar:xxx\n"
            . "justsometext\n"
            . ":thisisacommentyay\n"
            . "data:test\n"
            . "\n";

        $events = $this->parseBody($body);

        $this->assertCount(1, $events);
        $this->assertSame("test\n\ntest", $events[0]->getData());
    }

    /**
     * Tests that only one leading space is stripped, a tab is kept, and CR ends a line.
     *
     * @return void
     */
    public function testLeadingSpaceStrippedOnce(): void
    {
        $events = $this->parseBody("data:\ttest\rdata: \ndata:test\n\n");

        $this->assertCount(1, $events);
        $this->assertSame("\ttest\n\ntest", $events[0]->getData());
    }

    /**
     * Tests CRLF, LF, and a lone CR are all treated as line terminators.
     *
     * @return void
     */
    public function testNewlineVariants(): void
    {
        $events = $this->parseBody("data:test\r\ndata\ndata:test\r\n\r\n");

        $this->assertCount(1, $events);
        $this->assertSame("test\n\ntest", $events[0]->getData());
    }

    /**
     * Tests that a NUL byte is preserved in the data payload.
     *
     * @return void
     */
    public function testNullCharacterInData(): void
    {
        $events = $this->parseBody("data:\x00\n\n");

        $this->assertCount(1, $events);
        $this->assertSame("\x00", $events[0]->getData());
    }

    /**
     * Tests that multi-byte UTF-8 data is passed through unchanged.
     *
     * @return void
     */
    public function testUtf8DataPreserved(): void
    {
        $events = $this->parseBody("data:ok\xE2\x80\xA6\n\n");

        $this->assertCount(1, $events);
        $this->assertSame('ok…', $events[0]->getData());
    }

    /**
     * Tests that the id field sets the event ID.
     *
     * @return void
     */
    public function testIdFieldSetsId(): void
    {
        $events = $this->parseBody("id:abc\ndata:x\n\n");

        $this->assertCount(1, $events);
        $this->assertSame('abc', $events[0]->getId());
    }

    /**
     * Tests that an id containing a NUL byte is ignored.
     *
     * @dataProvider provideNulIds
     *
     * @param string $idValue The id field value.
     * @return void
     */
    public function testIdWithNulIgnored(string $idValue): void
    {
        $events = $this->parseBody('id:' . $idValue . "\ndata:hello\n\n");

        $this->assertCount(1, $events);
        $this->assertSame('', $events[0]->getId());
        $this->assertSame('hello', $events[0]->getData());
    }

    /**
     * @return array<string, array{string}>
     */
    public function provideNulIds(): array
    {
        return [
            'two nulls' => ["\x00\x00"],
            'trailing null' => ["x\x00"],
            'leading null' => ["\x00x"],
            'embedded null' => ["x\x00x"],
            'space then null' => [" \x00"],
        ];
    }

    /**
     * Tests that the last event ID persists across events and resets on an empty id.
     *
     * @return void
     */
    public function testIdPersistsAndResets(): void
    {
        $body = "id:1\ndata:1\n\n"
            . "data:2\n\n"
            . "id\ndata:3\n\n"
            . "id:2\ndata:4\n\n";

        $events = $this->parseBody($body);

        $ids = array_map(static fn (ServerSentEvent $e): string => $e->getId(), $events);
        $this->assertSame(['1', '1', '', '2'], $ids);
        $this->assertSame(['1', '2', '3', '4'], $this->dataOf($events));
    }

    /**
     * Tests retry field parsing, including decimal-not-octal and bogus values.
     *
     * @dataProvider provideRetry
     *
     * @param string $body The body.
     * @param int|null $expected The expected retry value.
     * @return void
     */
    public function testRetryField(string $body, ?int $expected): void
    {
        $events = $this->parseBody($body);

        $this->assertCount(1, $events);
        $this->assertSame($expected, $events[0]->getRetry());
    }

    /**
     * @return array<string, array{string, int|null}>
     */
    public function provideRetry(): array
    {
        return [
            'plain' => ["retry:3000\ndata:x\n\n", 3000],
            'leading zero is decimal not octal' => ["retry:03000\ndata:x\n\n", 3000],
            'bogus is ignored' => ["retry:1000x\ndata:x\n\n", null],
            'bogus keeps previous value' => ["retry:3000\nretry:1000x\ndata:x\n\n", 3000],
            'empty retry field' => ["retry\ndata:x\n\n", null],
        ];
    }

    /**
     * Tests that a leading BOM is stripped once while a mid-stream BOM is literal.
     *
     * @return void
     */
    public function testLeadingBomStrippedOnce(): void
    {
        $bom = "\xEF\xBB\xBF";
        $events = $this->parseBody($bom . "data:1\n\n" . $bom . "data:2\n\ndata:3\n\n");

        $this->assertSame(['1', '3'], $this->dataOf($events));
    }

    /**
     * Tests that only the first of a double BOM is stripped.
     *
     * @return void
     */
    public function testDoubleBomStripsOnlyOne(): void
    {
        $bom = "\xEF\xBB\xBF";
        $events = $this->parseBody($bom . $bom . "data:1\n\ndata:2\n\ndata:3\n\n");

        $this->assertSame(['2', '3'], $this->dataOf($events));
    }

    /**
     * Tests that an event left pending at EOF (no final blank line) is discarded.
     *
     * @return void
     */
    public function testIncompleteFinalEventDiscarded(): void
    {
        $events = $this->parseBody("retry:1000\ndata:test1\n\nid:test\ndata:test2\n");

        $this->assertCount(1, $events);
        $this->assertSame('test1', $events[0]->getData());
        $this->assertSame('', $events[0]->getId());
        $this->assertSame(1000, $events[0]->getRetry());
    }

    /**
     * Tests line and data parsing across a fuller mixed stream.
     *
     * @return void
     */
    public function testMixedStreamLinesAndData(): void
    {
        $body = "data:msg\n"
            . "data: msg\n\n"
            . ":\n"
            . "falsefield:msg\n\n"
            . "falsefield:msg\n"
            . "Data:data\n\n"
            . "data\n\n"
            . "data:end\n\n";

        $events = $this->parseBody($body);

        $this->assertSame(["msg\nmsg", '', 'end'], $this->dataOf($events));
    }

    /**
     * Tests that an empty stream yields no events.
     *
     * @return void
     */
    public function testEmptyStream(): void
    {
        $this->assertSame([], $this->parse([]));
        $this->assertSame([], $this->parseBody(''));
    }

    /**
     * Tests that a frame split across reads is reassembled.
     *
     * @return void
     */
    public function testFrameSplitAcrossChunks(): void
    {
        $events = $this->parse(['data: hel', 'lo wor', "ld\n", "\n"]);

        $this->assertCount(1, $events);
        $this->assertSame('hello world', $events[0]->getData());
    }

    /**
     * Tests a CRLF terminator split across two reads (CR ends one chunk, LF starts the next).
     *
     * @return void
     */
    public function testCrlfSplitAcrossChunks(): void
    {
        $events = $this->parse(["data:x\r", "\n\r\n"]);

        $this->assertCount(1, $events);
        $this->assertSame('x', $events[0]->getData());
    }

    /**
     * Tests a lone CR separating two data lines within one event.
     *
     * @return void
     */
    public function testLoneCrSeparatesDataLines(): void
    {
        $events = $this->parseBody("data: a\rdata: b\n\n");

        $this->assertCount(1, $events);
        $this->assertSame("a\nb", $events[0]->getData());
    }

    /**
     * Tests a multi-byte UTF-8 character split across reads.
     *
     * @return void
     */
    public function testMultibyteUtf8SplitAcrossChunks(): void
    {
        $events = $this->parse(["data:a\xE2\x80", "\xA6b\n\n"]);

        $this->assertCount(1, $events);
        $this->assertSame('a…b', $events[0]->getData());
    }

    /**
     * Tests a BOM split across reads is still stripped.
     *
     * @return void
     */
    public function testBomSplitAcrossChunks(): void
    {
        $events = $this->parse(["\xEF", "\xBB\xBF", "data:x\n\n"]);

        $this->assertCount(1, $events);
        $this->assertSame('x', $events[0]->getData());
    }

    /**
     * Tests that the stream is closed after it is fully consumed.
     *
     * @return void
     */
    public function testStreamClosedAfterConsumption(): void
    {
        $stream = new ChunkStream(["data:x\n\n"]);
        iterator_to_array((new SseEventStreamParser())->parse($stream), false);

        $this->assertTrue($stream->isClosed());
    }

    /**
     * Tests that the stream is closed when iteration stops early.
     *
     * @return void
     */
    public function testStreamClosedOnEarlyAbandon(): void
    {
        $stream = new ChunkStream(["data:1\n\ndata:2\n\n"]);

        foreach ((new SseEventStreamParser())->parse($stream) as $event) {
            break;
        }

        $this->assertTrue($stream->isClosed());
    }

    /**
     * Tests that events are produced lazily as the stream is read.
     *
     * @return void
     */
    public function testLazyConsumption(): void
    {
        $stream = new ChunkStream(["data:1\n\n", "data:2\n\n"]);
        $generator = (new SseEventStreamParser())->parse($stream);

        $this->assertSame('1', $generator->current()->getData());
        $this->assertSame(1, $stream->getReadCount());

        $generator->next();
        $this->assertSame('2', $generator->current()->getData());
        $this->assertSame(2, $stream->getReadCount());
    }
}

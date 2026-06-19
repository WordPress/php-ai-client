<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use RuntimeException;
use Psr\Http\Message\StreamInterface;

/**
 * A read-only stream that returns a fixed list of chunks, one per read().
 */
class ChunkStream implements StreamInterface
{
    /**
     * @var list<string> The chunks to return, in order.
     */
    private array $chunks;

    /**
     * @var int Index of the next chunk to return.
     */
    private int $position = 0;

    /**
     * @var int Number of read() calls that returned a chunk.
     */
    private int $readCount = 0;

    /**
     * @var bool Whether close() has been called.
     */
    private bool $closed = false;

    /**
     * @param list<string> $chunks The chunks to return, in order.
     */
    public function __construct(array $chunks)
    {
        $this->chunks = array_values($chunks);
    }

    /**
     * @return int Number of read() calls that returned a chunk.
     */
    public function getReadCount(): int
    {
        return $this->readCount;
    }

    /**
     * @return bool Whether the stream was closed.
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function eof(): bool
    {
        return $this->position >= count($this->chunks);
    }

    public function read(int $length): string
    {
        if ($this->eof()) {
            return '';
        }

        $this->readCount++;

        return $this->chunks[$this->position++];
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function __toString(): string
    {
        return implode('', array_slice($this->chunks, $this->position));
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new RuntimeException('Not seekable.');
    }

    public function rewind(): void
    {
        throw new RuntimeException('Not seekable.');
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new RuntimeException('Not writable.');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function getContents(): string
    {
        $contents = implode('', array_slice($this->chunks, $this->position));
        $this->position = count($this->chunks);

        return $contents;
    }

    /**
     * @param string|null $key The metadata key.
     * @return array<string, mixed>|null The metadata.
     */
    public function getMetadata(?string $key = null)
    {
        return $key === null ? [] : null;
    }
}

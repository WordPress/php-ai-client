<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use RuntimeException;

/**
 * A stream that delivers a set of chunks and then fails with an error.
 */
class FailingChunkStream extends ChunkStream
{
    /**
     * @var string The message of the error thrown once the chunks are exhausted.
     */
    private string $errorMessage;

    /**
     * @param list<string> $chunks The chunks to deliver before failing.
     * @param string $errorMessage The message of the error thrown afterwards.
     */
    public function __construct(array $chunks, string $errorMessage = 'Connection reset by peer')
    {
        parent::__construct($chunks);
        $this->errorMessage = $errorMessage;
    }

    /**
     * Never reports end-of-stream, so the reader keeps reading until the failure.
     */
    public function eof(): bool
    {
        return false;
    }

    /**
     * Returns the next chunk, or throws once the chunks are exhausted.
     */
    public function read(int $length): string
    {
        if (parent::eof()) {
            throw new RuntimeException($this->errorMessage);
        }

        return parent::read($length);
    }
}

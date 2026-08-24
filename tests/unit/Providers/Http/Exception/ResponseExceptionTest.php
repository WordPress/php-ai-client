<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;

/**
 * @covers \WordPress\AiClient\Providers\Http\Exception\ResponseException
 */
class ResponseExceptionTest extends TestCase
{
    /**
     * Tests that fromStreamError builds the streaming error message.
     */
    public function testFromStreamErrorMessage(): void
    {
        $exception = ResponseException::fromStreamError('OpenAI', 'connection reset');

        $this->assertSame('Error while streaming the OpenAI API response: connection reset', $exception->getMessage());
    }

    /**
     * Tests that fromStreamError chains the previous exception when given one.
     */
    public function testFromStreamErrorChainsPreviousException(): void
    {
        $previous = new RuntimeException('underlying');

        $exception = ResponseException::fromStreamError('OpenAI', 'connection reset', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * Tests that fromStreamError has no previous exception by default.
     */
    public function testFromStreamErrorHasNoPreviousByDefault(): void
    {
        $exception = ResponseException::fromStreamError('OpenAI', 'connection reset');

        $this->assertNull($exception->getPrevious());
    }

    /**
     * Tests that fromMissingData builds the missing-key message.
     */
    public function testFromMissingDataMessage(): void
    {
        $exception = ResponseException::fromMissingData('OpenAI', 'choices');

        $this->assertSame('Unexpected OpenAI API response: Missing the "choices" key.', $exception->getMessage());
    }

    /**
     * Tests that fromInvalidData builds the invalid-key message.
     */
    public function testFromInvalidDataMessage(): void
    {
        $exception = ResponseException::fromInvalidData('OpenAI', 'usage', 'not an object');

        $this->assertSame(
            'Unexpected OpenAI API response: Invalid "usage" key: not an object',
            $exception->getMessage()
        );
    }
}

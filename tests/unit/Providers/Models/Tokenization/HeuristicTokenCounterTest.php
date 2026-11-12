<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\Tokenization;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Tokenization\Contracts\TokenCounterInterface;
use WordPress\AiClient\Providers\Models\Tokenization\HeuristicTokenCounter;

/**
 * @covers \WordPress\AiClient\Providers\Models\Tokenization\HeuristicTokenCounter
 */
class HeuristicTokenCounterTest extends TestCase
{
    /**
     * @var HeuristicTokenCounter
     */
    private HeuristicTokenCounter $counter;

    /**
     * @var ModelMetadata
     */
    private ModelMetadata $metadata;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counter = new HeuristicTokenCounter();
        $this->metadata = new ModelMetadata('m', 'M', [], []);
    }

    /**
     * Tests that the counter implements the shared contract.
     *
     * @return void
     */
    public function testImplementsTokenCounterInterface(): void
    {
        $this->assertInstanceOf(TokenCounterInterface::class, $this->counter);
    }

    /**
     * Tests that an empty message list yields zero tokens.
     *
     * @return void
     */
    public function testCountTokensReturnsZeroForNoMessages(): void
    {
        $this->assertSame(0, $this->counter->countTokens([], $this->metadata));
    }

    /**
     * Tests that text is estimated at roughly four characters per token, rounded up.
     *
     * @return void
     */
    public function testCountTokensRoundsUp(): void
    {
        // 4 characters -> 1 token.
        $exact = new UserMessage([new MessagePart('abcd')]);
        $this->assertSame(1, $this->counter->countTokens([$exact], $this->metadata));

        // 5 characters -> ceil(5 / 4) = 2 tokens.
        $overflow = new UserMessage([new MessagePart('abcde')]);
        $this->assertSame(2, $this->counter->countTokens([$overflow], $this->metadata));
    }

    /**
     * Tests that text across multiple parts and messages is summed.
     *
     * @return void
     */
    public function testCountTokensSumsAcrossPartsAndMessages(): void
    {
        $messages = [
            new UserMessage([
                new MessagePart('abcd'),
                new MessagePart('efgh'),
            ]),
            new UserMessage([new MessagePart('ijkl')]),
        ];

        // 12 characters total -> 12 / 4 = 3 tokens.
        $this->assertSame(3, $this->counter->countTokens($messages, $this->metadata));
    }
}

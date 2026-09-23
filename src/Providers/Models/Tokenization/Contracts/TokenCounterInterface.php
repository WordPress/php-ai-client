<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Tokenization\Contracts;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Contract for estimating the number of tokens a set of messages will consume.
 *
 * Token counting is model specific: every provider tokenizes text differently, and an accurate
 * count generally requires that provider's own tokenizer. The SDK ships a rough, dependency-free
 * default (see {@see \WordPress\AiClient\Providers\Models\Tokenization\HeuristicTokenCounter}); a
 * consumer that has access to an accurate tokenizer (for example a self-hosted server's tokenize
 * endpoint, or a bundled tokenizer library) may implement this interface and supply it via
 * {@see \WordPress\AiClient\Builders\PromptBuilder::usingTokenCounter()}.
 *
 * @since n.e.x.t
 */
interface TokenCounterInterface
{
    /**
     * Estimates the number of tokens the given messages will consume for the given model.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to estimate a token count for.
     * @param ModelMetadata $modelMetadata The metadata of the model the messages are intended for.
     * @return int The estimated number of tokens. Never negative.
     */
    public function countTokens(array $messages, ModelMetadata $modelMetadata): int;
}

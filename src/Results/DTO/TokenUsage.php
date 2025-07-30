<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataValueObject;

/**
 * Represents token usage statistics for an AI operation.
 *
 * This DTO tracks the number of tokens used in prompts and completions,
 * which is important for monitoring usage and costs.
 *
 * @since n.e.x.t
 *
 * @phpstan-type TokenUsageArrayShape array{
 *     promptTokens: int,
 *     completionTokens: int,
 *     totalTokens: int
 * }
 *
 * @extends AbstractDataValueObject<TokenUsageArrayShape>
 */
class TokenUsage extends AbstractDataValueObject
{
    public const KEY_PROMPT_TOKENS = 'promptTokens';
    public const KEY_COMPLETION_TOKENS = 'completionTokens';
    public const KEY_TOTAL_TOKENS = 'totalTokens';
    /**
     * @var int Number of tokens in the prompt.
     */
    private int $promptTokens;

    /**
     * @var int Number of tokens in the completion.
     */
    private int $completionTokens;

    /**
     * @var int Total number of tokens used.
     */
    private int $totalTokens;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param int $promptTokens Number of tokens in the prompt.
     * @param int $completionTokens Number of tokens in the completion.
     * @param int $totalTokens Total number of tokens used.
     */
    public function __construct(int $promptTokens, int $completionTokens, int $totalTokens)
    {
        $this->promptTokens = $promptTokens;
        $this->completionTokens = $completionTokens;
        $this->totalTokens = $totalTokens;
    }

    /**
     * Gets the number of prompt tokens.
     *
     * @since n.e.x.t
     *
     * @return int The prompt token count.
     */
    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    /**
     * Gets the number of completion tokens.
     *
     * @since n.e.x.t
     *
     * @return int The completion token count.
     */
    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    /**
     * Gets the total number of tokens.
     *
     * @since n.e.x.t
     *
     * @return int The total token count.
     */
    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                self::KEY_PROMPT_TOKENS => [
                    'type' => 'integer',
                    'description' => 'Number of tokens in the prompt.',
                ],
                self::KEY_COMPLETION_TOKENS => [
                    'type' => 'integer',
                    'description' => 'Number of tokens in the completion.',
                ],
                self::KEY_TOTAL_TOKENS => [
                    'type' => 'integer',
                    'description' => 'Total number of tokens used.',
                ],
            ],
            'required' => [self::KEY_PROMPT_TOKENS, self::KEY_COMPLETION_TOKENS, self::KEY_TOTAL_TOKENS],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return TokenUsageArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_PROMPT_TOKENS => $this->promptTokens,
            self::KEY_COMPLETION_TOKENS => $this->completionTokens,
            self::KEY_TOTAL_TOKENS => $this->totalTokens,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [
            self::KEY_PROMPT_TOKENS,
            self::KEY_COMPLETION_TOKENS,
            self::KEY_TOTAL_TOKENS
        ]);

        return new self(
            $array[self::KEY_PROMPT_TOKENS],
            $array[self::KEY_COMPLETION_TOKENS],
            $array[self::KEY_TOTAL_TOKENS]
        );
    }
}

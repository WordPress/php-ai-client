<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;

/**
 * Represents token usage statistics for an AI operation.
 *
 * This DTO tracks the number of tokens used in prompts and completions,
 * which is important for monitoring usage and costs.
 *
 * Note that thought tokens are a subset of completion tokens, not additive.
 * In other words: completionTokens - thoughtTokens = tokens of actual output content.
 *
 * @since 0.1.0
 *
 * @phpstan-type TokenUsageArrayShape array{
 *     promptTokens: int|null,
 *     completionTokens: int|null,
 *     totalTokens: int|null,
 *     thoughtTokens?: int|null
 * }
 *
 * @extends AbstractDataTransferObject<TokenUsageArrayShape>
 */
class TokenUsage extends AbstractDataTransferObject
{
    public const KEY_PROMPT_TOKENS = 'promptTokens';
    public const KEY_COMPLETION_TOKENS = 'completionTokens';
    public const KEY_TOTAL_TOKENS = 'totalTokens';
    public const KEY_THOUGHT_TOKENS = 'thoughtTokens';
    /**
     * @var int|null Number of tokens in the prompt.
     */
    private ?int $promptTokens;

    /**
     * @var int|null Number of tokens in the completion, including any thought tokens.
     */
    private ?int $completionTokens;

    /**
     * @var int|null Total number of tokens used.
     */
    private ?int $totalTokens;

    /**
     * @var int|null Number of tokens used for thinking, as a subset of completion tokens.
     */
    private ?int $thoughtTokens;

    /**
     * Constructor.
     *
     * @since 0.1.0
     *
     * @param int|null $promptTokens Number of tokens in the prompt, or null when not provided.
     * @param int|null $completionTokens Number of tokens in the completion, or null when not provided.
     * @param int|null $totalTokens Total number of tokens used, or null when not provided.
     * @param int|null $thoughtTokens Number of tokens used for thinking, as a subset of completion tokens.
     */
    public function __construct(
        ?int $promptTokens = null,
        ?int $completionTokens = null,
        ?int $totalTokens = null,
        ?int $thoughtTokens = null
    ) {
        $this->promptTokens = $promptTokens;
        $this->completionTokens = $completionTokens;
        $this->totalTokens = $totalTokens;
        $this->thoughtTokens = $thoughtTokens;
    }

    /**
     * Gets the number of prompt tokens.
     *
     * @since 0.1.0
     *
     * @return int|null The prompt token count, or null when not provided.
     */
    public function getPromptTokens(): ?int
    {
        return $this->promptTokens;
    }

    /**
     * Gets the number of completion tokens, including any thought tokens.
     *
     * @since 0.1.0
     *
     * @return int|null The completion token count, or null when not provided.
     */
    public function getCompletionTokens(): ?int
    {
        return $this->completionTokens;
    }

    /**
     * Gets the total number of tokens.
     *
     * @since 0.1.0
     *
     * @return int|null The total token count, or null when not provided.
     */
    public function getTotalTokens(): ?int
    {
        return $this->totalTokens;
    }

    /**
     * Gets the number of thought tokens, which is a subset of the completion token count.
     *
     * @since 1.3.0
     *
     * @return int|null The thought token count or null if not available.
     */
    public function getThoughtTokens(): ?int
    {
        return $this->thoughtTokens;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                self::KEY_PROMPT_TOKENS => [
                    'type' => ['integer', 'null'],
                    'description' => 'Number of tokens in the prompt.',
                ],
                self::KEY_COMPLETION_TOKENS => [
                    'type' => ['integer', 'null'],
                    'description' => 'Number of tokens in the completion, including any thought tokens.',
                ],
                self::KEY_TOTAL_TOKENS => [
                    'type' => ['integer', 'null'],
                    'description' => 'Total number of tokens used.',
                ],
                self::KEY_THOUGHT_TOKENS => [
                    'type' => 'integer',
                    'description' => 'Number of tokens used for thinking, as a subset of completion tokens.',
                ],
            ],
            'required' => [self::KEY_PROMPT_TOKENS, self::KEY_COMPLETION_TOKENS, self::KEY_TOTAL_TOKENS],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     *
     * @return TokenUsageArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_PROMPT_TOKENS => $this->promptTokens,
            self::KEY_COMPLETION_TOKENS => $this->completionTokens,
            self::KEY_TOTAL_TOKENS => $this->totalTokens,
        ];

        if ($this->thoughtTokens !== null) {
            $data[self::KEY_THOUGHT_TOKENS] = $this->thoughtTokens;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
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
            $array[self::KEY_TOTAL_TOKENS],
            $array[self::KEY_THOUGHT_TOKENS] ?? null
        );
    }
}

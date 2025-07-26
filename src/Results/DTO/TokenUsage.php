<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;

/**
 * Represents token usage statistics for an AI operation.
 *
 * This DTO tracks the number of tokens used in prompts and completions,
 * which is important for monitoring usage and costs.
 *
 * @since n.e.x.t
 */
class TokenUsage implements WithJsonSchemaInterface
{
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
                'promptTokens' => [
                    'type' => 'integer',
                    'description' => 'Number of tokens in the prompt.',
                ],
                'completionTokens' => [
                    'type' => 'integer',
                    'description' => 'Number of tokens in the completion.',
                ],
                'totalTokens' => [
                    'type' => 'integer',
                    'description' => 'Total number of tokens used.',
                ],
            ],
            'required' => ['promptTokens', 'completionTokens', 'totalTokens'],
        ];
    }
}

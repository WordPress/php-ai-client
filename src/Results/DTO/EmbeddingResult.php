<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Embeddings\DTO\Embedding;
use WordPress\AiClient\Results\Contracts\ResultInterface;

/**
 * Represents the result of an embedding generation operation
 *
 * This DTO contains the generated embeddings along with usage statistics
 * and metadata from the AI provider.
 *
 * @since n.e.x.t
 */
class EmbeddingResult implements ResultInterface
{
    /**
     * @var string Unique identifier for this result
     */
    private string $id;

    /**
     * @var Embedding[] The generated embeddings
     */
    private array $embeddings;

    /**
     * @var TokenUsage Token usage statistics
     */
    private TokenUsage $tokenUsage;

    /**
     * @var array<string, mixed> Provider-specific metadata
     */
    private array $providerMetadata;

    /**
     * Constructor
     *
     * @since n.e.x.t
     * @param string $id Unique identifier for this result
     * @param Embedding[] $embeddings The generated embeddings
     * @param TokenUsage $tokenUsage Token usage statistics
     * @param array<string, mixed> $providerMetadata Provider-specific metadata
     */
    public function __construct(string $id, array $embeddings, TokenUsage $tokenUsage, array $providerMetadata = [])
    {
        $this->id = $id;
        $this->embeddings = $embeddings;
        $this->tokenUsage = $tokenUsage;
        $this->providerMetadata = $providerMetadata;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the generated embeddings
     *
     * @since n.e.x.t
     * @return Embedding[] The embeddings
     */
    public function getEmbeddings(): array
    {
        return $this->embeddings;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getTokenUsage(): TokenUsage
    {
        return $this->tokenUsage;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getProviderMetadata(): array
    {
        return $this->providerMetadata;
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
                'id' => [
                    'type' => 'string',
                    'description' => 'Unique identifier for this result',
                ],
                'embeddings' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'vector' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'number',
                                ],
                            ],
                        ],
                        'required' => ['vector'],
                    ],
                    'description' => 'The generated embeddings',
                ],
                'tokenUsage' => TokenUsage::getJsonSchema(),
                'providerMetadata' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Provider-specific metadata',
                ],
            ],
            'required' => ['id', 'embeddings', 'tokenUsage'],
        ];
    }
}

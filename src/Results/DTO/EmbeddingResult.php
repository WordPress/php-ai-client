<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Embeddings\DTO\Embedding;
use WordPress\AiClient\Results\Contracts\ResultInterface;

/**
 * Represents the result of an embedding generation operation.
 *
 * This DTO contains the generated embeddings along with usage statistics
 * and metadata from the AI provider.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type EmbeddingArrayShape from Embedding
 * @phpstan-import-type TokenUsageArrayShape from TokenUsage
 *
 * @phpstan-type EmbeddingResultArrayShape array{
 *     id: string,
 *     embeddings: array<EmbeddingArrayShape>,
 *     tokenUsage: TokenUsageArrayShape,
 *     providerMetadata?: array<string, mixed>
 * }
 *
 * @extends AbstractDataTransferObject<EmbeddingResultArrayShape>
 */
class EmbeddingResult extends AbstractDataTransferObject implements ResultInterface
{
    public const KEY_ID = 'id';
    public const KEY_EMBEDDINGS = 'embeddings';
    public const KEY_TOKEN_USAGE = 'tokenUsage';
    public const KEY_PROVIDER_METADATA = 'providerMetadata';

    /**
     * @var string Unique identifier for this result.
     */
    private string $id;

    /**
     * @var Embedding[] The generated embeddings.
     */
    private array $embeddings;

    /**
     * @var TokenUsage Token usage statistics.
     */
    private TokenUsage $tokenUsage;

    /**
     * @var array<string, mixed> Provider-specific metadata.
     */
    private array $providerMetadata;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $id Unique identifier for this result.
     * @param Embedding[] $embeddings The generated embeddings.
     * @param TokenUsage $tokenUsage Token usage statistics.
     * @param array<string, mixed> $providerMetadata Provider-specific metadata.
     * @throws InvalidArgumentException If no embeddings provided.
     */
    public function __construct(string $id, array $embeddings, TokenUsage $tokenUsage, array $providerMetadata = [])
    {
        if (empty($embeddings)) {
            throw new InvalidArgumentException('At least one embedding must be provided');
        }

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
     * Gets the generated embeddings.
     *
     * @since n.e.x.t
     *
     * @return Embedding[] The embeddings.
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
                self::KEY_ID => [
                    'type' => 'string',
                    'description' => 'Unique identifier for this result.',
                ],
                self::KEY_EMBEDDINGS => [
                    'type' => 'array',
                    'items' => Embedding::getJsonSchema(),
                    'description' => 'The generated embeddings.',
                ],
                self::KEY_TOKEN_USAGE => TokenUsage::getJsonSchema(),
                self::KEY_PROVIDER_METADATA => [
                    'type' => 'object',
                    'description' => 'Provider-specific metadata.',
                ],
            ],
            'required' => [self::KEY_ID, self::KEY_EMBEDDINGS, self::KEY_TOKEN_USAGE],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return EmbeddingResultArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_ID => $this->id,
            self::KEY_EMBEDDINGS => array_map(fn(Embedding $embedding) => $embedding->toArray(), $this->embeddings),
            self::KEY_TOKEN_USAGE => $this->tokenUsage->toArray(),
            self::KEY_PROVIDER_METADATA => $this->providerMetadata,
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
            self::KEY_ID,
            self::KEY_EMBEDDINGS,
            self::KEY_TOKEN_USAGE,
        ]);

        $embeddings = array_map(
            fn(array $embeddingArray) => Embedding::fromArray($embeddingArray),
            $array[self::KEY_EMBEDDINGS]
        );

        return new self(
            $array[self::KEY_ID],
            $embeddings,
            TokenUsage::fromArray($array[self::KEY_TOKEN_USAGE]),
            $array[self::KEY_PROVIDER_METADATA] ?? []
        );
    }
}
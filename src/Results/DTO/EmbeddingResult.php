<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Embeddings\DTO\Embedding;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\Contracts\ResultInterface;

/**
 * Represents the result of an embedding generation operation.
 *
 * @since 0.2.0
 *
 * @phpstan-import-type EmbeddingArrayShape from Embedding
 * @phpstan-import-type TokenUsageArrayShape from TokenUsage
 * @phpstan-import-type ProviderMetadataArrayShape from ProviderMetadata
 * @phpstan-import-type ModelMetadataArrayShape from ModelMetadata
 *
 * @phpstan-type EmbeddingResultArrayShape array{
 *     id: string,
 *     embeddings: array<EmbeddingArrayShape>,
 *     tokenUsage: TokenUsageArrayShape,
 *     providerMetadata: ProviderMetadataArrayShape,
 *     modelMetadata: ModelMetadataArrayShape,
 *     additionalData?: array<string, mixed>
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
    public const KEY_MODEL_METADATA = 'modelMetadata';
    public const KEY_ADDITIONAL_DATA = 'additionalData';

    /**
     * @var string Unique identifier for this result.
     */
    private string $id;

    /**
     * @var list<Embedding> Embeddings returned by the provider.
     */
    private array $embeddings;

    /**
     * @var TokenUsage Token usage statistics.
     */
    private TokenUsage $tokenUsage;

    /**
     * @var ProviderMetadata Provider metadata.
     */
    private ProviderMetadata $providerMetadata;

    /**
     * @var ModelMetadata Model metadata.
     */
    private ModelMetadata $modelMetadata;

    /**
     * @var array<string, mixed> Provider-specific metadata.
     */
    private array $additionalData;

    /**
     * Constructor.
     *
     * @since 0.2.0
     *
     * @param string $id Unique identifier for this result.
     * @param list<Embedding> $embeddings Embeddings returned by the provider.
     * @param TokenUsage $tokenUsage Token usage statistics.
     * @param ProviderMetadata $providerMetadata Provider metadata.
     * @param ModelMetadata $modelMetadata Model metadata.
     * @param array<string, mixed> $additionalData Provider-specific metadata.
     */
    public function __construct(
        string $id,
        array $embeddings,
        TokenUsage $tokenUsage,
        ProviderMetadata $providerMetadata,
        ModelMetadata $modelMetadata,
        array $additionalData = []
    ) {
        if (empty($embeddings)) {
            throw new InvalidArgumentException('At least one embedding must be provided.');
        }

        if (!array_is_list($embeddings)) {
            throw new InvalidArgumentException('Embeddings must be provided as a list array.');
        }

        $this->id = $id;
        $this->embeddings = $embeddings;
        $this->tokenUsage = $tokenUsage;
        $this->providerMetadata = $providerMetadata;
        $this->modelMetadata = $modelMetadata;
        $this->additionalData = $additionalData;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the embeddings.
     *
     * @since 0.2.0
     *
     * @return list<Embedding> The embeddings.
     */
    public function getEmbeddings(): array
    {
        return $this->embeddings;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    public function getTokenUsage(): TokenUsage
    {
        return $this->tokenUsage;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    public function getProviderMetadata(): ProviderMetadata
    {
        return $this->providerMetadata;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    public function getModelMetadata(): ModelMetadata
    {
        return $this->modelMetadata;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    /**
     * Gets the number of embeddings.
     *
     * @since 0.2.0
     *
     * @return int The number of embeddings.
     */
    public function getEmbeddingCount(): int
    {
        return count($this->embeddings);
    }

    /**
     * Checks if multiple embeddings were returned.
     *
     * @since 0.2.0
     *
     * @return bool True if more than one embedding is available.
     */
    public function hasMultipleEmbeddings(): bool
    {
        return $this->getEmbeddingCount() > 1;
    }

    /**
     * Returns the first embedding vector.
     *
     * @since 0.2.0
     *
     * @return list<float> The first embedding vector.
     */
    public function toVector(): array
    {
        return $this->embeddings[0]->getVector();
    }

    /**
     * Returns all embedding vectors.
     *
     * @since 0.2.0
     *
     * @return list<list<float>> All embedding vectors.
     */
    public function toVectors(): array
    {
        return array_map(
            static fn(Embedding $embedding): array => $embedding->getVector(),
            $this->embeddings
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
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
                    'minItems' => 1,
                    'description' => 'Embeddings returned by the provider.',
                ],
                self::KEY_TOKEN_USAGE => TokenUsage::getJsonSchema(),
                self::KEY_PROVIDER_METADATA => ProviderMetadata::getJsonSchema(),
                self::KEY_MODEL_METADATA => ModelMetadata::getJsonSchema(),
                self::KEY_ADDITIONAL_DATA => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Provider-specific metadata.',
                ],
            ],
            'required' => [
                self::KEY_ID,
                self::KEY_EMBEDDINGS,
                self::KEY_TOKEN_USAGE,
                self::KEY_PROVIDER_METADATA,
                self::KEY_MODEL_METADATA,
            ],
            'additionalProperties' => false,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     *
     * @return EmbeddingResultArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_ID => $this->id,
            self::KEY_EMBEDDINGS => array_map(
                static fn(Embedding $embedding): array => $embedding->toArray(),
                $this->embeddings
            ),
            self::KEY_TOKEN_USAGE => $this->tokenUsage->toArray(),
            self::KEY_PROVIDER_METADATA => $this->providerMetadata->toArray(),
            self::KEY_MODEL_METADATA => $this->modelMetadata->toArray(),
            self::KEY_ADDITIONAL_DATA => $this->additionalData,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [
            self::KEY_ID,
            self::KEY_EMBEDDINGS,
            self::KEY_TOKEN_USAGE,
            self::KEY_PROVIDER_METADATA,
            self::KEY_MODEL_METADATA,
        ]);

        $embeddings = array_values(
            array_map(
                static fn(array $embedding): Embedding => Embedding::fromArray($embedding),
                $array[self::KEY_EMBEDDINGS]
            )
        );

        $additionalData = $array[self::KEY_ADDITIONAL_DATA] ?? [];

        return new self(
            $array[self::KEY_ID],
            $embeddings,
            TokenUsage::fromArray($array[self::KEY_TOKEN_USAGE]),
            ProviderMetadata::fromArray($array[self::KEY_PROVIDER_METADATA]),
            ModelMetadata::fromArray($array[self::KEY_MODEL_METADATA]),
            $additionalData
        );
    }
}

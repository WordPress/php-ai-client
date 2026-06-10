<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\Contracts\ResultInterface;

/**
 * Represents the result of an embedding generation operation.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type TokenUsageArrayShape from TokenUsage
 * @phpstan-import-type ProviderMetadataArrayShape from ProviderMetadata
 * @phpstan-import-type ModelMetadataArrayShape from ModelMetadata
 *
 * @phpstan-type EmbeddingResultArrayShape array{
 *     id: string,
 *     embeddings: list<list<float|int>>,
 *     dimensions: int,
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
    public const KEY_DIMENSIONS = 'dimensions';
    public const KEY_TOKEN_USAGE = 'tokenUsage';
    public const KEY_PROVIDER_METADATA = 'providerMetadata';
    public const KEY_MODEL_METADATA = 'modelMetadata';
    public const KEY_ADDITIONAL_DATA = 'additionalData';

    private string $id;

    /**
     * @var list<Embedding>
     */
    private array $embeddings;

    private int $dimensions;
    private TokenUsage $tokenUsage;
    private ProviderMetadata $providerMetadata;
    private ModelMetadata $modelMetadata;

    /**
     * @var array<string, mixed>
     */
    private array $additionalData;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $id Unique identifier for this result.
     * @param list<Embedding|list<float|int>> $embeddings The generated embedding vectors.
     * @param int $dimensions The vector dimension count.
     * @param TokenUsage $tokenUsage Token usage statistics.
     * @param ProviderMetadata $providerMetadata Provider metadata.
     * @param ModelMetadata $modelMetadata Model metadata.
     * @param array<string, mixed> $additionalData Additional data.
     */
    public function __construct(
        string $id,
        array $embeddings,
        int $dimensions,
        TokenUsage $tokenUsage,
        ProviderMetadata $providerMetadata,
        ModelMetadata $modelMetadata,
        array $additionalData = []
    ) {
        if (empty($embeddings)) {
            throw new InvalidArgumentException('At least one embedding must be provided');
        }

        if ($dimensions < 1) {
            throw new InvalidArgumentException('Embedding dimensions must be greater than zero');
        }

        $normalizedEmbeddings = [];
        foreach ($embeddings as $embedding) {
            if (!$embedding instanceof Embedding) {
                $embedding = new Embedding($embedding, $dimensions);
            } elseif ($embedding->getDimensions() !== $dimensions) {
                throw new InvalidArgumentException('Embedding vector length must match dimensions.');
            }

            $normalizedEmbeddings[] = $embedding;
        }

        $this->id = $id;
        $this->embeddings = $normalizedEmbeddings;
        $this->dimensions = $dimensions;
        $this->tokenUsage = $tokenUsage;
        $this->providerMetadata = $providerMetadata;
        $this->modelMetadata = $modelMetadata;
        $this->additionalData = $additionalData;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the generated embedding vectors.
     *
     * @since n.e.x.t
     *
     * @return list<Embedding> The embeddings.
     */
    public function getEmbeddings(): array
    {
        return $this->embeddings;
    }

    /**
     * Gets the first generated embedding vector.
     *
     * @since n.e.x.t
     *
     * @return Embedding The first embedding.
     */
    public function getEmbedding(): Embedding
    {
        return $this->embeddings[0];
    }

    public function getDimensions(): int
    {
        return $this->dimensions;
    }

    public function getTokenUsage(): TokenUsage
    {
        return $this->tokenUsage;
    }

    public function getProviderMetadata(): ProviderMetadata
    {
        return $this->providerMetadata;
    }

    public function getModelMetadata(): ModelMetadata
    {
        return $this->modelMetadata;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

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
                    'description' => 'Generated embedding vectors.',
                ],
                self::KEY_DIMENSIONS => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'Embedding vector dimensions.',
                ],
                self::KEY_TOKEN_USAGE => TokenUsage::getJsonSchema(),
                self::KEY_PROVIDER_METADATA => ProviderMetadata::getJsonSchema(),
                self::KEY_MODEL_METADATA => ModelMetadata::getJsonSchema(),
                self::KEY_ADDITIONAL_DATA => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Additional provider-specific data.',
                ],
            ],
            'required' => [
                self::KEY_ID,
                self::KEY_EMBEDDINGS,
                self::KEY_DIMENSIONS,
                self::KEY_TOKEN_USAGE,
                self::KEY_PROVIDER_METADATA,
                self::KEY_MODEL_METADATA,
            ],
        ];
    }

    /**
     * @return EmbeddingResultArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_ID => $this->id,
            self::KEY_EMBEDDINGS => array_map(
                static fn (Embedding $embedding): array => $embedding->toArray(),
                $this->embeddings
            ),
            self::KEY_DIMENSIONS => $this->dimensions,
            self::KEY_TOKEN_USAGE => $this->tokenUsage->toArray(),
            self::KEY_PROVIDER_METADATA => $this->providerMetadata->toArray(),
            self::KEY_MODEL_METADATA => $this->modelMetadata->toArray(),
        ];

        if (!empty($this->additionalData)) {
            $data[self::KEY_ADDITIONAL_DATA] = $this->additionalData;
        }

        return $data;
    }

    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [
            self::KEY_ID,
            self::KEY_EMBEDDINGS,
            self::KEY_DIMENSIONS,
            self::KEY_TOKEN_USAGE,
            self::KEY_PROVIDER_METADATA,
            self::KEY_MODEL_METADATA,
        ]);

        return new self(
            $array[self::KEY_ID],
            $array[self::KEY_EMBEDDINGS],
            $array[self::KEY_DIMENSIONS],
            TokenUsage::fromArray($array[self::KEY_TOKEN_USAGE]),
            ProviderMetadata::fromArray($array[self::KEY_PROVIDER_METADATA]),
            ModelMetadata::fromArray($array[self::KEY_MODEL_METADATA]),
            $array[self::KEY_ADDITIONAL_DATA] ?? []
        );
    }
}

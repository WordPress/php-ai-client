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
 *     embeddings: list<list<float>>,
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
     * @var list<list<float>> Embedding vectors.
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
     * @var array<string, mixed> Additional data.
     */
    private array $additionalData;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $id Unique identifier for this result.
     * @param list<list<float>> $embeddings Embedding vectors.
     * @param TokenUsage $tokenUsage Token usage statistics.
     * @param ProviderMetadata $providerMetadata Provider metadata.
     * @param ModelMetadata $modelMetadata Model metadata.
     * @param array<string, mixed> $additionalData Additional data.
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
            throw new InvalidArgumentException('At least one embedding must be provided');
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
     * @since n.e.x.t
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets the embedding vectors.
     *
     * @since n.e.x.t
     *
     * @return list<list<float>> The embedding vectors.
     */
    public function getEmbeddings(): array
    {
        return $this->embeddings;
    }

    /**
     * Gets the first embedding vector.
     *
     * @since n.e.x.t
     *
     * @return list<float> The first embedding vector.
     */
    public function getEmbedding(): array
    {
        return $this->embeddings[0];
    }

    /**
     * Gets the embedding vector dimension.
     *
     * @since n.e.x.t
     *
     * @return int The vector dimension.
     */
    public function getDimensions(): int
    {
        return count($this->embeddings[0]);
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
     * Gets the provider metadata.
     *
     * @since n.e.x.t
     *
     * @return ProviderMetadata The provider metadata.
     */
    public function getProviderMetadata(): ProviderMetadata
    {
        return $this->providerMetadata;
    }

    /**
     * Gets the model metadata.
     *
     * @since n.e.x.t
     *
     * @return ModelMetadata The model metadata.
     */
    public function getModelMetadata(): ModelMetadata
    {
        return $this->modelMetadata;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
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
                self::KEY_ID => ['type' => 'string'],
                self::KEY_EMBEDDINGS => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'array',
                        'items' => ['type' => 'number'],
                    ],
                ],
                self::KEY_TOKEN_USAGE => TokenUsage::getJsonSchema(),
                self::KEY_PROVIDER_METADATA => ProviderMetadata::getJsonSchema(),
                self::KEY_MODEL_METADATA => ModelMetadata::getJsonSchema(),
                self::KEY_ADDITIONAL_DATA => ['type' => 'object'],
            ],
            'required' => [
                self::KEY_ID,
                self::KEY_EMBEDDINGS,
                self::KEY_TOKEN_USAGE,
                self::KEY_PROVIDER_METADATA,
                self::KEY_MODEL_METADATA,
            ],
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
        $data = [
            self::KEY_ID => $this->id,
            self::KEY_EMBEDDINGS => $this->embeddings,
            self::KEY_TOKEN_USAGE => $this->tokenUsage->toArray(),
            self::KEY_PROVIDER_METADATA => $this->providerMetadata->toArray(),
            self::KEY_MODEL_METADATA => $this->modelMetadata->toArray(),
        ];

        if ($this->additionalData !== []) {
            $data[self::KEY_ADDITIONAL_DATA] = $this->additionalData;
        }

        return $data;
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
            self::KEY_PROVIDER_METADATA,
            self::KEY_MODEL_METADATA,
        ]);

        return new self(
            $array[self::KEY_ID],
            $array[self::KEY_EMBEDDINGS],
            TokenUsage::fromArray($array[self::KEY_TOKEN_USAGE]),
            ProviderMetadata::fromArray($array[self::KEY_PROVIDER_METADATA]),
            ModelMetadata::fromArray($array[self::KEY_MODEL_METADATA]),
            $array[self::KEY_ADDITIONAL_DATA] ?? []
        );
    }
}

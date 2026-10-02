<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\Contracts\ResultInterface;

/**
 * Represents the result of a classification operation.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type TokenUsageArrayShape from TokenUsage
 * @phpstan-import-type ProviderMetadataArrayShape from ProviderMetadata
 * @phpstan-import-type ModelMetadataArrayShape from ModelMetadata
 * @phpstan-import-type ClassificationAnswerArrayShape from ClassificationAnswer
 *
 * @phpstan-type ClassificationResultArrayShape array{
 *     id: string,
 *     answers: array<string, ClassificationAnswerArrayShape>,
 *     tokenUsage: TokenUsageArrayShape,
 *     providerMetadata: ProviderMetadataArrayShape,
 *     modelMetadata: ModelMetadataArrayShape,
 *     additionalData?: array<string, mixed>
 * }
 *
 * @extends AbstractDataTransferObject<ClassificationResultArrayShape>
 */
class ClassificationResult extends AbstractDataTransferObject implements ResultInterface
{
    public const KEY_ID = 'id';
    public const KEY_ANSWERS = 'answers';
    public const KEY_TOKEN_USAGE = 'tokenUsage';
    public const KEY_PROVIDER_METADATA = 'providerMetadata';
    public const KEY_MODEL_METADATA = 'modelMetadata';
    public const KEY_ADDITIONAL_DATA = 'additionalData';

    /**
     * @var string Unique identifier for this result.
     */
    private string $id;

    /**
     * @var array<string, ClassificationAnswer> The answers, keyed by question key.
     */
    private array $answers;

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
     * @param array<string, ClassificationAnswer> $answers The answers, keyed by question key.
     * @param TokenUsage $tokenUsage Token usage statistics.
     * @param ProviderMetadata $providerMetadata Provider metadata.
     * @param ModelMetadata $modelMetadata Model metadata.
     * @param array<string, mixed> $additionalData Additional data.
     *
     * @throws InvalidArgumentException If no answers are provided.
     */
    public function __construct(
        string $id,
        array $answers,
        TokenUsage $tokenUsage,
        ProviderMetadata $providerMetadata,
        ModelMetadata $modelMetadata,
        array $additionalData = []
    ) {
        if ($answers === []) {
            throw new InvalidArgumentException('At least one answer must be provided.');
        }

        $this->id = $id;
        $this->answers = $answers;
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
     * Gets the answers.
     *
     * @since n.e.x.t
     *
     * @return array<string, ClassificationAnswer> The answers, keyed by question key.
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    /**
     * Gets the answer to a question.
     *
     * @since n.e.x.t
     *
     * @param string $questionKey The key of the question.
     * @return ClassificationAnswer The answer to the question.
     * @throws InvalidArgumentException If there is no answer for the question.
     */
    public function getAnswer(string $questionKey): ClassificationAnswer
    {
        if (!isset($this->answers[$questionKey])) {
            throw new InvalidArgumentException(
                sprintf('No answer found for question "%s".', $questionKey)
            );
        }

        return $this->answers[$questionKey];
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
    public function getProviderMetadata(): ProviderMetadata
    {
        return $this->providerMetadata;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
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
                self::KEY_ID => [
                    'type' => 'string',
                    'description' => 'Unique identifier for this result.',
                ],
                self::KEY_ANSWERS => [
                    'type' => 'object',
                    'additionalProperties' => ClassificationAnswer::getJsonSchema(),
                    'description' => 'The answers, keyed by question key.',
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
                self::KEY_ANSWERS,
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
     * @return ClassificationResultArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_ID => $this->id,
            self::KEY_ANSWERS => array_map(
                static fn (ClassificationAnswer $answer): array => $answer->toArray(),
                $this->answers
            ),
            self::KEY_TOKEN_USAGE => $this->tokenUsage->toArray(),
            self::KEY_PROVIDER_METADATA => $this->providerMetadata->toArray(),
            self::KEY_MODEL_METADATA => $this->modelMetadata->toArray(),
        ];

        if (!empty($this->additionalData)) {
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
            self::KEY_ANSWERS,
            self::KEY_TOKEN_USAGE,
            self::KEY_PROVIDER_METADATA,
            self::KEY_MODEL_METADATA,
        ]);

        return new self(
            $array[self::KEY_ID],
            array_map(
                static fn (array $answer): ClassificationAnswer => ClassificationAnswer::fromArray($answer),
                $array[self::KEY_ANSWERS]
            ),
            TokenUsage::fromArray($array[self::KEY_TOKEN_USAGE]),
            ProviderMetadata::fromArray($array[self::KEY_PROVIDER_METADATA]),
            ModelMetadata::fromArray($array[self::KEY_MODEL_METADATA]),
            $array[self::KEY_ADDITIONAL_DATA] ?? []
        );
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Results\Contracts\ResultInterface;

/**
 * Represents the result of a text extraction (OCR / document parsing) operation.
 *
 * Unlike {@see GenerativeAiResult}, extraction results are not candidate-based: they hold the
 * structured, per-page content of a processed document. Providers that bill per page report a
 * zero {@see TokenUsage}; the meaningful unit is {@see self::getPageCount()}. The raw decoded
 * provider payload should be preserved under the `raw` key of the additional data.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type TokenUsageArrayShape from TokenUsage
 * @phpstan-import-type ProviderMetadataArrayShape from ProviderMetadata
 * @phpstan-import-type ModelMetadataArrayShape from ModelMetadata
 * @phpstan-import-type ExtractedPageArrayShape from ExtractedPage
 *
 * @phpstan-type TextExtractionResultArrayShape array{
 *     id: string,
 *     pages: list<ExtractedPageArrayShape>,
 *     tokenUsage: TokenUsageArrayShape,
 *     providerMetadata: ProviderMetadataArrayShape,
 *     modelMetadata: ModelMetadataArrayShape,
 *     additionalData?: array<string, mixed>
 * }
 *
 * @extends AbstractDataTransferObject<TextExtractionResultArrayShape>
 */
class TextExtractionResult extends AbstractDataTransferObject implements ResultInterface
{
    public const KEY_ID = 'id';
    public const KEY_PAGES = 'pages';
    public const KEY_TOKEN_USAGE = 'tokenUsage';
    public const KEY_PROVIDER_METADATA = 'providerMetadata';
    public const KEY_MODEL_METADATA = 'modelMetadata';
    public const KEY_ADDITIONAL_DATA = 'additionalData';

    /**
     * @var string Unique identifier for this result.
     */
    private string $id;

    /**
     * @var list<ExtractedPage> The extracted pages.
     */
    private array $pages;

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
     * @var array<string, mixed>
     */
    private array $additionalData;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $id Unique identifier for this result.
     * @param list<ExtractedPage> $pages The extracted pages.
     * @param TokenUsage $tokenUsage Token usage statistics. Page-priced providers report zeros.
     * @param ProviderMetadata $providerMetadata Provider metadata.
     * @param ModelMetadata $modelMetadata Model metadata.
     * @param array<string, mixed> $additionalData Additional data; the raw provider payload
     *                                             should be preserved under the `raw` key.
     */
    public function __construct(
        string $id,
        array $pages,
        TokenUsage $tokenUsage,
        ProviderMetadata $providerMetadata,
        ModelMetadata $modelMetadata,
        array $additionalData = []
    ) {
        if (empty($pages)) {
            throw new InvalidArgumentException('At least one extracted page must be provided.');
        }

        foreach ($pages as $page) {
            if (!$page instanceof ExtractedPage) {
                throw new InvalidArgumentException('All pages must be ExtractedPage instances.');
            }
        }

        $this->id = $id;
        $this->pages = $pages;
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
     * Gets the extracted pages.
     *
     * @since n.e.x.t
     *
     * @return list<ExtractedPage> The pages.
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * Gets the number of pages processed.
     *
     * For page-priced providers this is the billing-relevant unit.
     *
     * @since n.e.x.t
     *
     * @return int The page count.
     */
    public function getPageCount(): int
    {
        return count($this->pages);
    }

    /**
     * Gets the full extracted content as a single markdown string.
     *
     * Pages are joined in order, separated by blank lines.
     *
     * @since n.e.x.t
     *
     * @return string The extracted content.
     */
    public function toMarkdown(): string
    {
        return implode(
            "\n\n",
            array_map(
                static fn (ExtractedPage $page): string => $page->getMarkdown(),
                $this->pages
            )
        );
    }

    /**
     * Gets the full extracted content as a single string.
     *
     * Alias of {@see self::toMarkdown()}.
     *
     * @since n.e.x.t
     *
     * @return string The extracted content.
     */
    public function toText(): string
    {
        return $this->toMarkdown();
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
     * Gets the JSON schema for text extraction results.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The JSON schema.
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
                self::KEY_PAGES => [
                    'type' => 'array',
                    'items' => ExtractedPage::getJsonSchema(),
                    'minItems' => 1,
                    'description' => 'The extracted pages.',
                ],
                self::KEY_TOKEN_USAGE => TokenUsage::getJsonSchema(),
                self::KEY_PROVIDER_METADATA => ProviderMetadata::getJsonSchema(),
                self::KEY_MODEL_METADATA => ModelMetadata::getJsonSchema(),
                self::KEY_ADDITIONAL_DATA => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Additional provider-specific data, including the raw provider payload.',
                ],
            ],
            'required' => [
                self::KEY_ID,
                self::KEY_PAGES,
                self::KEY_TOKEN_USAGE,
                self::KEY_PROVIDER_METADATA,
                self::KEY_MODEL_METADATA,
            ],
        ];
    }

    /**
     * Converts the text extraction result to an array.
     *
     * @since n.e.x.t
     *
     * @return TextExtractionResultArrayShape The text extraction result array.
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_ID => $this->id,
            self::KEY_PAGES => array_map(
                static fn (ExtractedPage $page): array => $page->toArray(),
                $this->pages
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
     * Creates a text extraction result from an array.
     *
     * @since n.e.x.t
     *
     * @param TextExtractionResultArrayShape $array The text extraction result array.
     * @return self The text extraction result instance.
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [
            self::KEY_ID,
            self::KEY_PAGES,
            self::KEY_TOKEN_USAGE,
            self::KEY_PROVIDER_METADATA,
            self::KEY_MODEL_METADATA,
        ]);

        $pages = [];
        foreach ($array[self::KEY_PAGES] as $pageData) {
            $pages[] = ExtractedPage::fromArray($pageData);
        }

        return new self(
            $array[self::KEY_ID],
            $pages,
            TokenUsage::fromArray($array[self::KEY_TOKEN_USAGE]),
            ProviderMetadata::fromArray($array[self::KEY_PROVIDER_METADATA]),
            ModelMetadata::fromArray($array[self::KEY_MODEL_METADATA]),
            $array[self::KEY_ADDITIONAL_DATA] ?? []
        );
    }

    /**
     * Creates a deep clone of this text extraction result.
     *
     * @since n.e.x.t
     */
    public function __clone()
    {
        $clonedPages = [];
        foreach ($this->pages as $page) {
            $clonedPages[] = clone $page;
        }
        $this->pages = $clonedPages;

        $this->tokenUsage = clone $this->tokenUsage;
        $this->providerMetadata = clone $this->providerMetadata;
        $this->modelMetadata = clone $this->modelMetadata;
    }
}

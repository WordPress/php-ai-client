<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;

/**
 * Represents a citation or source attribution for generated text.
 *
 * Citations anchor a span of generated text to its source, which can be
 * either a remote URL or an index into the request's documents array.
 * Offset indices are relative to the owning MessagePart's text property.
 *
 * Providers disagree on what their offsets measure, so `type` records which
 * one produced the citation. Vendor-specific leftovers go in `additionalData`.
 *
 * @since n.e.x.t
 *
 * @phpstan-type CitationArrayShape array{
 *     type?: string|null,
 *     url?: string|null,
 *     documentIndex?: int|null,
 *     title?: string|null,
 *     startIndex?: int|null,
 *     endIndex?: int|null,
 *     quotedText?: string|null,
 *     additionalData?: array<string, mixed>|null
 * }
 *
 * @extends AbstractDataTransferObject<CitationArrayShape>
 */
class Citation extends AbstractDataTransferObject
{
    public const KEY_TYPE = 'type';
    public const KEY_URL = 'url';
    public const KEY_DOCUMENT_INDEX = 'documentIndex';
    public const KEY_TITLE = 'title';
    public const KEY_START_INDEX = 'startIndex';
    public const KEY_END_INDEX = 'endIndex';
    public const KEY_QUOTED_TEXT = 'quotedText';
    public const KEY_ADDITIONAL_DATA = 'additionalData';

    /**
     * @var string|null The provider's location type, e.g. `char_location`.
     */
    private ?string $type;

    /**
     * @var string|null The remote source URL.
     */
    private ?string $url;

    /**
     * @var int|null The index into the request's documents array.
     */
    private ?int $documentIndex;

    /**
     * @var string|null An optional title for the source.
     */
    private ?string $title;

    /**
     * @var int|null The start byte offset into the owning MessagePart's text.
     */
    private ?int $startIndex;

    /**
     * @var int|null The end byte offset into the owning MessagePart's text.
     */
    private ?int $endIndex;

    /**
     * @var string|null The quoted source passage (e.g. Anthropic's cited_text).
     */
    private ?string $quotedText;

    /**
     * @var array<string, mixed>|null Vendor-specific leftovers, keyed by name.
     */
    private ?array $additionalData;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string|null $url The remote source URL.
     * @param int|null $documentIndex The index into the request's documents array.
     * @param string|null $title An optional title for the source.
     * @param int|null $startIndex The start byte offset into the owning part's text.
     * @param int|null $endIndex The end byte offset into the owning part's text.
     * @param string|null $quotedText The quoted source passage.
     * @param string|null $type The provider's location type, e.g. `char_location`.
     * @param array<string, mixed>|null $additionalData Vendor-specific leftovers.
     * @throws InvalidArgumentException If offsets are negative or misordered, or if
     *                                  $additionalData is not a string-keyed map.
     */
    public function __construct(
        ?string $url = null,
        ?int $documentIndex = null,
        ?string $title = null,
        ?int $startIndex = null,
        ?int $endIndex = null,
        ?string $quotedText = null,
        ?string $type = null,
        ?array $additionalData = null
    ) {
        if ($documentIndex !== null && $documentIndex < 0) {
            throw new InvalidArgumentException(
                sprintf('Citation documentIndex must be non-negative, got %d.', $documentIndex)
            );
        }

        if ($startIndex !== null && $startIndex < 0) {
            throw new InvalidArgumentException(
                sprintf('Citation startIndex must be non-negative, got %d.', $startIndex)
            );
        }

        if ($endIndex !== null && $endIndex < 0) {
            throw new InvalidArgumentException(
                sprintf('Citation endIndex must be non-negative, got %d.', $endIndex)
            );
        }

        if ($startIndex !== null && $endIndex !== null && $startIndex > $endIndex) {
            throw new InvalidArgumentException(
                sprintf(
                    'Citation startIndex (%d) must be less than or equal to endIndex (%d).',
                    $startIndex,
                    $endIndex
                )
            );
        }

        if ($additionalData !== null) {
            foreach (array_keys($additionalData) as $key) {
                if (!is_string($key)) {
                    throw new InvalidArgumentException(
                        'Citation additionalData must be a map with string keys.'
                    );
                }
            }
        }

        $this->type = $type;
        $this->url = $url;
        $this->documentIndex = $documentIndex;
        $this->title = $title;
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
        $this->quotedText = $quotedText;
        $this->additionalData = $additionalData;
    }

    /**
     * Gets the provider's location type, verbatim.
     *
     * Tells you which coordinate frame the offsets belong to.
     *
     * @since n.e.x.t
     *
     * @return string|null The location type or null if the provider supplied none.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Gets vendor-specific leftovers that have no normalized counterpart.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed>|null The additional data or null if not set.
     */
    public function getAdditionalData(): ?array
    {
        return $this->additionalData;
    }

    /**
     * Gets the remote source URL.
     *
     * @since n.e.x.t
     *
     * @return string|null The URL or null if the source is a document reference.
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Gets the document index.
     *
     * @since n.e.x.t
     *
     * @return int|null The document index or null if the source is a URL.
     */
    public function getDocumentIndex(): ?int
    {
        return $this->documentIndex;
    }

    /**
     * Gets the title of the source.
     *
     * @since n.e.x.t
     *
     * @return string|null The title or null if not available.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Gets the start byte offset into the owning MessagePart's text.
     *
     * @since n.e.x.t
     *
     * @return int|null The start offset or null if span is not available.
     */
    public function getStartIndex(): ?int
    {
        return $this->startIndex;
    }

    /**
     * Gets the end byte offset into the owning MessagePart's text.
     *
     * @since n.e.x.t
     *
     * @return int|null The end offset or null if span is not available.
     */
    public function getEndIndex(): ?int
    {
        return $this->endIndex;
    }

    /**
     * Gets the quoted source passage.
     *
     * @since n.e.x.t
     *
     * @return string|null The quoted text or null if not available.
     */
    public function getQuotedText(): ?string
    {
        return $this->quotedText;
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
                self::KEY_TYPE => [
                    'type' => ['string', 'null'],
                    'description' => 'The provider\'s location type, e.g. char_location.',
                ],
                self::KEY_URL => [
                    'type' => ['string', 'null'],
                    'description' => 'The remote source URL.',
                ],
                self::KEY_DOCUMENT_INDEX => [
                    'type' => ['integer', 'null'],
                    'minimum' => 0,
                    'description' => 'The index into the request\'s documents array.',
                ],
                self::KEY_TITLE => [
                    'type' => ['string', 'null'],
                    'description' => 'An optional title for the source.',
                ],
                self::KEY_START_INDEX => [
                    'type' => ['integer', 'null'],
                    'minimum' => 0,
                    'description' => 'The start byte offset into the owning MessagePart\'s text.',
                ],
                self::KEY_END_INDEX => [
                    'type' => ['integer', 'null'],
                    'minimum' => 0,
                    'description' => 'The end byte offset into the owning MessagePart\'s text.',
                ],
                self::KEY_QUOTED_TEXT => [
                    'type' => ['string', 'null'],
                    'description' => 'The quoted source passage.',
                ],
                self::KEY_ADDITIONAL_DATA => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Vendor-specific leftovers with no normalized counterpart.',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return CitationArrayShape
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->type !== null) {
            $data[self::KEY_TYPE] = $this->type;
        }

        if ($this->url !== null) {
            $data[self::KEY_URL] = $this->url;
        }

        if ($this->documentIndex !== null) {
            $data[self::KEY_DOCUMENT_INDEX] = $this->documentIndex;
        }

        if ($this->title !== null) {
            $data[self::KEY_TITLE] = $this->title;
        }

        if ($this->startIndex !== null) {
            $data[self::KEY_START_INDEX] = $this->startIndex;
        }

        if ($this->endIndex !== null) {
            $data[self::KEY_END_INDEX] = $this->endIndex;
        }

        if ($this->quotedText !== null) {
            $data[self::KEY_QUOTED_TEXT] = $this->quotedText;
        }

        if ($this->additionalData !== null) {
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
        return new self(
            $array[self::KEY_URL] ?? null,
            $array[self::KEY_DOCUMENT_INDEX] ?? null,
            $array[self::KEY_TITLE] ?? null,
            $array[self::KEY_START_INDEX] ?? null,
            $array[self::KEY_END_INDEX] ?? null,
            $array[self::KEY_QUOTED_TEXT] ?? null,
            $array[self::KEY_TYPE] ?? null,
            $array[self::KEY_ADDITIONAL_DATA] ?? null
        );
    }
}

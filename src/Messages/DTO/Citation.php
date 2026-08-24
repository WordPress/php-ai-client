<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;

/**
 * Represents a citation or source attribution.
 *
 * @since 1.4.0
 *
 * @phpstan-type CitationArrayShape array{
 *     uri: string,
 *     title?: string|null
 * }
 *
 * @extends AbstractDataTransferObject<CitationArrayShape>
 */
class Citation extends AbstractDataTransferObject
{
    public const KEY_URI = 'uri';
    public const KEY_TITLE = 'title';

    /**
     * @var string The source URI or identifier.
     */
    private string $uri;

    /**
     * @var string|null An optional title for the source.
     */
    private ?string $title;

    /**
     * Constructor.
     *
     * @since 1.4.0
     *
     * @param string $uri The source URI or identifier.
     * @param string|null $title An optional title for the source.
     */
    public function __construct(string $uri, ?string $title = null)
    {
        $this->uri = $uri;
        $this->title = $title;
    }

    /**
     * Gets the source URI or identifier.
     *
     * @since 1.4.0
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Gets the title of the source.
     *
     * @since 1.4.0
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.4.0
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                self::KEY_URI => [
                    'type' => 'string',
                    'description' => 'The source URI or identifier.',
                ],
                self::KEY_TITLE => [
                    'type' => ['string', 'null'],
                    'description' => 'An optional title for the source.',
                ],
            ],
            'required' => [self::KEY_URI],
            'additionalProperties' => false,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.4.0
     *
     * @return CitationArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_URI => $this->uri,
        ];

        if ($this->title !== null) {
            $data[self::KEY_TITLE] = $this->title;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since 1.4.0
     */
    public static function fromArray(array $array): self
    {
        return new self(
            $array[self::KEY_URI],
            $array[self::KEY_TITLE] ?? null
        );
    }
}

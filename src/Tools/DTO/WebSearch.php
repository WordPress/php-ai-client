<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataTransferObject;

/**
 * Represents web search configuration for AI models.
 *
 * This DTO defines constraints for web searches that AI models can perform,
 * including allowed and disallowed domains, and a bag of provider specific
 * options for settings that have no portable equivalent.
 *
 * @since 0.1.0
 *
 * @phpstan-type WebSearchArrayShape array{
 *     allowedDomains?: string[],
 *     disallowedDomains?: string[],
 *     providerOptions?: array<string, array<string, mixed>>
 * }
 *
 * @extends AbstractDataTransferObject<WebSearchArrayShape>
 */
class WebSearch extends AbstractDataTransferObject
{
    public const KEY_ALLOWED_DOMAINS = 'allowedDomains';
    public const KEY_DISALLOWED_DOMAINS = 'disallowedDomains';
    public const KEY_PROVIDER_OPTIONS = 'providerOptions';

    /**
     * @var string[] List of domains that are allowed for web search.
     */
    private array $allowedDomains;

    /**
     * @var string[] List of domains that are disallowed for web search.
     */
    private array $disallowedDomains;

    /**
     * @var array<string, array<string, mixed>> Provider specific options, keyed by provider ID.
     */
    private array $providerOptions;

    /**
     * Constructor.
     *
     * @since 0.1.0
     * @since n.e.x.t Adds the optional $providerOptions parameter.
     *
     * @param string[] $allowedDomains List of domains that are allowed for web search.
     * @param string[] $disallowedDomains List of domains that are disallowed for web search.
     * @param array<string, array<string, mixed>> $providerOptions Provider specific web search options, keyed by
     *                                                            provider ID. Only the entry matching the resolved
     *                                                            provider is used.
     * @throws InvalidArgumentException If the provider options are not keyed by provider ID, or an entry is not
     *                                  an array.
     */
    public function __construct(
        array $allowedDomains = [],
        array $disallowedDomains = [],
        array $providerOptions = []
    ) {
        foreach ($providerOptions as $providerId => $options) {
            if (!is_string($providerId) || $providerId === '') {
                throw new InvalidArgumentException(
                    'Web search provider options must be keyed by a non-empty provider ID.'
                );
            }

            if (!is_array($options)) {
                throw new InvalidArgumentException(
                    sprintf('Web search provider options for "%s" must be an array.', $providerId)
                );
            }
        }

        $this->allowedDomains = $allowedDomains;
        $this->disallowedDomains = $disallowedDomains;
        $this->providerOptions = $providerOptions;
    }

    /**
     * Gets the allowed domains.
     *
     * @since 0.1.0
     *
     * @return string[] The allowed domains.
     */
    public function getAllowedDomains(): array
    {
        return $this->allowedDomains;
    }

    /**
     * Gets the disallowed domains.
     *
     * @since 0.1.0
     *
     * @return string[] The disallowed domains.
     */
    public function getDisallowedDomains(): array
    {
        return $this->disallowedDomains;
    }

    /**
     * Gets the provider specific options for all providers.
     *
     * @since n.e.x.t
     *
     * @return array<string, array<string, mixed>> The provider specific options, keyed by provider ID.
     */
    public function getProviderOptions(): array
    {
        return $this->providerOptions;
    }

    /**
     * Gets the provider specific options for a single provider.
     *
     * @since n.e.x.t
     *
     * @param string $providerId The provider ID to get the options for.
     * @return array<string, mixed> The options for the provider, or an empty array if none were provided.
     */
    public function getProviderOptionsFor(string $providerId): array
    {
        return $this->providerOptions[$providerId] ?? [];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                self::KEY_ALLOWED_DOMAINS => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'description' => 'List of domains that are allowed for web search.',
                ],
                self::KEY_DISALLOWED_DOMAINS => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'description' => 'List of domains that are disallowed for web search.',
                ],
                self::KEY_PROVIDER_OPTIONS => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                    ],
                    'description' => 'Provider specific web search options, keyed by provider ID.',
                ],
            ],
            'required' => [],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     *
     * @return WebSearchArrayShape
     */
    public function toArray(): array
    {
        $data = [
            self::KEY_ALLOWED_DOMAINS => $this->allowedDomains,
            self::KEY_DISALLOWED_DOMAINS => $this->disallowedDomains,
        ];

        if ($this->providerOptions !== []) {
            $data[self::KEY_PROVIDER_OPTIONS] = $this->providerOptions;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    public static function fromArray(array $array): self
    {
        return new self(
            $array[self::KEY_ALLOWED_DOMAINS] ?? [],
            $array[self::KEY_DISALLOWED_DOMAINS] ?? [],
            $array[self::KEY_PROVIDER_OPTIONS] ?? []
        );
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchema;

/**
 * Represents web search configuration for AI models
 *
 * This DTO defines constraints for web searches that AI models can perform,
 * including allowed and disallowed domains.
 *
 * @since n.e.x.t
 */
class WebSearch implements WithJsonSchema
{
    /**
     * @var string[] List of domains that are allowed for web search
     */
    private array $allowedDomains;

    /**
     * @var string[] List of domains that are disallowed for web search
     */
    private array $disallowedDomains;

    /**
     * Constructor
     *
     * @since n.e.x.t
     * @param string[] $allowedDomains List of domains that are allowed for web search
     * @param string[] $disallowedDomains List of domains that are disallowed for web search
     */
    public function __construct(array $allowedDomains = [], array $disallowedDomains = [])
    {
        $this->allowedDomains = $allowedDomains;
        $this->disallowedDomains = $disallowedDomains;
    }

    /**
     * Get the allowed domains
     *
     * @since n.e.x.t
     * @return string[] The allowed domains
     */
    public function getAllowedDomains(): array
    {
        return $this->allowedDomains;
    }

    /**
     * Get the disallowed domains
     *
     * @since n.e.x.t
     * @return string[] The disallowed domains
     */
    public function getDisallowedDomains(): array
    {
        return $this->disallowedDomains;
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
                'allowedDomains' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'description' => 'List of domains that are allowed for web search',
                ],
                'disallowedDomains' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'description' => 'List of domains that are disallowed for web search',
                ],
            ],
            'required' => [],
        ];
    }
}

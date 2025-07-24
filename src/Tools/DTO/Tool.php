<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Providers\Enums\ToolTypeEnum;

/**
 * Represents a tool configuration for AI models.
 *
 * Tools allow AI models to perform actions beyond text generation,
 * such as calling functions or performing web searches.
 *
 * @since n.e.x.t
 */
class Tool implements WithJsonSchemaInterface
{
    /**
     * @var ToolTypeEnum The type of tool.
     */
    private ToolTypeEnum $type;

    /**
     * @var FunctionDeclaration[]|null Function declarations (when type is FUNCTION_DECLARATIONS).
     */
    private ?array $functionDeclarations = null;

    /**
     * @var WebSearch|null Web search configuration (when type is WEB_SEARCH).
     */
    private ?WebSearch $webSearch = null;

    /**
     * Private constructor to enforce factory method usage.
     *
     * @since n.e.x.t
     *
     * @param ToolTypeEnum $type The type of tool.
     */
    private function __construct(ToolTypeEnum $type)
    {
        $this->type = $type;
    }

    /**
     * Creates a function declarations tool.
     *
     * @since n.e.x.t
     *
     * @param FunctionDeclaration[] $declarations The function declarations.
     * @return self
     */
    public static function functionDeclarations(array $declarations): self
    {
        $tool = new self(ToolTypeEnum::functionDeclarations());
        $tool->functionDeclarations = $declarations;
        return $tool;
    }

    /**
     * Creates a web search tool.
     *
     * @since n.e.x.t
     *
     * @param WebSearch $webSearch The web search configuration.
     * @return self
     */
    public static function webSearch(WebSearch $webSearch): self
    {
        $tool = new self(ToolTypeEnum::webSearch());
        $tool->webSearch = $webSearch;
        return $tool;
    }

    /**
     * Gets the tool type.
     *
     * @since n.e.x.t
     *
     * @return ToolTypeEnum The tool type.
     */
    public function getType(): ToolTypeEnum
    {
        return $this->type;
    }

    /**
     * Gets the function declarations.
     *
     * @since n.e.x.t
     *
     * @return FunctionDeclaration[]|null The function declarations or null if not a function tool.
     */
    public function getFunctionDeclarations(): ?array
    {
        return $this->functionDeclarations;
    }

    /**
     * Gets the web search configuration.
     *
     * @since n.e.x.t
     *
     * @return WebSearch|null The web search configuration or null if not a web search tool.
     */
    public function getWebSearch(): ?WebSearch
    {
        return $this->webSearch;
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
                'type' => [
                    'type' => 'string',
                    'enum' => ['function_declarations', 'web_search'],
                    'description' => 'The type of tool.',
                ],
                'functionDeclarations' => [
                    'type' => ['array', 'null'],
                    'items' => FunctionDeclaration::getJsonSchema(),
                    'description' => 'Function declarations (when type is function_declarations).',
                ],
                'webSearch' => [
                    'oneOf' => [
                        ['type' => 'null'],
                        WebSearch::getJsonSchema(),
                    ],
                    'description' => 'Web search configuration (when type is web_search).',
                ],
            ],
            'required' => ['type'],
        ];
    }
}

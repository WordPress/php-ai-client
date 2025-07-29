<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataValueObject;
use WordPress\AiClient\Providers\Enums\ToolTypeEnum;

/**
 * Represents a tool configuration for AI models.
 *
 * Tools allow AI models to perform actions beyond text generation,
 * such as calling functions or performing web searches.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type FunctionDeclarationArrayShape from FunctionDeclaration
 * @phpstan-import-type WebSearchArrayShape from WebSearch
 *
 * @phpstan-type ToolArrayShape array{
 *     type: string,
 *     functionDeclarations?: array<FunctionDeclarationArrayShape>,
 *     webSearch?: WebSearchArrayShape
 * }
 *
 * @extends AbstractDataValueObject<ToolArrayShape>
 */
final class Tool extends AbstractDataValueObject
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
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param FunctionDeclaration[]|WebSearch $content The tool content.
     * @throws \InvalidArgumentException If content type is not supported.
     */
    public function __construct($content)
    {
        if (is_array($content)) {
            $this->type = ToolTypeEnum::functionDeclarations();
            $this->functionDeclarations = $content;
        } elseif ($content instanceof WebSearch) {
            $this->type = ToolTypeEnum::webSearch();
            $this->webSearch = $content;
        } else {
            throw new \InvalidArgumentException(
                'Tool content must be an array of FunctionDeclaration instances or a WebSearch instance'
            );
        }
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
            'oneOf' => [
                [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'const' => ToolTypeEnum::functionDeclarations()->value,
                            'description' => 'The type of tool.',
                        ],
                        'functionDeclarations' => [
                            'type' => 'array',
                            'items' => FunctionDeclaration::getJsonSchema(),
                            'description' => 'Function declarations.',
                        ],
                    ],
                    'required' => ['type', 'functionDeclarations'],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'const' => ToolTypeEnum::webSearch()->value,
                            'description' => 'The type of tool.',
                        ],
                        'webSearch' => WebSearch::getJsonSchema(),
                    ],
                    'required' => ['type', 'webSearch'],
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return ToolArrayShape
     */
    public function toArray(): array
    {
        $data = ['type' => $this->type->value];

        if ($this->type->isFunctionDeclarations() && $this->functionDeclarations !== null) {
            $data['functionDeclarations'] = array_map(function (FunctionDeclaration $declaration) {
                return $declaration->toArray();
            }, $this->functionDeclarations);
        } elseif ($this->type->isWebSearch() && $this->webSearch !== null) {
            $data['webSearch'] = $this->webSearch->toArray();
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): Tool
    {
        static::validateFromArrayData($array, ['type']);

        // Check which properties are set to determine how to construct the Tool
        if (isset($array['functionDeclarations'])) {
            $declarations = array_map(function (array $declarationData) {
                return FunctionDeclaration::fromArray($declarationData);
            }, $array['functionDeclarations']);
            return new self($declarations);
        } elseif (isset($array['webSearch'])) {
            return new self(WebSearch::fromArray($array['webSearch']));
        } else {
            throw new InvalidArgumentException(
                'Tool requires either functionDeclarations or webSearch.'
            );
        }
    }
}

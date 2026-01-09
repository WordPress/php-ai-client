<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tools\DTO;

use WordPress\AiClient\Common\AbstractDataTransferObject;

/**
 * Represents code execution configuration for AI models.
 *
 * This DTO defines configuration for code execution/interpreter tools
 * that AI models can use to run code.
 *
 * @since n.e.x.t
 *
 * @phpstan-type CodeExecutionArrayShape array{containerId?: string|null, customOptions?: array<string, mixed>}
 *
 * @extends AbstractDataTransferObject<CodeExecutionArrayShape>
 */
class CodeExecution extends AbstractDataTransferObject
{
    public const KEY_CONTAINER_ID = 'containerId';
    public const KEY_CUSTOM_OPTIONS = 'customOptions';

    /**
     * The container ID for code execution.
     *
     * When null, providers should use their default/auto mode.
     *
     * @var string|null
     */
    private ?string $containerId;

    /**
     * Provider-specific custom options.
     *
     * For OpenAI, this can include options like 'memory_limit' when using auto mode.
     *
     * @var array<string, mixed>
     */
    private array $customOptions;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string|null $containerId The container ID, or null for auto mode.
     * @param array<string, mixed> $customOptions Provider-specific custom options.
     */
    public function __construct(?string $containerId = null, array $customOptions = [])
    {
        $this->containerId = $containerId;
        $this->customOptions = $customOptions;
    }

    /**
     * Gets the container ID.
     *
     * @since n.e.x.t
     *
     * @return string|null The container ID, or null for auto mode.
     */
    public function getContainerId(): ?string
    {
        return $this->containerId;
    }

    /**
     * Gets the custom options.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed> The custom options.
     */
    public function getCustomOptions(): array
    {
        return $this->customOptions;
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
                self::KEY_CONTAINER_ID => [
                    'type' => ['string', 'null'],
                    'description' => 'The container ID for code execution, or null for auto mode.',
                ],
                self::KEY_CUSTOM_OPTIONS => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Provider-specific custom options.',
                ],
            ],
            'required' => [],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return CodeExecutionArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_CONTAINER_ID => $this->containerId,
            self::KEY_CUSTOM_OPTIONS => $this->customOptions,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        return new self(
            $array[self::KEY_CONTAINER_ID] ?? null,
            $array[self::KEY_CUSTOM_OPTIONS] ?? []
        );
    }
}

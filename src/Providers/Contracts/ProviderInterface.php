<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Contracts;

use InvalidArgumentException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;

/**
 * Interface for AI providers.
 *
 * Providers represent AI services (Google, OpenAI, Anthropic, etc.)
 * and provide access to models, metadata, and availability information.
 *
 * @since n.e.x.t
 */
interface ProviderInterface
{
    /**
     * Gets provider metadata.
     *
     * @since n.e.x.t
     *
     * @return ProviderMetadata Provider metadata.
     */
    public static function metadata(): ProviderMetadata;

    /**
     * Creates a model instance.
     *
     * @since n.e.x.t
     *
     * @param string                          $modelId     Model identifier.
     * @param ModelConfig|array<string,mixed> $modelConfig Model configuration.
     * @return ModelInterface Model instance.
     * @throws InvalidArgumentException If model not found or configuration invalid.
     */
    public static function model(string $modelId, $modelConfig = []): ModelInterface;

    /**
     * Gets provider availability checker.
     *
     * @since n.e.x.t
     *
     * @return ProviderAvailabilityInterface Provider availability checker.
     */
    public static function availability(): ProviderAvailabilityInterface;

    /**
     * Gets model metadata directory.
     *
     * @since n.e.x.t
     *
     * @return ModelMetadataDirectoryInterface Model metadata directory.
     */
    public static function modelMetadataDirectory(): ModelMetadataDirectoryInterface;
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Contracts;

use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Interface for AI models.
 *
 * Models represent specific AI models from providers and define
 * their capabilities, configuration, and execution methods.
 *
 * @since 0.1.0
 */
interface ModelInterface
{
    /**
     * Gets model metadata.
     *
     * @since 0.1.0
     *
     * @return ModelMetadata Model metadata.
     */
    public function metadata(): ModelMetadata;

    /**
     * Returns the metadata for the model's provider.
     *
     * @since 0.1.0
     *
     * @return ProviderMetadata The provider metadata.
     */
    public function providerMetadata(): ProviderMetadata;

    /**
     * Sets model configuration.
     *
     * @since 0.1.0
     *
     * @param ModelConfig $config Model configuration.
     * @return void
     */
    public function setConfig(ModelConfig $config): void;

    /**
     * Gets model configuration.
     *
     * @since 0.1.0
     *
     * @return ModelConfig Current model configuration.
     */
    public function getConfig(): ModelConfig;

    /**
     * Returns the supported input and output modalities for this model.
     *
     * The returned array contains two keys: 'input' and 'output', each
     * containing a list of supported ModalityEnum values. An empty list
     * means no modalities of that kind are explicitly declared.
     *
     * @since n.e.x.t
     *
     * @return array{input: list<ModalityEnum>, output: list<ModalityEnum>} Supported modalities.
     */
    public function getCapabilities(): array;

    /**
     * Checks whether the model supports the given input modality.
     *
     * @since n.e.x.t
     *
     * @param ModalityEnum $modality The modality to check.
     * @return bool True if the input modality is supported, false otherwise.
     */
    public function supportsInput(ModalityEnum $modality): bool;

    /**
     * Checks whether the model supports the given output modality.
     *
     * @since n.e.x.t
     *
     * @param ModalityEnum $modality The modality to check.
     * @return bool True if the output modality is supported, false otherwise.
     */
    public function supportsOutput(ModalityEnum $modality): bool;
}

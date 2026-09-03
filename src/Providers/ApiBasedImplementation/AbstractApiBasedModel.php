<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\ApiBasedImplementation;

use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\ApiBasedImplementation\Contracts\ApiBasedModelInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\Contracts\WithHttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\WithRequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Http\Traits\WithHttpTransporterTrait;
use WordPress\AiClient\Providers\Http\Traits\WithRequestAuthenticationTrait;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

/**
 * Base class for an API-based model for a provider.
 *
 * While this class contains no abstract methods, it is still abstract to ensure that each model class can actually
 * perform generative AI tasks by implementing the corresponding interfaces.
 *
 * @since 0.1.0
 */
abstract class AbstractApiBasedModel implements
    ApiBasedModelInterface,
    WithHttpTransporterInterface,
    WithRequestAuthenticationInterface
{
    use WithHttpTransporterTrait;
    use WithRequestAuthenticationTrait;

    /**
     * @var ModelMetadata The metadata for the model.
     */
    private ModelMetadata $metadata;

    /**
     * @var ProviderMetadata The metadata for the model's provider.
     */
    private ProviderMetadata $providerMetadata;

    /**
     * @var ModelConfig The configuration for the model.
     */
    private ModelConfig $config;

    /**
     * @var RequestOptions|null The request options for HTTP transport.
     */
    private ?RequestOptions $requestOptions = null;

    /**
     * Constructor.
     *
     * @since 0.1.0
     *
     * @param ModelMetadata $metadata The metadata for the model.
     * @param ProviderMetadata $providerMetadata The metadata for the model's provider.
     */
    public function __construct(ModelMetadata $metadata, ProviderMetadata $providerMetadata)
    {
        $this->metadata = $metadata;
        $this->providerMetadata = $providerMetadata;
        $this->config = ModelConfig::fromArray([]);
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    final public function metadata(): ModelMetadata
    {
        return $this->metadata;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    final public function providerMetadata(): ProviderMetadata
    {
        return $this->providerMetadata;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    final public function setConfig(ModelConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    final public function getConfig(): ModelConfig
    {
        return $this->config;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.3.0
     */
    final public function setRequestOptions(RequestOptions $requestOptions): void
    {
        $this->requestOptions = $requestOptions;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.3.0
     */
    final public function getRequestOptions(): ?RequestOptions
    {
        return $this->requestOptions;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    final public function getCapabilities(): array
    {
        return [
            'input'  => $this->extractModalities(OptionEnum::inputModalities()),
            'output' => $this->extractModalities(OptionEnum::outputModalities()),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    final public function supportsInput(ModalityEnum $modality): bool
    {
        foreach ($this->extractModalities(OptionEnum::inputModalities()) as $supported) {
            if ($supported->value === $modality->value) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    final public function supportsOutput(ModalityEnum $modality): bool
    {
        foreach ($this->extractModalities(OptionEnum::outputModalities()) as $supported) {
            if ($supported->value === $modality->value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extracts a list of ModalityEnum values from the model metadata for a given option key.
     *
     * Looks up the SupportedOption matching $optionKey in the model's metadata and converts
     * each stored value to a ModalityEnum instance. Values that are not valid ModalityEnum
     * values are silently skipped.
     *
     * @since n.e.x.t
     *
     * @param OptionEnum $optionKey The option key to look up (e.g. inputModalities, outputModalities).
     * @return list<ModalityEnum> The list of modalities, or an empty list if none are defined.
     */
    private function extractModalities(OptionEnum $optionKey): array
    {
        foreach ($this->metadata->getSupportedOptions() as $supportedOption) {
            if ($supportedOption->getName()->value !== $optionKey->value) {
                continue;
            }

            $values = $supportedOption->getSupportedValues();
            if ($values === null) {
                return [];
            }

            $modalities = [];
            foreach ($values as $value) {
                if ($value instanceof ModalityEnum) {
                    $modalities[] = $value;
                } elseif (is_string($value)) {
                    $modalities[] = ModalityEnum::from($value);
                }
            }
            return $modalities;
        }

        return [];
    }
}

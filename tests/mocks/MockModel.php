<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks;

use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\Contracts\WithHttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\WithRequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\Traits\WithHttpTransporterTrait;
use WordPress\AiClient\Providers\Http\Traits\WithRequestAuthenticationTrait;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

/**
 * Mock model for testing.
 */
class MockModel implements ModelInterface, WithHttpTransporterInterface, WithRequestAuthenticationInterface
{
    use WithHttpTransporterTrait;
    use WithRequestAuthenticationTrait;

    /**
     * @var ModelMetadata The model metadata.
     */
    private ModelMetadata $metadata;

    /**
     * @var ModelConfig The model configuration.
     */
    private ModelConfig $config;

    /**
     * Constructor.
     *
     * @param ModelMetadata $metadata The model metadata.
     * @param ModelConfig $config The model configuration.
     */
    public function __construct(ModelMetadata $metadata, ModelConfig $config)
    {
        $this->metadata = $metadata;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function metadata(): ModelMetadata
    {
        return $this->metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function providerMetadata(): ProviderMetadata
    {
        // Return the MockProvider's metadata
        return MockProvider::metadata();
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig(): ModelConfig
    {
        return $this->config;
    }

    /**
     * {@inheritDoc}
     */
    public function setConfig(ModelConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getCapabilities(): array
    {
        return [
            'input'  => $this->extractModalities(OptionEnum::inputModalities()),
            'output' => $this->extractModalities(OptionEnum::outputModalities()),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function supportsInput(ModalityEnum $modality): bool
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
     */
    public function supportsOutput(ModalityEnum $modality): bool
    {
        foreach ($this->extractModalities(OptionEnum::outputModalities()) as $supported) {
            if ($supported->value === $modality->value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extracts modality instances from the metadata's supported options.
     *
     * @param OptionEnum $optionKey The option key to look up.
     * @return list<ModalityEnum> The list of modalities.
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

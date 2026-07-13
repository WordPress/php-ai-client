<?php

declare(strict_types=1);

namespace WordPress\AiClient\Builders;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\ApiBasedImplementation\Contracts\ApiBasedModelInterface;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\ProviderRegistry;

/**
 * Resolves a configured model for a set of requirements.
 *
 * @since n.e.x.t
 */
class ModelResolver
{
    private ProviderRegistry $registry;
    private ?ModelInterface $model = null;
    private ?string $providerIdOrClassName = null;
    /** @var list<string> */
    private array $modelPreferenceKeys = [];
    private ModelConfig $modelConfig;
    private ?RequestOptions $requestOptions = null;

    public function __construct(ProviderRegistry $registry)
    {
        $this->registry = $registry;
        $this->modelConfig = new ModelConfig();
    }

    public function __clone()
    {
        $this->modelConfig = clone $this->modelConfig;
        if ($this->requestOptions !== null) {
            $this->requestOptions = clone $this->requestOptions;
        }
    }

    public function getModelConfig(): ModelConfig
    {
        return $this->modelConfig;
    }

    public function setModelConfig(ModelConfig $modelConfig): void
    {
        $this->modelConfig = $modelConfig;
    }

    public function getModel(): ?ModelInterface
    {
        return $this->model;
    }

    public function usingModel(ModelInterface $model): void
    {
        $this->model = $model;
        $this->modelConfig = ModelConfig::fromArray(array_merge(
            $model->getConfig()->toArray(),
            $this->modelConfig->toArray()
        ));
    }

    public function usingModelConfig(ModelConfig $config): void
    {
        $this->modelConfig = ModelConfig::fromArray(array_merge($config->toArray(), $this->modelConfig->toArray()));
    }

    public function usingProvider(string $providerIdOrClassName): void
    {
        $this->providerIdOrClassName = $providerIdOrClassName;
    }

    /** @param string|ModelInterface|array{0:string,1:string} ...$preferredModels Preferred models. */
    public function usingModelPreference(...$preferredModels): void
    {
        if ($preferredModels === []) {
            throw new InvalidArgumentException('At least one model preference must be provided.');
        }

        $keys = [];
        foreach ($preferredModels as $preferredModel) {
            if (is_array($preferredModel)) {
                if (!array_is_list($preferredModel) || count($preferredModel) !== 2) {
                    throw new InvalidArgumentException(
                        'Model preference tuple must contain model identifier and provider ID.'
                    );
                }
                [$providerId, $modelId] = $preferredModel;
                $providerId = $this->normalize(
                    $providerId,
                    'Model preference provider identifiers cannot be empty.'
                );
                $keys[] = 'providerModel::' . $providerId . '::' . $this->normalize($modelId);
            } elseif ($preferredModel instanceof ModelInterface) {
                $keys[] = sprintf(
                    'providerModel::%s::%s',
                    $preferredModel->providerMetadata()->getId(),
                    $preferredModel->metadata()->getId()
                );
            } elseif (is_string($preferredModel)) {
                $keys[] = 'model::' . $this->normalize($preferredModel);
            } else {
                throw new InvalidArgumentException(
                    'Model preferences must be model identifiers, instances of ModelInterface, ' .
                    'or provider/model tuples.'
                );
            }
        }
        $this->modelPreferenceKeys = $keys;
    }

    public function usingRequestOptions(RequestOptions $requestOptions): void
    {
        $this->requestOptions = $requestOptions;
    }

    public function resolve(ModelRequirements $requirements): ModelInterface
    {
        if ($this->model !== null) {
            $model = $this->model;
            $model->setConfig($this->modelConfig);
            $this->registry->bindModelDependencies($model);
            $this->bindRequestOptions($model);
            return $model;
        }

        $candidates = $this->getCandidates($requirements);
        if ($candidates === []) {
            $prefix = $this->providerIdOrClassName === null
                ? 'No models found that support'
                : sprintf('No models found for provider "%s" that support', $this->providerIdOrClassName);
            throw new InvalidArgumentException(
                sprintf('%s %s for this prompt.', $prefix, $requirements->getRequiredCapabilities()[0]->value)
            );
        }

        foreach ($this->modelPreferenceKeys as $key) {
            if (isset($candidates[$key])) {
                return $this->createModel($candidates[$key]);
            }
        }

        $candidate = reset($candidates);
        assert(is_array($candidate));
        return $this->createModel($candidate);
    }

    /** @return array<string, array{0:string,1:string}> Candidate models by preference key. */
    private function getCandidates(ModelRequirements $requirements): array
    {
        $groups = $this->providerIdOrClassName === null
            ? $this->registry->findModelsMetadataForSupport($requirements)
            : null;
        $candidates = [];

        if ($groups !== null) {
            foreach ($groups as $group) {
                $candidates += $this->mapCandidates($group->getProvider()->getId(), $group->getModels());
            }
            return $candidates;
        }

        assert($this->providerIdOrClassName !== null);
        return $this->mapCandidates(
            $this->registry->getProviderId($this->providerIdOrClassName),
            $this->registry->findProviderModelsMetadataForSupport($this->providerIdOrClassName, $requirements)
        );
    }

    /**
     * @param list<\WordPress\AiClient\Providers\Models\DTO\ModelMetadata> $models Model metadata.
     * @return array<string, array{0:string,1:string}> Candidate models by preference key.
     */
    private function mapCandidates(string $providerId, array $models): array
    {
        $candidates = [];
        foreach ($models as $model) {
            $modelId = $model->getId();
            $candidates['providerModel::' . $providerId . '::' . $modelId] = [$providerId, $modelId];
            $candidates['model::' . $modelId] = [$providerId, $modelId];
        }
        return $candidates;
    }

    /** @param array{0:string,1:string} $candidate Provider and model identifiers. */
    private function createModel(array $candidate): ModelInterface
    {
        [$providerId, $modelId] = $candidate;
        $model = $this->registry->getProviderModel($providerId, $modelId, $this->modelConfig);
        $this->bindRequestOptions($model);
        return $model;
    }

    private function bindRequestOptions(ModelInterface $model): void
    {
        if ($this->requestOptions !== null && $model instanceof ApiBasedModelInterface) {
            $model->setRequestOptions($this->requestOptions);
        }
    }

    /** @param mixed $value The value to normalize. */
    private function normalize($value, string $message = 'Model preference identifiers cannot be empty.'): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException($message);
        }
        return trim($value);
    }
}

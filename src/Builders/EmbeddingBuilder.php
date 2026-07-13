<?php

declare(strict_types=1);

namespace WordPress\AiClient\Builders;

use Psr\EventDispatcher\EventDispatcherInterface;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Events\AfterGenerateEmbeddingEvent;
use WordPress\AiClient\Events\BeforeGenerateEmbeddingEvent;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\EmbeddingGeneration\Contracts\EmbeddingGenerationModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\Embedding;
use WordPress\AiClient\Results\DTO\EmbeddingResult;

/**
 * Fluent builder for transforming text inputs into embeddings.
 *
 * @since n.e.x.t
 */
class EmbeddingBuilder
{
    /** @var list<string> */
    private array $inputs;
    private ModelResolver $modelResolver;
    private ?EventDispatcherInterface $eventDispatcher;

    /** @param list<string> $inputs The text inputs to embed. */
    public function __construct(
        array $inputs,
        ProviderRegistry $registry,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        if (!array_is_list($inputs) || $inputs === []) {
            throw new InvalidArgumentException('Embedding inputs must be a non-empty list array.');
        }
        foreach ($inputs as $input) {
            if (!is_string($input) || trim($input) === '') {
                throw new InvalidArgumentException('Embedding inputs must be non-empty strings.');
            }
        }
        $this->inputs = $inputs;
        $this->modelResolver = new ModelResolver($registry);
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Creates an independent copy of the builder configuration.
     *
     * @since n.e.x.t
     */
    public function __clone()
    {
        $this->modelResolver = clone $this->modelResolver;
    }

    public function usingModel(ModelInterface $model): self
    {
        $this->modelResolver->usingModel($model);
        return $this;
    }
    public function usingModelConfig(ModelConfig $config): self
    {
        $this->modelResolver->usingModelConfig($config);
        return $this;
    }
    /** @param string|ModelInterface|array{0:string,1:string} ...$models Preferred models. */
    public function usingModelPreference(...$models): self
    {
        $this->modelResolver->usingModelPreference(...$models);
        return $this;
    }
    public function usingProvider(string $provider): self
    {
        $this->modelResolver->usingProvider($provider);
        return $this;
    }
    public function usingRequestOptions(RequestOptions $options): self
    {
        $this->modelResolver->usingRequestOptions($options);
        return $this;
    }
    public function usingDimensions(int $dimensions): self
    {
        $this->modelResolver->getModelConfig()->setDimensions($dimensions);
        return $this;
    }

    public function generateEmbeddingResult(): EmbeddingResult
    {
        $capability = CapabilityEnum::embeddingGeneration();
        $model = $this->modelResolver->resolve(new ModelRequirements([$capability], []));
        if (!$model instanceof EmbeddingGenerationModelInterface) {
            throw new RuntimeException(
                sprintf('Model "%s" does not support embedding generation.', $model->metadata()->getId())
            );
        }
        $this->dispatch(new BeforeGenerateEmbeddingEvent($this->inputs, $model, $capability));
        $result = $model->generateEmbeddingResult($this->inputs);
        if (count($result->getEmbeddings()) !== count($this->inputs)) {
            throw new RuntimeException(
                sprintf(
                    'Expected %d embedding(s) from the model, but received %d.',
                    count($this->inputs),
                    count($result->getEmbeddings())
                )
            );
        }
        $this->dispatch(new AfterGenerateEmbeddingEvent($this->inputs, $model, $capability, $result));
        return $result;
    }

    public function generateEmbedding(): Embedding
    {
        if (count($this->inputs) !== 1) {
            throw new InvalidArgumentException(
                'generateEmbedding() requires exactly one input; use generateEmbeddings() for batches.'
            );
        }
        return $this->generateEmbeddingResult()->getEmbedding();
    }

    /** @return list<Embedding> The generated embeddings. */
    public function generateEmbeddings(): array
    {
        return $this->generateEmbeddingResult()->getEmbeddings();
    }

    private function dispatch(object $event): void
    {
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}

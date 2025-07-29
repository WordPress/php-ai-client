<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\DTO;

use WordPress\AiClient\Common\AbstractDataValueObject;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Tools\DTO\Tool;

/**
 * Represents configuration for an AI model.
 *
 * This class allows configuring various parameters for model behavior,
 * including output modalities, system instructions, generation parameters,
 * and tool integrations.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type ToolArrayShape from Tool
 *
 * @phpstan-type ModelConfigArrayShape array{
 *     outputModalities?: array<int, string>,
 *     systemInstruction?: string,
 *     candidateCount?: int,
 *     maxTokens?: int,
 *     temperature?: float,
 *     topP?: float,
 *     topK?: int,
 *     stopSequences?: array<int, string>,
 *     presencePenalty?: float,
 *     frequencyPenalty?: float,
 *     logprobs?: bool,
 *     topLogprobs?: int,
 *     tools?: array<int, ToolArrayShape>,
 *     customOptions?: array<string, mixed>
 * }
 *
 * @extends AbstractDataValueObject<ModelConfigArrayShape>
 */
final class ModelConfig extends AbstractDataValueObject
{
    public const KEY_OUTPUT_MODALITIES = 'outputModalities';
    public const KEY_SYSTEM_INSTRUCTION = 'systemInstruction';
    public const KEY_CANDIDATE_COUNT = 'candidateCount';
    public const KEY_MAX_TOKENS = 'maxTokens';
    public const KEY_TEMPERATURE = 'temperature';
    public const KEY_TOP_P = 'topP';
    public const KEY_TOP_K = 'topK';
    public const KEY_STOP_SEQUENCES = 'stopSequences';
    public const KEY_PRESENCE_PENALTY = 'presencePenalty';
    public const KEY_FREQUENCY_PENALTY = 'frequencyPenalty';
    public const KEY_LOGPROBS = 'logprobs';
    public const KEY_TOP_LOGPROBS = 'topLogprobs';
    public const KEY_TOOLS = 'tools';
    public const KEY_CUSTOM_OPTIONS = 'customOptions';

    /**
     * @var ModalityEnum[]|null Output modalities for the model.
     */
    protected ?array $outputModalities = null;

    /**
     * @var string|null System instruction for the model.
     */
    protected ?string $systemInstruction = null;

    /**
     * @var int|null Number of response candidates to generate.
     */
    protected ?int $candidateCount = null;

    /**
     * @var int|null Maximum number of tokens to generate.
     */
    protected ?int $maxTokens = null;

    /**
     * @var float|null Temperature for randomness (0.0 to 2.0).
     */
    protected ?float $temperature = null;

    /**
     * @var float|null Top-p nucleus sampling parameter.
     */
    protected ?float $topP = null;

    /**
     * @var int|null Top-k sampling parameter.
     */
    protected ?int $topK = null;

    /**
     * @var string[]|null Stop sequences.
     */
    protected ?array $stopSequences = null;

    /**
     * @var float|null Presence penalty for reducing repetition.
     */
    protected ?float $presencePenalty = null;

    /**
     * @var float|null Frequency penalty for reducing repetition.
     */
    protected ?float $frequencyPenalty = null;

    /**
     * @var bool|null Whether to return log probabilities.
     */
    protected ?bool $logprobs = null;

    /**
     * @var int|null Number of top log probabilities to return.
     */
    protected ?int $topLogprobs = null;

    /**
     * @var Tool[]|null Tools available to the model.
     */
    protected ?array $tools = null;

    /**
     * @var array<string, mixed> Custom provider-specific options.
     */
    protected array $customOptions = [];

    /**
     * Sets the output modalities.
     *
     * @since n.e.x.t
     *
     * @param ModalityEnum[] $outputModalities The output modalities.
     */
    public function setOutputModalities(array $outputModalities): void
    {
        $this->outputModalities = $outputModalities;
    }

    /**
     * Gets the output modalities.
     *
     * @since n.e.x.t
     *
     * @return ModalityEnum[]|null The output modalities.
     */
    public function getOutputModalities(): ?array
    {
        return $this->outputModalities;
    }

    /**
     * Sets the system instruction.
     *
     * @since n.e.x.t
     *
     * @param string $systemInstruction The system instruction.
     */
    public function setSystemInstruction(string $systemInstruction): void
    {
        $this->systemInstruction = $systemInstruction;
    }

    /**
     * Gets the system instruction.
     *
     * @since n.e.x.t
     *
     * @return string|null The system instruction.
     */
    public function getSystemInstruction(): ?string
    {
        return $this->systemInstruction;
    }

    /**
     * Sets the candidate count.
     *
     * @since n.e.x.t
     *
     * @param int $candidateCount The candidate count.
     */
    public function setCandidateCount(int $candidateCount): void
    {
        $this->candidateCount = $candidateCount;
    }

    /**
     * Gets the candidate count.
     *
     * @since n.e.x.t
     *
     * @return int|null The candidate count.
     */
    public function getCandidateCount(): ?int
    {
        return $this->candidateCount;
    }

    /**
     * Sets the maximum tokens.
     *
     * @since n.e.x.t
     *
     * @param int $maxTokens The maximum tokens.
     */
    public function setMaxTokens(int $maxTokens): void
    {
        $this->maxTokens = $maxTokens;
    }

    /**
     * Gets the maximum tokens.
     *
     * @since n.e.x.t
     *
     * @return int|null The maximum tokens.
     */
    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    /**
     * Sets the temperature.
     *
     * @since n.e.x.t
     *
     * @param float $temperature The temperature.
     */
    public function setTemperature(float $temperature): void
    {
        $this->temperature = $temperature;
    }

    /**
     * Gets the temperature.
     *
     * @since n.e.x.t
     *
     * @return float|null The temperature.
     */
    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    /**
     * Sets the top-p parameter.
     *
     * @since n.e.x.t
     *
     * @param float $topP The top-p parameter.
     */
    public function setTopP(float $topP): void
    {
        $this->topP = $topP;
    }

    /**
     * Gets the top-p parameter.
     *
     * @since n.e.x.t
     *
     * @return float|null The top-p parameter.
     */
    public function getTopP(): ?float
    {
        return $this->topP;
    }

    /**
     * Sets the top-k parameter.
     *
     * @since n.e.x.t
     *
     * @param int $topK The top-k parameter.
     */
    public function setTopK(int $topK): void
    {
        $this->topK = $topK;
    }

    /**
     * Gets the top-k parameter.
     *
     * @since n.e.x.t
     *
     * @return int|null The top-k parameter.
     */
    public function getTopK(): ?int
    {
        return $this->topK;
    }

    /**
     * Sets the stop sequences.
     *
     * @since n.e.x.t
     *
     * @param string[] $stopSequences The stop sequences.
     */
    public function setStopSequences(array $stopSequences): void
    {
        $this->stopSequences = $stopSequences;
    }

    /**
     * Gets the stop sequences.
     *
     * @since n.e.x.t
     *
     * @return string[]|null The stop sequences.
     */
    public function getStopSequences(): ?array
    {
        return $this->stopSequences;
    }

    /**
     * Sets the presence penalty.
     *
     * @since n.e.x.t
     *
     * @param float $presencePenalty The presence penalty.
     */
    public function setPresencePenalty(float $presencePenalty): void
    {
        $this->presencePenalty = $presencePenalty;
    }

    /**
     * Gets the presence penalty.
     *
     * @since n.e.x.t
     *
     * @return float|null The presence penalty.
     */
    public function getPresencePenalty(): ?float
    {
        return $this->presencePenalty;
    }

    /**
     * Sets the frequency penalty.
     *
     * @since n.e.x.t
     *
     * @param float $frequencyPenalty The frequency penalty.
     */
    public function setFrequencyPenalty(float $frequencyPenalty): void
    {
        $this->frequencyPenalty = $frequencyPenalty;
    }

    /**
     * Gets the frequency penalty.
     *
     * @since n.e.x.t
     *
     * @return float|null The frequency penalty.
     */
    public function getFrequencyPenalty(): ?float
    {
        return $this->frequencyPenalty;
    }

    /**
     * Sets whether to return log probabilities.
     *
     * @since n.e.x.t
     *
     * @param bool $logprobs Whether to return log probabilities.
     */
    public function setLogprobs(bool $logprobs): void
    {
        $this->logprobs = $logprobs;
    }

    /**
     * Gets whether to return log probabilities.
     *
     * @since n.e.x.t
     *
     * @return bool|null Whether to return log probabilities.
     */
    public function getLogprobs(): ?bool
    {
        return $this->logprobs;
    }

    /**
     * Sets the number of top log probabilities to return.
     *
     * @since n.e.x.t
     *
     * @param int $topLogprobs The number of top log probabilities.
     */
    public function setTopLogprobs(int $topLogprobs): void
    {
        $this->topLogprobs = $topLogprobs;
    }

    /**
     * Gets the number of top log probabilities to return.
     *
     * @since n.e.x.t
     *
     * @return int|null The number of top log probabilities.
     */
    public function getTopLogprobs(): ?int
    {
        return $this->topLogprobs;
    }

    /**
     * Sets the tools.
     *
     * @since n.e.x.t
     *
     * @param Tool[] $tools The tools.
     */
    public function setTools(array $tools): void
    {
        $this->tools = $tools;
    }

    /**
     * Gets the tools.
     *
     * @since n.e.x.t
     *
     * @return Tool[]|null The tools.
     */
    public function getTools(): ?array
    {
        return $this->tools;
    }

    /**
     * Sets the custom options.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $customOptions The custom options.
     */
    public function setCustomOptions(array $customOptions): void
    {
        $this->customOptions = $customOptions;
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
                self::KEY_OUTPUT_MODALITIES => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => ModalityEnum::getValues(),
                    ],
                    'description' => 'Output modalities for the model.',
                ],
                self::KEY_SYSTEM_INSTRUCTION => [
                    'type' => 'string',
                    'description' => 'System instruction for the model.',
                ],
                self::KEY_CANDIDATE_COUNT => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'Number of response candidates to generate.',
                ],
                self::KEY_MAX_TOKENS => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'Maximum number of tokens to generate.',
                ],
                self::KEY_TEMPERATURE => [
                    'type' => 'number',
                    'minimum' => 0.0,
                    'maximum' => 2.0,
                    'description' => 'Temperature for randomness.',
                ],
                self::KEY_TOP_P => [
                    'type' => 'number',
                    'minimum' => 0.0,
                    'maximum' => 1.0,
                    'description' => 'Top-p nucleus sampling parameter.',
                ],
                self::KEY_TOP_K => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'Top-k sampling parameter.',
                ],
                self::KEY_STOP_SEQUENCES => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'description' => 'Stop sequences.',
                ],
                self::KEY_PRESENCE_PENALTY => [
                    'type' => 'number',
                    'description' => 'Presence penalty for reducing repetition.',
                ],
                self::KEY_FREQUENCY_PENALTY => [
                    'type' => 'number',
                    'description' => 'Frequency penalty for reducing repetition.',
                ],
                self::KEY_LOGPROBS => [
                    'type' => 'boolean',
                    'description' => 'Whether to return log probabilities.',
                ],
                self::KEY_TOP_LOGPROBS => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'Number of top log probabilities to return.',
                ],
                self::KEY_TOOLS => [
                    'type' => 'array',
                    'items' => Tool::getJsonSchema(),
                    'description' => 'Tools available to the model.',
                ],
                self::KEY_CUSTOM_OPTIONS => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Custom provider-specific options.',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return ModelConfigArrayShape
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->outputModalities !== null) {
            $data[self::KEY_OUTPUT_MODALITIES] = array_values(array_map(
                static function (ModalityEnum $modality): string {
                    return $modality->value;
                },
                $this->outputModalities
            ));
        }

        if ($this->systemInstruction !== null) {
            $data[self::KEY_SYSTEM_INSTRUCTION] = $this->systemInstruction;
        }

        if ($this->candidateCount !== null) {
            $data[self::KEY_CANDIDATE_COUNT] = $this->candidateCount;
        }

        if ($this->maxTokens !== null) {
            $data[self::KEY_MAX_TOKENS] = $this->maxTokens;
        }

        if ($this->temperature !== null) {
            $data[self::KEY_TEMPERATURE] = $this->temperature;
        }

        if ($this->topP !== null) {
            $data[self::KEY_TOP_P] = $this->topP;
        }

        if ($this->topK !== null) {
            $data[self::KEY_TOP_K] = $this->topK;
        }

        if ($this->stopSequences !== null) {
            $data[self::KEY_STOP_SEQUENCES] = array_values($this->stopSequences);
        }

        if ($this->presencePenalty !== null) {
            $data[self::KEY_PRESENCE_PENALTY] = $this->presencePenalty;
        }

        if ($this->frequencyPenalty !== null) {
            $data[self::KEY_FREQUENCY_PENALTY] = $this->frequencyPenalty;
        }

        if ($this->logprobs !== null) {
            $data[self::KEY_LOGPROBS] = $this->logprobs;
        }

        if ($this->topLogprobs !== null) {
            $data[self::KEY_TOP_LOGPROBS] = $this->topLogprobs;
        }

        if ($this->tools !== null) {
            $data[self::KEY_TOOLS] = array_values(array_map(static function (Tool $tool): array {
                return $tool->toArray();
            }, $this->tools));
        }

        $data[self::KEY_CUSTOM_OPTIONS] = $this->customOptions;

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        $config = new self();

        if (isset($array[self::KEY_OUTPUT_MODALITIES])) {
            $config->setOutputModalities(array_map(
                static fn(string $modality): ModalityEnum => ModalityEnum::from($modality),
                $array[self::KEY_OUTPUT_MODALITIES]
            ));
        }

        if (isset($array[self::KEY_SYSTEM_INSTRUCTION])) {
            $config->setSystemInstruction($array[self::KEY_SYSTEM_INSTRUCTION]);
        }

        if (isset($array[self::KEY_CANDIDATE_COUNT])) {
            $config->setCandidateCount($array[self::KEY_CANDIDATE_COUNT]);
        }

        if (isset($array[self::KEY_MAX_TOKENS])) {
            $config->setMaxTokens($array[self::KEY_MAX_TOKENS]);
        }

        if (isset($array[self::KEY_TEMPERATURE])) {
            $config->setTemperature($array[self::KEY_TEMPERATURE]);
        }

        if (isset($array[self::KEY_TOP_P])) {
            $config->setTopP($array[self::KEY_TOP_P]);
        }

        if (isset($array[self::KEY_TOP_K])) {
            $config->setTopK($array[self::KEY_TOP_K]);
        }

        if (isset($array[self::KEY_STOP_SEQUENCES])) {
            $config->setStopSequences(array_values($array[self::KEY_STOP_SEQUENCES]));
        }

        if (isset($array[self::KEY_PRESENCE_PENALTY])) {
            $config->setPresencePenalty($array[self::KEY_PRESENCE_PENALTY]);
        }

        if (isset($array[self::KEY_FREQUENCY_PENALTY])) {
            $config->setFrequencyPenalty($array[self::KEY_FREQUENCY_PENALTY]);
        }

        if (isset($array[self::KEY_LOGPROBS])) {
            $config->setLogprobs($array[self::KEY_LOGPROBS]);
        }

        if (isset($array[self::KEY_TOP_LOGPROBS])) {
            $config->setTopLogprobs($array[self::KEY_TOP_LOGPROBS]);
        }

        if (isset($array[self::KEY_TOOLS])) {
            $config->setTools(array_map(static function (array $toolData): Tool {
                return Tool::fromArray($toolData);
            }, $array[self::KEY_TOOLS]));
        }

        if (isset($array[self::KEY_CUSTOM_OPTIONS])) {
            $config->setCustomOptions($array[self::KEY_CUSTOM_OPTIONS]);
        }

        return $config;
    }
}

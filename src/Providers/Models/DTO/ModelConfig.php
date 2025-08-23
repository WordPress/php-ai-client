<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Files\Enums\MediaOrientationEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\WebSearch;

/**
 * Represents configuration for an AI model.
 *
 * This class allows configuring various parameters for model behavior,
 * including output modalities, system instructions, generation parameters,
 * and tool integrations.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type FunctionDeclarationArrayShape from FunctionDeclaration
 * @phpstan-import-type WebSearchArrayShape from WebSearch
 *
 * @phpstan-type ModelConfigArrayShape array{
 *     outputModalities?: list<string>,
 *     systemInstruction?: string,
 *     candidateCount?: int,
 *     maxTokens?: int,
 *     temperature?: float,
 *     topP?: float,
 *     topK?: int,
 *     stopSequences?: list<string>,
 *     presencePenalty?: float,
 *     frequencyPenalty?: float,
 *     logprobs?: bool,
 *     topLogprobs?: int,
 *     functionDeclarations?: list<FunctionDeclarationArrayShape>,
 *     webSearch?: WebSearchArrayShape,
 *     outputFileType?: string,
 *     outputMimeType?: string,
 *     outputSchema?: array<string, mixed>,
 *     outputMediaOrientation?: string,
 *     outputMediaAspectRatio?: string,
 *     customOptions?: array<string, mixed>
 * }
 *
 * @extends AbstractDataTransferObject<ModelConfigArrayShape>
 */
class ModelConfig extends AbstractDataTransferObject
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
    public const KEY_FUNCTION_DECLARATIONS = 'functionDeclarations';
    public const KEY_WEB_SEARCH = 'webSearch';
    public const KEY_OUTPUT_FILE_TYPE = 'outputFileType';
    public const KEY_OUTPUT_MIME_TYPE = 'outputMimeType';
    public const KEY_OUTPUT_SCHEMA = 'outputSchema';
    public const KEY_OUTPUT_MEDIA_ORIENTATION = 'outputMediaOrientation';
    public const KEY_OUTPUT_MEDIA_ASPECT_RATIO = 'outputMediaAspectRatio';
    public const KEY_CUSTOM_OPTIONS = 'customOptions';

    /**
     * @var list<ModalityEnum>|null Output modalities for the model.
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
     * @var list<string>|null Stop sequences.
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
     * @var list<FunctionDeclaration>|null Function declarations available to the model.
     */
    protected ?array $functionDeclarations = null;

    /**
     * @var WebSearch|null Web search configuration for the model.
     */
    protected ?WebSearch $webSearch = null;

    /**
     * @var FileTypeEnum|null Output file type.
     */
    protected ?FileTypeEnum $outputFileType = null;

    /**
     * @var string|null Output MIME type.
     */
    protected ?string $outputMimeType = null;

    /**
     * @var array<string, mixed>|null Output schema (JSON schema).
     */
    protected ?array $outputSchema = null;

    /**
     * @var MediaOrientationEnum|null Output media orientation.
     */
    protected ?MediaOrientationEnum $outputMediaOrientation = null;

    /**
     * @var string|null Output media aspect ratio (e.g. 3:2, 16:9).
     */
    protected ?string $outputMediaAspectRatio = null;

    /**
     * @var array<string, mixed> Custom provider-specific options.
     */
    protected array $customOptions = [];

    /**
     * Sets the output modalities.
     *
     * @since n.e.x.t
     *
     * @param list<ModalityEnum> $outputModalities The output modalities.
     *
     * @throws InvalidArgumentException If the array is not a list.
     */
    public function setOutputModalities(array $outputModalities): void
    {
        if (!array_is_list($outputModalities)) {
            throw new InvalidArgumentException('Output modalities must be a list array.');
        }

        $this->outputModalities = $outputModalities;
    }

    /**
     * Gets the output modalities.
     *
     * @since n.e.x.t
     *
     * @return list<ModalityEnum>|null The output modalities.
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
     * @param list<string> $stopSequences The stop sequences.
     *
     * @throws InvalidArgumentException If the array is not a list.
     */
    public function setStopSequences(array $stopSequences): void
    {
        if (!array_is_list($stopSequences)) {
            throw new InvalidArgumentException('Stop sequences must be a list array.');
        }

        $this->stopSequences = $stopSequences;
    }

    /**
     * Gets the stop sequences.
     *
     * @since n.e.x.t
     *
     * @return list<string>|null The stop sequences.
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
     * Sets the function declarations.
     *
     * @since n.e.x.t
     *
     * @param list<FunctionDeclaration> $function_declarations The function declarations.
     *
     * @throws InvalidArgumentException If the array is not a list.
     */
    public function setFunctionDeclarations(array $function_declarations): void
    {
        if (!array_is_list($function_declarations)) {
            throw new InvalidArgumentException('Function declarations must be a list array.');
        }

        $this->functionDeclarations = $function_declarations;
    }

    /**
     * Gets the function declarations.
     *
     * @since n.e.x.t
     *
     * @return list<FunctionDeclaration>|null The function declarations.
     */
    public function getFunctionDeclarations(): ?array
    {
        return $this->functionDeclarations;
    }

    /**
     * Sets the web search configuration.
     *
     * @since n.e.x.t
     *
     * @param WebSearch $web_search The web search configuration.
     */
    public function setWebSearch(WebSearch $web_search): void
    {
        $this->webSearch = $web_search;
    }

    /**
     * Gets the web search configuration.
     *
     * @since n.e.x.t
     *
     * @return WebSearch|null The web search configuration.
     */
    public function getWebSearch(): ?WebSearch
    {
        return $this->webSearch;
    }

    /**
     * Sets the output file type.
     *
     * @since n.e.x.t
     *
     * @param FileTypeEnum $outputFileType The output file type.
     */
    public function setOutputFileType(FileTypeEnum $outputFileType): void
    {
        $this->outputFileType = $outputFileType;
    }

    /**
     * Gets the output file type.
     *
     * @since n.e.x.t
     *
     * @return FileTypeEnum|null The output file type.
     */
    public function getOutputFileType(): ?FileTypeEnum
    {
        return $this->outputFileType;
    }

    /**
     * Sets the output MIME type.
     *
     * @since n.e.x.t
     *
     * @param string $outputMimeType The output MIME type.
     */
    public function setOutputMimeType(string $outputMimeType): void
    {
        $this->outputMimeType = $outputMimeType;
    }

    /**
     * Gets the output MIME type.
     *
     * @since n.e.x.t
     *
     * @return string|null The output MIME type.
     */
    public function getOutputMimeType(): ?string
    {
        return $this->outputMimeType;
    }

    /**
     * Sets the output schema.
     *
     * When setting an output schema, this method automatically sets
     * the output MIME type to "application/json" if not already set.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $outputSchema The output schema (JSON schema).
     */
    public function setOutputSchema(array $outputSchema): void
    {
        $this->outputSchema = $outputSchema;

        // Automatically set outputMimeType to application/json when schema is provided
        if ($this->outputMimeType === null) {
            $this->outputMimeType = 'application/json';
        }
    }

    /**
     * Gets the output schema.
     *
     * @since n.e.x.t
     *
     * @return array<string, mixed>|null The output schema.
     */
    public function getOutputSchema(): ?array
    {
        return $this->outputSchema;
    }

    /**
     * Sets the output media orientation.
     *
     * @since n.e.x.t
     *
     * @param MediaOrientationEnum $outputMediaOrientation The output media orientation.
     */
    public function setOutputMediaOrientation(MediaOrientationEnum $outputMediaOrientation): void
    {
        $this->outputMediaOrientation = $outputMediaOrientation;
    }

    /**
     * Gets the output media orientation.
     *
     * @since n.e.x.t
     *
     * @return MediaOrientationEnum|null The output media orientation.
     */
    public function getOutputMediaOrientation(): ?MediaOrientationEnum
    {
        return $this->outputMediaOrientation;
    }

    /**
     * Sets the output media aspect ratio.
     *
     * If set, this supersedes the output media orientation, as it is a more specific configuration.
     *
     * @since n.e.x.t
     *
     * @param string $outputMediaAspectRatio The output media aspect ratio (e.g. 3:2, 16:9).
     */
    public function setOutputMediaAspectRatio(string $outputMediaAspectRatio): void
    {
        if (!preg_match('/^\d+:\d+$/', $outputMediaAspectRatio)) {
            throw new InvalidArgumentException(
                'Output media aspect ratio must be in the format "width:height" (e.g. 3:2, 16:9).'
            );
        }
        $this->outputMediaAspectRatio = $outputMediaAspectRatio;
    }

    /**
     * Gets the output media aspect ratio.
     *
     * @since n.e.x.t
     *
     * @return string|null The output media aspect ratio (e.g. 3:2, 16:9).
     */
    public function getOutputMediaAspectRatio(): ?string
    {
        return $this->outputMediaAspectRatio;
    }

    /**
     * Sets a single custom option.
     *
     * @since n.e.x.t
     *
     * @param string $key   The option key.
     * @param mixed  $value The option value.
     */
    public function setCustomOption(string $key, $value): void
    {
        $this->customOptions[$key] = $value;
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
                self::KEY_FUNCTION_DECLARATIONS => [
                    'type' => 'array',
                    'items' => FunctionDeclaration::getJsonSchema(),
                    'description' => 'Function declarations available to the model.',
                ],
                self::KEY_WEB_SEARCH => WebSearch::getJsonSchema(),
                self::KEY_OUTPUT_FILE_TYPE => [
                    'type' => 'string',
                    'enum' => FileTypeEnum::getValues(),
                    'description' => 'Output file type.',
                ],
                self::KEY_OUTPUT_MIME_TYPE => [
                    'type' => 'string',
                    'description' => 'Output MIME type.',
                ],
                self::KEY_OUTPUT_SCHEMA => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Output schema (JSON schema).',
                ],
                self::KEY_OUTPUT_MEDIA_ORIENTATION => [
                    'type' => 'string',
                    'enum' => MediaOrientationEnum::getValues(),
                    'description' => 'Output media orientation.',
                ],
                self::KEY_OUTPUT_MEDIA_ASPECT_RATIO => [
                    'type' => 'string',
                    'pattern' => '^\d+:\d+$',
                    'description' => 'Output media aspect ratio.',
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
            $data[self::KEY_OUTPUT_MODALITIES] = array_map(
                static function (ModalityEnum $modality): string {
                    return $modality->value;
                },
                $this->outputModalities
            );
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
            $data[self::KEY_STOP_SEQUENCES] = $this->stopSequences;
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

        if ($this->functionDeclarations !== null) {
            $data[self::KEY_FUNCTION_DECLARATIONS] = array_map(
                static function (FunctionDeclaration $function_declaration): array {
                    return $function_declaration->toArray();
                },
                $this->functionDeclarations
            );
        }

        if ($this->webSearch !== null) {
            $data[self::KEY_WEB_SEARCH] = $this->webSearch->toArray();
        }

        if ($this->outputFileType !== null) {
            $data[self::KEY_OUTPUT_FILE_TYPE] = $this->outputFileType->value;
        }

        if ($this->outputMimeType !== null) {
            $data[self::KEY_OUTPUT_MIME_TYPE] = $this->outputMimeType;
        }

        if ($this->outputSchema !== null) {
            $data[self::KEY_OUTPUT_SCHEMA] = $this->outputSchema;
        }

        if ($this->outputMediaOrientation !== null) {
            $data[self::KEY_OUTPUT_MEDIA_ORIENTATION] = $this->outputMediaOrientation->value;
        }

        if ($this->outputMediaAspectRatio !== null) {
            $data[self::KEY_OUTPUT_MEDIA_ASPECT_RATIO] = $this->outputMediaAspectRatio;
        }

        $data[self::KEY_CUSTOM_OPTIONS] = $this->customOptions;

        return $data;
    }

    /**
     * Converts the model configuration to required options.
     *
     * @since n.e.x.t
     *
     * @return list<RequiredOption> The required options.
     */
    public function toRequiredOptions(): array
    {
        $requiredOptions = [];

        // Map properties that have corresponding OptionEnum values
        if ($this->outputModalities !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::outputModalities()->value,
                $this->outputModalities
            );
        }

        if ($this->systemInstruction !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::systemInstruction()->value,
                $this->systemInstruction
            );
        }

        if ($this->candidateCount !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::candidateCount()->value,
                $this->candidateCount
            );
        }

        if ($this->maxTokens !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::maxTokens()->value,
                $this->maxTokens
            );
        }

        if ($this->temperature !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::temperature()->value,
                $this->temperature
            );
        }

        if ($this->topP !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::topP()->value,
                $this->topP
            );
        }

        if ($this->topK !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::topK()->value,
                $this->topK
            );
        }

        if ($this->outputMimeType !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::outputMimeType()->value,
                $this->outputMimeType
            );
        }

        if ($this->outputSchema !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::outputSchema()->value,
                $this->outputSchema
            );
        }

        // Handle properties without OptionEnum values as custom options
        // These would need to be handled specially by providers
        if ($this->stopSequences !== null) {
            $requiredOptions[] = new RequiredOption('stop_sequences', $this->stopSequences);
        }

        if ($this->presencePenalty !== null) {
            $requiredOptions[] = new RequiredOption('presence_penalty', $this->presencePenalty);
        }

        if ($this->frequencyPenalty !== null) {
            $requiredOptions[] = new RequiredOption('frequency_penalty', $this->frequencyPenalty);
        }

        if ($this->logprobs !== null) {
            $requiredOptions[] = new RequiredOption('logprobs', $this->logprobs);
        }

        if ($this->topLogprobs !== null) {
            $requiredOptions[] = new RequiredOption('top_logprobs', $this->topLogprobs);
        }

        if ($this->functionDeclarations !== null) {
            $requiredOptions[] = new RequiredOption('function_declarations', true);
        }

        if ($this->webSearch !== null) {
            $requiredOptions[] = new RequiredOption('web_search', true);
        }

        if ($this->outputFileType !== null) {
            $requiredOptions[] = new RequiredOption('output_file_type', $this->outputFileType->value);
        }

        if ($this->outputMediaOrientation !== null) {
            $requiredOptions[] = new RequiredOption('output_media_orientation', $this->outputMediaOrientation->value);
        }

        if ($this->outputMediaAspectRatio !== null) {
            $requiredOptions[] = new RequiredOption('output_media_aspect_ratio', $this->outputMediaAspectRatio);
        }

        // Add custom options as individual RequiredOptions
        foreach ($this->customOptions as $key => $value) {
            $requiredOptions[] = new RequiredOption($key, $value);
        }

        return $requiredOptions;
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
            $config->setStopSequences($array[self::KEY_STOP_SEQUENCES]);
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

        if (isset($array[self::KEY_FUNCTION_DECLARATIONS])) {
            $config->setFunctionDeclarations(array_map(
                static function (array $function_declaration_data): FunctionDeclaration {
                    return FunctionDeclaration::fromArray($function_declaration_data);
                },
                $array[self::KEY_FUNCTION_DECLARATIONS]
            ));
        }

        if (isset($array[self::KEY_WEB_SEARCH])) {
            $config->setWebSearch(WebSearch::fromArray($array[self::KEY_WEB_SEARCH]));
        }

        if (isset($array[self::KEY_OUTPUT_FILE_TYPE])) {
            $config->setOutputFileType(FileTypeEnum::from($array[self::KEY_OUTPUT_FILE_TYPE]));
        }

        if (isset($array[self::KEY_OUTPUT_MIME_TYPE])) {
            $config->setOutputMimeType($array[self::KEY_OUTPUT_MIME_TYPE]);
        }

        if (isset($array[self::KEY_OUTPUT_SCHEMA])) {
            $config->setOutputSchema($array[self::KEY_OUTPUT_SCHEMA]);
        }

        if (isset($array[self::KEY_OUTPUT_MEDIA_ORIENTATION])) {
            $config->setOutputMediaOrientation(MediaOrientationEnum::from($array[self::KEY_OUTPUT_MEDIA_ORIENTATION]));
        }

        if (isset($array[self::KEY_OUTPUT_MEDIA_ASPECT_RATIO])) {
            $config->setOutputMediaAspectRatio($array[self::KEY_OUTPUT_MEDIA_ASPECT_RATIO]);
        }

        if (isset($array[self::KEY_CUSTOM_OPTIONS])) {
            $config->setCustomOptions($array[self::KEY_CUSTOM_OPTIONS]);
        }

        return $config;
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Enums;

use WordPress\AiClient\Common\AbstractEnum;

/**
 * Enum for model options.
 *
 * @since n.e.x.t
 *
 * @method static self inputModalities() Creates an instance for INPUT_MODALITIES option.
 * @method static self outputModalities() Creates an instance for OUTPUT_MODALITIES option.
 * @method static self systemInstruction() Creates an instance for SYSTEM_INSTRUCTION option.
 * @method static self candidateCount() Creates an instance for CANDIDATE_COUNT option.
 * @method static self maxTokens() Creates an instance for MAX_TOKENS option.
 * @method static self temperature() Creates an instance for TEMPERATURE option.
 * @method static self topK() Creates an instance for TOP_K option.
 * @method static self topP() Creates an instance for TOP_P option.
 * @method static self outputMimeType() Creates an instance for OUTPUT_MIME_TYPE option.
 * @method static self outputSchema() Creates an instance for OUTPUT_SCHEMA option.
 * @method bool isInputModalities() Checks if the option is INPUT_MODALITIES.
 * @method bool isOutputModalities() Checks if the option is OUTPUT_MODALITIES.
 * @method bool isSystemInstruction() Checks if the option is SYSTEM_INSTRUCTION.
 * @method bool isCandidateCount() Checks if the option is CANDIDATE_COUNT.
 * @method bool isMaxTokens() Checks if the option is MAX_TOKENS.
 * @method bool isTemperature() Checks if the option is TEMPERATURE.
 * @method bool isTopK() Checks if the option is TOP_K.
 * @method bool isTopP() Checks if the option is TOP_P.
 * @method bool isOutputMimeType() Checks if the option is OUTPUT_MIME_TYPE.
 * @method bool isOutputSchema() Checks if the option is OUTPUT_SCHEMA.
 */
class OptionEnum extends AbstractEnum
{
    /**
     * Input modalities option.
     */
    public const INPUT_MODALITIES = 'input_modalities';

    /**
     * Output modalities option.
     */
    public const OUTPUT_MODALITIES = 'output_modalities';

    /**
     * System instruction option.
     */
    public const SYSTEM_INSTRUCTION = 'system_instruction';

    /**
     * Candidate count option.
     */
    public const CANDIDATE_COUNT = 'candidate_count';

    /**
     * Maximum tokens option.
     */
    public const MAX_TOKENS = 'max_tokens';

    /**
     * Temperature option.
     */
    public const TEMPERATURE = 'temperature';

    /**
     * Top K option.
     */
    public const TOP_K = 'top_k';

    /**
     * Top P option.
     */
    public const TOP_P = 'top_p';

    /**
     * Output MIME type option.
     */
    public const OUTPUT_MIME_TYPE = 'output_mime_type';

    /**
     * Output schema option.
     */
    public const OUTPUT_SCHEMA = 'output_schema';
}

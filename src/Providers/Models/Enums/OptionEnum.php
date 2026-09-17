<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Enums;

use ReflectionClass;
use WordPress\AiClient\Common\AbstractEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;

/**
 * Enum for model options.
 *
 * This enum dynamically includes all options from ModelConfig KEY_* constants
 * in addition to the explicitly defined constants below. Values folded in from
 * ModelConfig are copied verbatim and are therefore camelCase, not snake_case.
 *
 * Explicitly defined option (also defined in ModelConfig, which takes precedence):
 * @method static self inputModalities() Creates an instance for INPUT_MODALITIES option.
 * @method bool isInputModalities() Checks if the option is INPUT_MODALITIES.
 *
 * Dynamically loaded from ModelConfig KEY_* constants:
 * @method static self candidateCount() Creates an instance for CANDIDATE_COUNT option.
 * @method static self customOptions() Creates an instance for CUSTOM_OPTIONS option.
 * @method static self dimensions() Creates an instance for DIMENSIONS option.
 * @method static self frequencyPenalty() Creates an instance for FREQUENCY_PENALTY option.
 * @method static self functionDeclarations() Creates an instance for FUNCTION_DECLARATIONS option.
 * @method static self logprobs() Creates an instance for LOGPROBS option.
 * @method static self maxTokens() Creates an instance for MAX_TOKENS option.
 * @method static self outputFileType() Creates an instance for OUTPUT_FILE_TYPE option.
 * @method static self outputMediaAspectRatio() Creates an instance for OUTPUT_MEDIA_ASPECT_RATIO option.
 * @method static self outputMediaOrientation() Creates an instance for OUTPUT_MEDIA_ORIENTATION option.
 * @method static self outputMimeType() Creates an instance for OUTPUT_MIME_TYPE option.
 * @method static self outputModalities() Creates an instance for OUTPUT_MODALITIES option.
 * @method static self outputSchema() Creates an instance for OUTPUT_SCHEMA option.
 * @method static self outputSpeechVoice() Creates an instance for OUTPUT_SPEECH_VOICE option.
 * @method static self presencePenalty() Creates an instance for PRESENCE_PENALTY option.
 * @method static self stopSequences() Creates an instance for STOP_SEQUENCES option.
 * @method static self systemInstruction() Creates an instance for SYSTEM_INSTRUCTION option.
 * @method static self temperature() Creates an instance for TEMPERATURE option.
 * @method static self topK() Creates an instance for TOP_K option.
 * @method static self topLogprobs() Creates an instance for TOP_LOGPROBS option.
 * @method static self topP() Creates an instance for TOP_P option.
 * @method static self webSearch() Creates an instance for WEB_SEARCH option.
 * @method bool isCandidateCount() Checks if the option is CANDIDATE_COUNT.
 * @method bool isCustomOptions() Checks if the option is CUSTOM_OPTIONS.
 * @method bool isDimensions() Checks if the option is DIMENSIONS.
 * @method bool isFrequencyPenalty() Checks if the option is FREQUENCY_PENALTY.
 * @method bool isFunctionDeclarations() Checks if the option is FUNCTION_DECLARATIONS.
 * @method bool isLogprobs() Checks if the option is LOGPROBS.
 * @method bool isMaxTokens() Checks if the option is MAX_TOKENS.
 * @method bool isOutputFileType() Checks if the option is OUTPUT_FILE_TYPE.
 * @method bool isOutputMediaAspectRatio() Checks if the option is OUTPUT_MEDIA_ASPECT_RATIO.
 * @method bool isOutputMediaOrientation() Checks if the option is OUTPUT_MEDIA_ORIENTATION.
 * @method bool isOutputMimeType() Checks if the option is OUTPUT_MIME_TYPE.
 * @method bool isOutputModalities() Checks if the option is OUTPUT_MODALITIES.
 * @method bool isOutputSchema() Checks if the option is OUTPUT_SCHEMA.
 * @method bool isOutputSpeechVoice() Checks if the option is OUTPUT_SPEECH_VOICE.
 * @method bool isPresencePenalty() Checks if the option is PRESENCE_PENALTY.
 * @method bool isStopSequences() Checks if the option is STOP_SEQUENCES.
 * @method bool isSystemInstruction() Checks if the option is SYSTEM_INSTRUCTION.
 * @method bool isTemperature() Checks if the option is TEMPERATURE.
 * @method bool isTopK() Checks if the option is TOP_K.
 * @method bool isTopLogprobs() Checks if the option is TOP_LOGPROBS.
 * @method bool isTopP() Checks if the option is TOP_P.
 * @method bool isWebSearch() Checks if the option is WEB_SEARCH.
 *
 * @since 0.1.0
 */
class OptionEnum extends AbstractEnum
{
    /**
     * Input modalities option.
     *
     * Input modalities are derived from message content rather than configured
     * directly, but ModelConfig still defines KEY_INPUT_MODALITIES for model
     * discovery. That constant is folded in by
     * {@see self::determineClassEnumerations()} and takes precedence over this
     * one, so the effective value of this option is 'inputModalities'.
     */
    public const INPUT_MODALITIES = 'input_modalities';

    /**
     * Determines the class enumerations by reflecting on class constants.
     *
     * Overrides the parent method to dynamically add constants from ModelConfig
     * that are prefixed with KEY_. The enum constant name is the ModelConfig
     * constant name without that prefix, while the value is copied from
     * ModelConfig verbatim and is therefore camelCase. For example,
     * KEY_FUNCTION_DECLARATIONS becomes FUNCTION_DECLARATIONS with the value
     * 'functionDeclarations'.
     *
     * @since 0.1.0
     *
     * @param class-string $className The fully qualified class name.
     * @return array<string, string> The enum constants.
     */
    protected static function determineClassEnumerations(string $className): array
    {
        // Start with the constants defined in this class using parent method
        $constants = parent::determineClassEnumerations($className);

        // Use reflection to get all constants from ModelConfig
        $modelConfigReflection = new ReflectionClass(ModelConfig::class);
        $modelConfigConstants = $modelConfigReflection->getConstants();

        // Add ModelConfig constants that start with KEY_
        foreach ($modelConfigConstants as $constantName => $constantValue) {
            if (str_starts_with($constantName, 'KEY_')) {
                // Remove KEY_ prefix to get the enum constant name
                $enumConstantName = substr($constantName, 4);

                // The value is copied from ModelConfig verbatim, which stores
                // these as camelCase strings
                if (is_string($constantValue)) {
                    $constants[$enumConstantName] = $constantValue;
                }
            }
        }

        return $constants;
    }
}

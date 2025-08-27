<?php

declare(strict_types=1);

namespace WordPress\AiClient\Utils;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\DTO\RequiredOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

/**
 * Utility class for building model requirements from various contexts.
 *
 * This class centralizes the logic for analyzing messages, configurations,
 * and other contexts to infer what capabilities and options a model must
 * support to handle a particular AI operation.
 *
 * @since n.e.x.t
 */
class RequirementsUtil
{
    /**
     * Builds model requirements from a list of messages and configuration.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to analyze.
     * @param CapabilityEnum $primaryCapability The primary capability required.
     * @param ModelConfig|null $modelConfig Optional model configuration.
     * @return ModelRequirements The inferred requirements.
     */
    public static function fromMessages(
        array $messages,
        CapabilityEnum $primaryCapability,
        ?ModelConfig $modelConfig = null
    ): ModelRequirements {
        $capabilities = [$primaryCapability];
        $requiredOptions = [];

        // Analyze message context
        $messageAnalysis = self::analyzeMessages($messages);

        // Add chat history capability if multiple messages
        if ($messageAnalysis['requiresChatHistory']) {
            $capabilities[] = CapabilityEnum::chatHistory();
        }

        // Add input modalities requirement
        if (!empty($messageAnalysis['inputModalities'])) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::inputModalities(),
                $messageAnalysis['inputModalities']
            );
        }

        // Add function calling requirement if needed
        if ($messageAnalysis['requiresFunctionCalling']) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::functionDeclarations(),
                true
            );
        }

        // Include requirements from model configuration
        if ($modelConfig !== null) {
            $configRequirements = $modelConfig->toRequiredOptions();
            $requiredOptions = self::mergeRequiredOptions($requiredOptions, $configRequirements);
        }

        return new ModelRequirements($capabilities, $requiredOptions);
    }

    /**
     * Builds basic model requirements for a single capability.
     *
     * @since n.e.x.t
     *
     * @param CapabilityEnum $capability The required capability.
     * @param ModelConfig|null $modelConfig Optional model configuration.
     * @return ModelRequirements The basic requirements.
     */
    public static function basic(CapabilityEnum $capability, ?ModelConfig $modelConfig = null): ModelRequirements
    {
        $requiredOptions = [];

        if ($modelConfig !== null) {
            $requiredOptions = $modelConfig->toRequiredOptions();
        }

        return new ModelRequirements([$capability], $requiredOptions);
    }

    /**
     * Analyzes a list of messages to determine requirements context.
     *
     * @since n.e.x.t
     *
     * @param list<Message> $messages The messages to analyze.
     * @return array{
     *     requiresChatHistory: bool,
     *     inputModalities: list<ModalityEnum>,
     *     requiresFunctionCalling: bool,
     *     hasTextInput: bool,
     *     hasFileInput: bool
     * } Analysis results.
     */
    public static function analyzeMessages(array $messages): array
    {
        $inputModalities = [];
        $hasFunctionMessageParts = false;
        $hasTextInput = false;
        $hasFileInput = false;

        foreach ($messages as $message) {
            foreach ($message->getParts() as $part) {
                // Check for text input
                if ($part->getType()->isText()) {
                    $hasTextInput = true;
                    $inputModalities[] = ModalityEnum::text();
                }

                // Check for file inputs
                if ($part->getType()->isFile()) {
                    $hasFileInput = true;
                    $file = $part->getFile();

                    if ($file !== null) {
                        if ($file->isImage()) {
                            $inputModalities[] = ModalityEnum::image();
                        } elseif ($file->isAudio()) {
                            $inputModalities[] = ModalityEnum::audio();
                        } elseif ($file->isVideo()) {
                            $inputModalities[] = ModalityEnum::video();
                        } elseif ($file->isDocument() || $file->isText()) {
                            $inputModalities[] = ModalityEnum::document();
                        }
                    }
                }

                // Check for function calls/responses
                if ($part->getType()->isFunctionCall() || $part->getType()->isFunctionResponse()) {
                    $hasFunctionMessageParts = true;
                }
            }
        }

        return [
            'requiresChatHistory' => count($messages) > 1,
            'inputModalities' => array_values(array_unique($inputModalities, SORT_REGULAR)),
            'requiresFunctionCalling' => $hasFunctionMessageParts,
            'hasTextInput' => $hasTextInput,
            'hasFileInput' => $hasFileInput,
        ];
    }

    /**
     * Merges two arrays of required options, avoiding duplicates.
     *
     * @since n.e.x.t
     *
     * @param list<RequiredOption> $existing Existing required options.
     * @param list<RequiredOption> $new New required options to merge.
     * @return list<RequiredOption> Merged required options.
     */
    public static function mergeRequiredOptions(array $existing, array $new): array
    {
        $merged = $existing;

        foreach ($new as $newOption) {
            $merged = self::includeInRequiredOptions($merged, $newOption);
        }

        return $merged;
    }

    /**
     * Includes a required option in the array, replacing existing ones with same key.
     *
     * @since n.e.x.t
     *
     * @param list<RequiredOption> $requiredOptions Existing options.
     * @param RequiredOption $optionToInclude Option to include.
     * @return list<RequiredOption> Updated options array.
     */
    private static function includeInRequiredOptions(array $requiredOptions, RequiredOption $optionToInclude): array
    {
        // Remove any existing option with the same key
        $filtered = array_filter(
            $requiredOptions,
            static fn(RequiredOption $option): bool => !$option->getName()->equals($optionToInclude->getName())
        );

        // Add the new option
        $filtered[] = $optionToInclude;

        return array_values($filtered);
    }

    /**
     * Creates requirements for multi-modal operations.
     *
     * @since n.e.x.t
     *
     * @param CapabilityEnum $primaryCapability The primary capability.
     * @param list<ModalityEnum> $inputModalities Required input modalities.
     * @param list<ModalityEnum> $outputModalities Required output modalities.
     * @param ModelConfig|null $modelConfig Optional configuration.
     * @return ModelRequirements Multi-modal requirements.
     */
    public static function multiModal(
        CapabilityEnum $primaryCapability,
        array $inputModalities,
        array $outputModalities = [],
        ?ModelConfig $modelConfig = null
    ): ModelRequirements {
        $capabilities = [$primaryCapability];
        $requiredOptions = [];

        // Add input modalities
        if (!empty($inputModalities)) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::inputModalities(),
                $inputModalities
            );
        }

        // Add output modalities if specified
        if (!empty($outputModalities)) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::outputModalities(),
                $outputModalities
            );
        }

        // Include configuration requirements
        if ($modelConfig !== null) {
            $configRequirements = $modelConfig->toRequiredOptions();
            $requiredOptions = self::mergeRequiredOptions($requiredOptions, $configRequirements);
        }

        return new ModelRequirements($capabilities, $requiredOptions);
    }
}

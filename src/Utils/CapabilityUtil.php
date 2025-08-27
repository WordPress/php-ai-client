<?php

declare(strict_types=1);

namespace WordPress\AiClient\Utils;

use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

/**
 * Utility class for working with AI capabilities and modalities.
 *
 * This class provides helper methods for mapping between different capability
 * types, determining compatibility, and working with input/output modalities.
 *
 * @since n.e.x.t
 */
class CapabilityUtil
{
    /**
     * Maps generation types to their corresponding capabilities.
     *
     * @since n.e.x.t
     *
     * @param string $generationType The generation type (e.g., 'text', 'image', 'speech').
     * @return CapabilityEnum|null The corresponding capability or null if not found.
     */
    public static function getCapabilityForGenerationType(string $generationType): ?CapabilityEnum
    {
        switch (strtolower($generationType)) {
            case 'text':
                return CapabilityEnum::textGeneration();
            case 'image':
                return CapabilityEnum::imageGeneration();
            case 'speech':
                return CapabilityEnum::speechGeneration();
            case 'text-to-speech':
            case 'tts':
                return CapabilityEnum::textToSpeechConversion();
            case 'music':
                return CapabilityEnum::musicGeneration();
            case 'video':
                return CapabilityEnum::videoGeneration();
            case 'embedding':
            case 'embeddings':
                return CapabilityEnum::embeddingGeneration();
            default:
                return null;
        }
    }

    /**
     * Gets the primary output modality for a capability.
     *
     * @since n.e.x.t
     *
     * @param CapabilityEnum $capability The capability.
     * @return ModalityEnum|null The primary output modality or null if not applicable.
     */
    public static function getPrimaryOutputModality(CapabilityEnum $capability): ?ModalityEnum
    {
        if ($capability->isTextGeneration()) {
            return ModalityEnum::text();
        }

        if ($capability->isImageGeneration()) {
            return ModalityEnum::image();
        }

        if ($capability->isSpeechGeneration() || $capability->isTextToSpeechConversion()) {
            return ModalityEnum::audio();
        }

        if ($capability->isMusicGeneration()) {
            return ModalityEnum::audio();
        }

        if ($capability->isVideoGeneration()) {
            return ModalityEnum::video();
        }

        // Embedding generation doesn't have a traditional modality
        return null;
    }

    /**
     * Determines if a capability requires specific input modalities.
     *
     * @since n.e.x.t
     *
     * @param CapabilityEnum $capability The capability to check.
     * @return list<ModalityEnum> Default input modalities for the capability.
     */
    public static function getDefaultInputModalities(CapabilityEnum $capability): array
    {
        // Most generation types primarily use text input for prompts
        if (
            $capability->isTextGeneration() ||
            $capability->isImageGeneration() ||
            $capability->isSpeechGeneration() ||
            $capability->isMusicGeneration() ||
            $capability->isVideoGeneration()
        ) {
            return [ModalityEnum::text()];
        }

        // Text-to-speech typically uses text input
        if ($capability->isTextToSpeechConversion()) {
            return [ModalityEnum::text()];
        }

        // Embedding generation can handle various inputs
        if ($capability->isEmbeddingGeneration()) {
            return [ModalityEnum::text()];
        }

        return [];
    }

    /**
     * Checks if two capabilities are compatible for the same operation.
     *
     * @since n.e.x.t
     *
     * @param CapabilityEnum $capability1 First capability.
     * @param CapabilityEnum $capability2 Second capability.
     * @return bool True if compatible, false otherwise.
     */
    public static function areCompatible(CapabilityEnum $capability1, CapabilityEnum $capability2): bool
    {
        // Same capability is always compatible
        if ($capability1->equals($capability2)) {
            return true;
        }

        // Chat history is compatible with most generation types
        if ($capability1->isChatHistory() || $capability2->isChatHistory()) {
            return true;
        }

        // Different generation types are generally not compatible
        $generationCapabilities = [
            $capability1->isTextGeneration(),
            $capability1->isImageGeneration(),
            $capability1->isSpeechGeneration(),
            $capability1->isTextToSpeechConversion(),
            $capability1->isMusicGeneration(),
            $capability1->isVideoGeneration(),
            $capability1->isEmbeddingGeneration(),
        ];

        $capability2Generations = [
            $capability2->isTextGeneration(),
            $capability2->isImageGeneration(),
            $capability2->isSpeechGeneration(),
            $capability2->isTextToSpeechConversion(),
            $capability2->isMusicGeneration(),
            $capability2->isVideoGeneration(),
            $capability2->isEmbeddingGeneration(),
        ];

        // If both are generation types, they're not compatible
        if (in_array(true, $generationCapabilities, true) && in_array(true, $capability2Generations, true)) {
            return false;
        }

        return true;
    }

    /**
     * Gets all generation-type capabilities.
     *
     * @since n.e.x.t
     *
     * @return list<CapabilityEnum> All generation capabilities.
     */
    public static function getAllGenerationCapabilities(): array
    {
        return [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::imageGeneration(),
            CapabilityEnum::speechGeneration(),
            CapabilityEnum::textToSpeechConversion(),
            CapabilityEnum::musicGeneration(),
            CapabilityEnum::videoGeneration(),
            CapabilityEnum::embeddingGeneration(),
        ];
    }

    /**
     * Determines if a capability produces file output.
     *
     * @since n.e.x.t
     *
     * @param CapabilityEnum $capability The capability to check.
     * @return bool True if the capability produces file output.
     */
    public static function producesFileOutput(CapabilityEnum $capability): bool
    {
        return $capability->isImageGeneration() ||
               $capability->isSpeechGeneration() ||
               $capability->isTextToSpeechConversion() ||
               $capability->isMusicGeneration() ||
               $capability->isVideoGeneration();
    }

    /**
     * Gets suggested file extensions for a capability's output.
     *
     * @since n.e.x.t
     *
     * @param CapabilityEnum $capability The capability.
     * @return list<string> Suggested file extensions (without dots).
     */
    public static function getSuggestedFileExtensions(CapabilityEnum $capability): array
    {
        if ($capability->isImageGeneration()) {
            return ['png', 'jpg', 'jpeg', 'webp'];
        }

        if ($capability->isSpeechGeneration() || $capability->isTextToSpeechConversion()) {
            return ['mp3', 'wav', 'ogg'];
        }

        if ($capability->isMusicGeneration()) {
            return ['mp3', 'wav', 'midi'];
        }

        if ($capability->isVideoGeneration()) {
            return ['mp4', 'webm', 'mov'];
        }

        return [];
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Files\Contracts\FileInterface;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Results\Contracts\ResultInterface;

/**
 * Represents the result of a generative AI operation
 *
 * This DTO contains the generated candidates along with usage statistics
 * and metadata from the AI provider.
 *
 * @since n.e.x.t
 */
class GenerativeAiResult implements ResultInterface
{
    /**
     * @var string Unique identifier for this result
     */
    private string $id;

    /**
     * @var Candidate[] The generated candidates
     */
    private array $candidates;

    /**
     * @var TokenUsage Token usage statistics
     */
    private TokenUsage $tokenUsage;

    /**
     * @var array<string, mixed> Provider-specific metadata
     */
    private array $providerMetadata;

    /**
     * Constructor
     *
     * @since n.e.x.t
     * @param string $id Unique identifier for this result
     * @param Candidate[] $candidates The generated candidates
     * @param TokenUsage $tokenUsage Token usage statistics
     * @param array<string, mixed> $providerMetadata Provider-specific metadata
     */
    public function __construct(string $id, array $candidates, TokenUsage $tokenUsage, array $providerMetadata = [])
    {
        $this->id = $id;
        $this->candidates = $candidates;
        $this->tokenUsage = $tokenUsage;
        $this->providerMetadata = $providerMetadata;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the generated candidates
     *
     * @since n.e.x.t
     * @return Candidate[] The candidates
     */
    public function getCandidates(): array
    {
        return $this->candidates;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getTokenUsage(): TokenUsage
    {
        return $this->tokenUsage;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public function getProviderMetadata(): array
    {
        return $this->providerMetadata;
    }

    /**
     * Convert the first candidate to text
     *
     * @since n.e.x.t
     * @return string The text content
     * @throws \RuntimeException If no candidates or no text content
     */
    public function toText(): string
    {
        if (empty($this->candidates)) {
            throw new \RuntimeException('No candidates available');
        }

        $message = $this->candidates[0]->getMessage();
        foreach ($message->getParts() as $part) {
            if ($part->getType()->equals(MessagePartTypeEnum::text()) && $part->getText() !== null) {
                return $part->getText();
            }
        }

        throw new \RuntimeException('No text content found in first candidate');
    }

    /**
     * Convert the first candidate to an image file
     *
     * @since n.e.x.t
     * @return FileInterface The image file
     * @throws \RuntimeException If no candidates or no image content
     */
    public function toImageFile(): FileInterface
    {
        if (empty($this->candidates)) {
            throw new \RuntimeException('No candidates available');
        }

        $message = $this->candidates[0]->getMessage();
        foreach ($message->getParts() as $part) {
            if ($part->getType()->equals(MessagePartTypeEnum::inlineFile()) && $part->getInlineFile() !== null) {
                return $part->getInlineFile();
            }
            if ($part->getType()->equals(MessagePartTypeEnum::remoteFile()) && $part->getRemoteFile() !== null) {
                return $part->getRemoteFile();
            }
        }

        throw new \RuntimeException('No image content found in first candidate');
    }

    /**
     * Convert the first candidate to an audio file
     *
     * @since n.e.x.t
     * @return FileInterface The audio file
     * @throws \RuntimeException If no candidates or no audio content
     */
    public function toAudioFile(): FileInterface
    {
        // Similar implementation to toImageFile, but checking for audio MIME types
        return $this->toImageFile(); // Simplified for now
    }

    /**
     * Convert the first candidate to a video file
     *
     * @since n.e.x.t
     * @return FileInterface The video file
     * @throws \RuntimeException If no candidates or no video content
     */
    public function toVideoFile(): FileInterface
    {
        // Similar implementation to toImageFile, but checking for video MIME types
        return $this->toImageFile(); // Simplified for now
    }

    /**
     * Convert the first candidate to a message
     *
     * @since n.e.x.t
     * @return Message The message
     * @throws \RuntimeException If no candidates available
     */
    public function toMessage(): Message
    {
        if (empty($this->candidates)) {
            throw new \RuntimeException('No candidates available');
        }

        return $this->candidates[0]->getMessage();
    }

    /**
     * Convert all candidates to text array
     *
     * @since n.e.x.t
     * @return string[] Array of text content
     */
    public function toTexts(): array
    {
        $texts = [];
        foreach ($this->candidates as $candidate) {
            $message = $candidate->getMessage();
            foreach ($message->getParts() as $part) {
                if ($part->getType()->equals(MessagePartTypeEnum::text()) && $part->getText() !== null) {
                    $texts[] = $part->getText();
                    break;
                }
            }
        }
        return $texts;
    }

    /**
     * Convert all candidates to image files
     *
     * @since n.e.x.t
     * @return FileInterface[] Array of image files
     */
    public function toImageFiles(): array
    {
        $files = [];
        foreach ($this->candidates as $candidate) {
            $message = $candidate->getMessage();
            foreach ($message->getParts() as $part) {
                if ($part->getType()->equals(MessagePartTypeEnum::inlineFile()) && $part->getInlineFile() !== null) {
                    $files[] = $part->getInlineFile();
                    break;
                }
                if ($part->getType()->equals(MessagePartTypeEnum::remoteFile()) && $part->getRemoteFile() !== null) {
                    $files[] = $part->getRemoteFile();
                    break;
                }
            }
        }
        return $files;
    }

    /**
     * Convert all candidates to audio files
     *
     * @since n.e.x.t
     * @return FileInterface[] Array of audio files
     */
    public function toAudioFiles(): array
    {
        // Similar implementation to toImageFiles, but checking for audio MIME types
        return $this->toImageFiles(); // Simplified for now
    }

    /**
     * Convert all candidates to video files
     *
     * @since n.e.x.t
     * @return FileInterface[] Array of video files
     */
    public function toVideoFiles(): array
    {
        // Similar implementation to toImageFiles, but checking for video MIME types
        return $this->toImageFiles(); // Simplified for now
    }

    /**
     * Convert all candidates to messages
     *
     * @since n.e.x.t
     * @return Message[] Array of messages
     */
    public function toMessages(): array
    {
        return array_map(fn(Candidate $candidate) => $candidate->getMessage(), $this->candidates);
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
                'id' => [
                    'type' => 'string',
                    'description' => 'Unique identifier for this result',
                ],
                'candidates' => [
                    'type' => 'array',
                    'items' => Candidate::getJsonSchema(),
                    'description' => 'The generated candidates',
                ],
                'tokenUsage' => TokenUsage::getJsonSchema(),
                'providerMetadata' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Provider-specific metadata',
                ],
            ],
            'required' => ['id', 'candidates', 'tokenUsage'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use WordPress\AiClient\Files\Contracts\FileInterface;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Results\Contracts\ResultInterface;

/**
 * Represents the result of a generative AI operation.
 *
 * This DTO contains the generated candidates along with usage statistics
 * and metadata from the AI provider.
 *
 * @since n.e.x.t
 */
class GenerativeAiResult implements ResultInterface
{
    /**
     * @var string Unique identifier for this result.
     */
    private string $id;

    /**
     * @var Candidate[] The generated candidates.
     */
    private array $candidates;

    /**
     * @var TokenUsage Token usage statistics.
     */
    private TokenUsage $tokenUsage;

    /**
     * @var array<string, mixed> Provider-specific metadata.
     */
    private array $providerMetadata;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param string $id Unique identifier for this result.
     * @param Candidate[] $candidates The generated candidates.
     * @param TokenUsage $tokenUsage Token usage statistics.
     * @param array<string, mixed> $providerMetadata Provider-specific metadata.
     * @throws \InvalidArgumentException If no candidates provided.
     */
    public function __construct(string $id, array $candidates, TokenUsage $tokenUsage, array $providerMetadata = [])
    {
        if (empty($candidates)) {
            throw new \InvalidArgumentException('At least one candidate must be provided');
        }

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
     * Gets the generated candidates.
     *
     * @since n.e.x.t
     *
     * @return Candidate[] The candidates.
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
     * Gets the total number of candidates.
     *
     * @since n.e.x.t
     *
     * @return int The total number of candidates.
     */
    public function getTotalCandidates(): int
    {
        return count($this->candidates);
    }

    /**
     * Checks if the result has multiple candidates.
     *
     * @since n.e.x.t
     *
     * @return bool True if there are multiple candidates, false otherwise.
     */
    public function hasMultipleCandidates(): bool
    {
        return count($this->candidates) > 1;
    }

    /**
     * Converts the first candidate to text.
     *
     * @since n.e.x.t
     *
     * @return string The text content.
     * @throws \RuntimeException If no text content.
     */
    public function toText(): string
    {
        $message = $this->candidates[0]->getMessage();
        foreach ($message->getParts() as $part) {
            $text = $part->getText();
            if ($text !== null) {
                return $text;
            }
        }

        throw new \RuntimeException('No text content found in first candidate');
    }

    /**
     * Converts the first candidate to a file.
     *
     * @since n.e.x.t
     *
     * @return FileInterface The file.
     * @throws \RuntimeException If no file content.
     */
    public function toFile(): FileInterface
    {
        $message = $this->candidates[0]->getMessage();
        foreach ($message->getParts() as $part) {
            $inlineFile = $part->getInlineFile();
            if ($inlineFile !== null) {
                return $inlineFile;
            }

            $remoteFile = $part->getRemoteFile();
            if ($remoteFile !== null) {
                return $remoteFile;
            }
        }

        throw new \RuntimeException('No file content found in first candidate');
    }

    /**
     * Converts the first candidate to an image file.
     *
     * @since n.e.x.t
     *
     * @return FileInterface The image file.
     * @throws \RuntimeException If no image content.
     */
    public function toImageFile(): FileInterface
    {
        $file = $this->toFile();

        if (!$file->getMimeType()->isImage()) {
            throw new \RuntimeException(
                sprintf('File is not an image. MIME type: %s', $file->getMimeType())
            );
        }

        return $file;
    }

    /**
     * Converts the first candidate to an audio file.
     *
     * @since n.e.x.t
     *
     * @return FileInterface The audio file.
     * @throws \RuntimeException If no audio content.
     */
    public function toAudioFile(): FileInterface
    {
        $file = $this->toFile();

        if (!$file->getMimeType()->isAudio()) {
            throw new \RuntimeException(
                sprintf('File is not an audio file. MIME type: %s', $file->getMimeType())
            );
        }

        return $file;
    }

    /**
     * Converts the first candidate to a video file.
     *
     * @since n.e.x.t
     *
     * @return FileInterface The video file.
     * @throws \RuntimeException If no video content.
     */
    public function toVideoFile(): FileInterface
    {
        $file = $this->toFile();

        if (!$file->getMimeType()->isVideo()) {
            throw new \RuntimeException(
                sprintf('File is not a video file. MIME type: %s', $file->getMimeType())
            );
        }

        return $file;
    }

    /**
     * Converts the first candidate to a message.
     *
     * @since n.e.x.t
     *
     * @return Message The message.
     */
    public function toMessage(): Message
    {
        return $this->candidates[0]->getMessage();
    }

    /**
     * Converts all candidates to text array.
     *
     * @since n.e.x.t
     *
     * @return string[] Array of text content.
     */
    public function toTexts(): array
    {
        $texts = [];
        foreach ($this->candidates as $candidate) {
            $message = $candidate->getMessage();
            foreach ($message->getParts() as $part) {
                $text = $part->getText();
                if ($text !== null) {
                    $texts[] = $text;
                    break;
                }
            }
        }
        return $texts;
    }

    /**
     * Converts all candidates to image files.
     *
     * @since n.e.x.t
     *
     * @return FileInterface[] Array of image files.
     */
    public function toImageFiles(): array
    {
        $files = [];
        foreach ($this->candidates as $candidate) {
            $message = $candidate->getMessage();
            foreach ($message->getParts() as $part) {
                $inlineFile = $part->getInlineFile();
                if ($inlineFile !== null && $inlineFile->getMimeType()->isImage()) {
                    $files[] = $inlineFile;
                    break;
                }

                $remoteFile = $part->getRemoteFile();
                if ($remoteFile !== null && $remoteFile->getMimeType()->isImage()) {
                    $files[] = $remoteFile;
                    break;
                }
            }
        }
        return $files;
    }

    /**
     * Converts all candidates to audio files.
     *
     * @since n.e.x.t
     *
     * @return FileInterface[] Array of audio files.
     */
    public function toAudioFiles(): array
    {
        $files = [];
        foreach ($this->candidates as $candidate) {
            $message = $candidate->getMessage();
            foreach ($message->getParts() as $part) {
                $inlineFile = $part->getInlineFile();
                if ($inlineFile !== null && $inlineFile->getMimeType()->isAudio()) {
                    $files[] = $inlineFile;
                    break;
                }

                $remoteFile = $part->getRemoteFile();
                if ($remoteFile !== null && $remoteFile->getMimeType()->isAudio()) {
                    $files[] = $remoteFile;
                    break;
                }
            }
        }
        return $files;
    }

    /**
     * Converts all candidates to video files.
     *
     * @since n.e.x.t
     *
     * @return FileInterface[] Array of video files.
     */
    public function toVideoFiles(): array
    {
        $files = [];
        foreach ($this->candidates as $candidate) {
            $message = $candidate->getMessage();
            foreach ($message->getParts() as $part) {
                $inlineFile = $part->getInlineFile();
                if ($inlineFile !== null && $inlineFile->getMimeType()->isVideo()) {
                    $files[] = $inlineFile;
                    break;
                }

                $remoteFile = $part->getRemoteFile();
                if ($remoteFile !== null && $remoteFile->getMimeType()->isVideo()) {
                    $files[] = $remoteFile;
                    break;
                }
            }
        }
        return $files;
    }

    /**
     * Converts all candidates to messages.
     *
     * @since n.e.x.t
     *
     * @return Message[] Array of messages.
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
                    'description' => 'Unique identifier for this result.',
                ],
                'candidates' => [
                    'type' => 'array',
                    'items' => Candidate::getJsonSchema(),
                    'minItems' => 1,
                    'description' => 'The generated candidates.',
                ],
                'tokenUsage' => TokenUsage::getJsonSchema(),
                'providerMetadata' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Provider-specific metadata.',
                ],
            ],
            'required' => ['id', 'candidates', 'tokenUsage'],
        ];
    }
}

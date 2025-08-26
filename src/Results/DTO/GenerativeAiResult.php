<?php

declare(strict_types=1);

namespace WordPress\AiClient\Results\DTO;

use InvalidArgumentException;
use RuntimeException;
use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Results\Contracts\ResultInterface;

/**
 * Represents the result of a generative AI operation.
 *
 * This DTO contains the generated candidates along with usage statistics
 * and metadata from the AI provider.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type CandidateArrayShape from Candidate
 * @phpstan-import-type TokenUsageArrayShape from TokenUsage
 *
 * @phpstan-type GenerativeAiResultArrayShape array{
 *     id: string,
 *     candidates: array<CandidateArrayShape>,
 *     tokenUsage: TokenUsageArrayShape,
 *     providerMetadata?: array<string, mixed>
 * }
 *
 * @extends AbstractDataTransferObject<GenerativeAiResultArrayShape>
 */
class GenerativeAiResult extends AbstractDataTransferObject implements ResultInterface
{
    public const KEY_ID = 'id';
    public const KEY_CANDIDATES = 'candidates';
    public const KEY_TOKEN_USAGE = 'tokenUsage';
    public const KEY_PROVIDER_METADATA = 'providerMetadata';
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
     * @throws InvalidArgumentException If no candidates provided.
     */
    public function __construct(string $id, array $candidates, TokenUsage $tokenUsage, array $providerMetadata = [])
    {
        if (empty($candidates)) {
            throw new InvalidArgumentException('At least one candidate must be provided');
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
    public function getCandidateCount(): int
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
        return $this->getCandidateCount() > 1;
    }

    /**
     * Converts the first candidate to text.
     *
     * @since n.e.x.t
     *
     * @return string The text content.
     * @throws RuntimeException If no text content.
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

        throw new RuntimeException('No text content found in first candidate');
    }

    /**
     * Converts the first candidate to a file.
     *
     * @since n.e.x.t
     *
     * @return File The file.
     * @throws RuntimeException If no file content.
     */
    public function toFile(): File
    {
        $message = $this->candidates[0]->getMessage();
        foreach ($message->getParts() as $part) {
            $file = $part->getFile();
            if ($file !== null) {
                return $file;
            }
        }

        throw new RuntimeException('No file content found in first candidate');
    }

    /**
     * Converts the first candidate to an image file.
     *
     * @since n.e.x.t
     *
     * @return File The image file.
     * @throws RuntimeException If no image content.
     */
    public function toImageFile(): File
    {
        $file = $this->toFile();

        if (!$file->isImage()) {
            throw new RuntimeException(
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
     * @return File The audio file.
     * @throws RuntimeException If no audio content.
     */
    public function toAudioFile(): File
    {
        $file = $this->toFile();

        if (!$file->isAudio()) {
            throw new RuntimeException(
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
     * @return File The video file.
     * @throws RuntimeException If no video content.
     */
    public function toVideoFile(): File
    {
        $file = $this->toFile();

        if (!$file->isVideo()) {
            throw new RuntimeException(
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
     * @return list<string> Array of text content.
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
     * Converts all candidates to files.
     *
     * @since n.e.x.t
     *
     * @return list<File> Array of files.
     */
    public function toFiles(): array
    {
        $files = [];
        foreach ($this->candidates as $candidate) {
            $message = $candidate->getMessage();
            foreach ($message->getParts() as $part) {
                $file = $part->getFile();
                if ($file !== null) {
                    $files[] = $file;
                    break;
                }
            }
        }
        return $files;
    }

    /**
     * Converts all candidates to image files.
     *
     * @since n.e.x.t
     *
     * @return list<File> Array of image files.
     */
    public function toImageFiles(): array
    {
        return array_values(array_filter(
            $this->toFiles(),
            fn(File $file) => $file->isImage()
        ));
    }

    /**
     * Converts all candidates to audio files.
     *
     * @since n.e.x.t
     *
     * @return list<File> Array of audio files.
     */
    public function toAudioFiles(): array
    {
        return array_values(array_filter(
            $this->toFiles(),
            fn(File $file) => $file->isAudio()
        ));
    }

    /**
     * Converts all candidates to video files.
     *
     * @since n.e.x.t
     *
     * @return list<File> Array of video files.
     */
    public function toVideoFiles(): array
    {
        return array_values(array_filter(
            $this->toFiles(),
            fn(File $file) => $file->isVideo()
        ));
    }

    /**
     * Converts all candidates to messages.
     *
     * @since n.e.x.t
     *
     * @return list<Message> Array of messages.
     */
    public function toMessages(): array
    {
        return array_values(array_map(fn(Candidate $candidate) => $candidate->getMessage(), $this->candidates));
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
                self::KEY_ID => [
                    'type' => 'string',
                    'description' => 'Unique identifier for this result.',
                ],
                self::KEY_CANDIDATES => [
                    'type' => 'array',
                    'items' => Candidate::getJsonSchema(),
                    'minItems' => 1,
                    'description' => 'The generated candidates.',
                ],
                self::KEY_TOKEN_USAGE => TokenUsage::getJsonSchema(),
                self::KEY_PROVIDER_METADATA => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'description' => 'Provider-specific metadata.',
                ],
            ],
            'required' => [self::KEY_ID, self::KEY_CANDIDATES, self::KEY_TOKEN_USAGE],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiResultArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_ID => $this->id,
            self::KEY_CANDIDATES => array_map(fn(Candidate $candidate) => $candidate->toArray(), $this->candidates),
            self::KEY_TOKEN_USAGE => $this->tokenUsage->toArray(),
            self::KEY_PROVIDER_METADATA => $this->providerMetadata,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_ID, self::KEY_CANDIDATES, self::KEY_TOKEN_USAGE]);

        $candidates = array_map(
            fn(array $candidateData) => Candidate::fromArray($candidateData),
            $array[self::KEY_CANDIDATES]
        );

        return new self(
            $array[self::KEY_ID],
            $candidates,
            TokenUsage::fromArray($array[self::KEY_TOKEN_USAGE]),
            $array[self::KEY_PROVIDER_METADATA] ?? []
        );
    }
}

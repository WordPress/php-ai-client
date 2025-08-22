<?php

declare(strict_types=1);

namespace WordPress\AiClient\Builders;

use InvalidArgumentException;
use RuntimeException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\DTO\RequiredOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts\SpeechGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Fluent builder for constructing AI prompts.
 *
 * This class provides a fluent interface for building prompts with various
 * content types and model configurations. It automatically infers model
 * requirements based on the features used in the prompt.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type MessageArrayShape from Message
 * @phpstan-import-type MessagePartArrayShape from MessagePart
 */
class PromptBuilder
{
    /**
     * @var ProviderRegistry The provider registry for finding suitable models.
     */
    private ProviderRegistry $registry;

    /**
     * @var list<Message> The messages in the conversation.
     */
    protected array $messages = [];

    /**
     * @var ModelInterface|null The model to use for generation.
     */
    protected ?ModelInterface $model = null;

    /**
     * @var ModelConfig The model configuration.
     */
    protected ModelConfig $modelConfig;

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The provider registry for finding suitable models.
     * @param string|MessagePart|Message|MessageArrayShape|list<string|MessagePart|MessagePartArrayShape>|list<Message>|null $prompt
     *     Optional initial prompt content.
     */
    // phpcs:enable Generic.Files.LineLength.TooLong
    public function __construct(ProviderRegistry $registry, $prompt = null)
    {
        $this->registry = $registry;
        $this->modelConfig = new ModelConfig();

        if ($prompt === null) {
            return;
        }

        // Check if it's a list of Messages - set as messages
        if ($this->isMessagesList($prompt)) {
            $this->messages = $prompt;
            return;
        }

        // Check if it's a MessageArrayShape - add to messages
        if (is_array($prompt) && Message::isArrayShape($prompt)) {
            $this->messages[] = Message::fromArray($prompt);
            return;
        }

        // Parse it as a user message
        $userMessage = $this->parseMessage($prompt, MessageRoleEnum::user());
        $this->messages[] = $userMessage;
    }

    /**
     * Adds text to the current message.
     *
     * @since n.e.x.t
     *
     * @param string $text The text to add.
     * @return self
     */
    public function withText(string $text): self
    {
        $part = new MessagePart($text);
        $this->appendPartToMessages($part);
        return $this;
    }

    /**
     * Adds an inline image to the current message.
     *
     * @since n.e.x.t
     *
     * @param string $base64Blob The base64-encoded image data.
     * @param string $mimeType The MIME type of the image.
     * @return self
     */
    public function withInlineImage(string $base64Blob, string $mimeType): self
    {
        // Create data URI format for inline image
        $dataUri = 'data:' . $mimeType . ';base64,' . $base64Blob;
        $file = new File($dataUri, $mimeType);
        $part = new MessagePart($file);
        $this->appendPartToMessages($part);
        return $this;
    }

    /**
     * Adds a remote image to the current message.
     *
     * @since n.e.x.t
     *
     * @param string $uri The URI of the remote image.
     * @param string $mimeType The MIME type of the image.
     * @return self
     */
    public function withRemoteImage(string $uri, string $mimeType): self
    {
        $file = new File($uri, $mimeType);
        $part = new MessagePart($file);
        $this->appendPartToMessages($part);
        return $this;
    }

    /**
     * Adds an image file to the current message.
     *
     * @since n.e.x.t
     *
     * @param File $file The image file.
     * @return self
     */
    public function withImageFile(File $file): self
    {
        $part = new MessagePart($file);
        $this->appendPartToMessages($part);
        return $this;
    }

    /**
     * Adds an audio file to the current message.
     *
     * @since n.e.x.t
     *
     * @param File $file The audio file.
     * @return self
     */
    public function withAudioFile(File $file): self
    {
        $part = new MessagePart($file);
        $this->appendPartToMessages($part);
        return $this;
    }

    /**
     * Adds a video file to the current message.
     *
     * @since n.e.x.t
     *
     * @param File $file The video file.
     * @return self
     */
    public function withVideoFile(File $file): self
    {
        $part = new MessagePart($file);
        $this->appendPartToMessages($part);
        return $this;
    }

    /**
     * Adds a function response to the current message.
     *
     * @since n.e.x.t
     *
     * @param FunctionResponse $functionResponse The function response.
     * @return self
     */
    public function withFunctionResponse(FunctionResponse $functionResponse): self
    {
        $part = new MessagePart($functionResponse);
        $this->appendPartToMessages($part);
        return $this;
    }

    /**
     * Adds message parts to the current message.
     *
     * @since n.e.x.t
     *
     * @param MessagePart ...$parts The message parts to add.
     * @return self
     */
    public function withMessageParts(MessagePart ...$parts): self
    {
        foreach ($parts as $part) {
            $this->appendPartToMessages($part);
        }
        return $this;
    }

    /**
     * Adds conversation history messages.
     *
     * @since n.e.x.t
     *
     * @param Message ...$messages The messages to add to history.
     * @return self
     */
    public function withHistory(Message ...$messages): self
    {
        foreach ($messages as $message) {
            $this->messages[] = $message;
        }

        return $this;
    }

    /**
     * Sets the model to use for generation.
     *
     * @since n.e.x.t
     *
     * @param ModelInterface $model The model to use.
     * @return self
     */
    public function usingModel(ModelInterface $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Sets a different provider registry.
     *
     * @since n.e.x.t
     *
     * @param ProviderRegistry $registry The provider registry to use.
     * @return self
     */
    public function usingRegistry(ProviderRegistry $registry): self
    {
        $this->registry = $registry;
        return $this;
    }

    /**
     * Sets the system instruction.
     *
     * System instructions are stored in the model configuration and guide
     * the AI model's behavior throughout the conversation.
     *
     * @since n.e.x.t
     *
     * @param string $systemInstruction The system instruction text.
     * @return self
     */
    public function usingSystemInstruction(string $systemInstruction): self
    {
        $this->modelConfig->setSystemInstruction($systemInstruction);
        return $this;
    }

    /**
     * Sets the maximum number of tokens to generate.
     *
     * @since n.e.x.t
     *
     * @param int $maxTokens The maximum number of tokens.
     * @return self
     */
    public function usingMaxTokens(int $maxTokens): self
    {
        $this->modelConfig->setMaxTokens($maxTokens);
        return $this;
    }

    /**
     * Sets the temperature for generation.
     *
     * @since n.e.x.t
     *
     * @param float $temperature The temperature value.
     * @return self
     */
    public function usingTemperature(float $temperature): self
    {
        $this->modelConfig->setTemperature($temperature);
        return $this;
    }

    /**
     * Sets the top-p value for generation.
     *
     * @since n.e.x.t
     *
     * @param float $topP The top-p value.
     * @return self
     */
    public function usingTopP(float $topP): self
    {
        $this->modelConfig->setTopP($topP);
        return $this;
    }

    /**
     * Sets the top-k value for generation.
     *
     * @since n.e.x.t
     *
     * @param int $topK The top-k value.
     * @return self
     */
    public function usingTopK(int $topK): self
    {
        $this->modelConfig->setTopK($topK);
        return $this;
    }

    /**
     * Sets stop sequences for generation.
     *
     * @since n.e.x.t
     *
     * @param string ...$stopSequences The stop sequences.
     * @return self
     */
    public function usingStopSequences(string ...$stopSequences): self
    {
        $this->modelConfig->setCustomOption('stopSequences', $stopSequences);
        return $this;
    }

    /**
     * Sets the number of candidates to generate.
     *
     * @since n.e.x.t
     *
     * @param int $candidateCount The number of candidates.
     * @return self
     */
    public function usingCandidateCount(int $candidateCount): self
    {
        $this->modelConfig->setCandidateCount($candidateCount);
        return $this;
    }

    /**
     * Sets the output MIME type.
     *
     * @since n.e.x.t
     *
     * @param string $mimeType The MIME type.
     * @return self
     */
    public function usingOutputMime(string $mimeType): self
    {
        $this->modelConfig->setOutputMimeType($mimeType);
        return $this;
    }

    /**
     * Sets the output schema.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed> $schema The output schema.
     * @return self
     */
    public function usingOutputSchema(array $schema): self
    {
        $this->modelConfig->setOutputSchema($schema);
        return $this;
    }

    /**
     * Sets the output modalities.
     *
     * @since n.e.x.t
     *
     * @param ModalityEnum ...$modalities The output modalities.
     * @return self
     */
    public function usingOutputModalities(ModalityEnum ...$modalities): self
    {
        $this->modelConfig->setOutputModalities($modalities);
        return $this;
    }

    /**
     * Configures the prompt for JSON response output.
     *
     * @since n.e.x.t
     *
     * @param array<string, mixed>|null $schema Optional JSON schema.
     * @return self
     */
    public function asJsonResponse(?array $schema = null): self
    {
        $this->usingOutputMime('application/json');
        if ($schema !== null) {
            $this->usingOutputSchema($schema);
        }
        return $this;
    }

    /**
     * Gets the inferred model requirements based on prompt features.
     *
     * @since n.e.x.t
     *
     * @return ModelRequirements The inferred requirements.
     */
    public function getModelRequirements(): ModelRequirements
    {
        $capabilities = [];
        $inputModalities = [];

        // Always need text generation capability
        $capabilities[] = CapabilityEnum::textGeneration();

        // Check if we have chat history (multiple messages)
        if (count($this->messages) > 1) {
            $capabilities[] = CapabilityEnum::chatHistory();
        }

        // Analyze all messages to determine required input modalities
        foreach ($this->messages as $message) {
            foreach ($message->getParts() as $part) {
                // Check for text input
                if ($part->getText() !== null) {
                    $inputModalities[ModalityEnum::text()->value] = ModalityEnum::text();
                }

                // Check for file inputs
                $file = $part->getFile();
                if ($file !== null) {
                    if ($file->isImage()) {
                        $inputModalities[ModalityEnum::image()->value] = ModalityEnum::image();
                    } elseif ($file->isAudio()) {
                        $inputModalities[ModalityEnum::audio()->value] = ModalityEnum::audio();
                    } elseif ($file->isVideo()) {
                        $inputModalities[ModalityEnum::video()->value] = ModalityEnum::video();
                    } elseif ($file->isDocument() || $file->isText()) {
                        $inputModalities[ModalityEnum::document()->value] = ModalityEnum::document();
                    }
                }

                // Check for function calls/responses (these might require special capabilities)
                if ($part->getFunctionCall() !== null || $part->getFunctionResponse() !== null) {
                    // Function calling capability would go here if we had it in CapabilityEnum
                    // For now, we'll just note this requires text generation
                }
            }
        }

        // Build required options from ModelConfig
        $requiredOptions = $this->modelConfig->toRequiredOptions();

        // Add input modalities if we have non-text inputs
        if (count($inputModalities) > 0) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::inputModalities()->value,
                array_values($inputModalities)
            );
        }

        return new ModelRequirements(
            $capabilities,
            $requiredOptions
        );
    }

    /**
     * Checks if the current prompt is supported by the selected model.
     *
     * @since n.e.x.t
     *
     * @param ModalityEnum|null $intendedOutput Optional output modality to check support for.
     * @return bool True if supported, false otherwise.
     */
    public function isSupported(?ModalityEnum $intendedOutput = null): bool
    {
        // If an intended output modality is specified, temporarily include it
        $originalModalities = null;
        if ($intendedOutput !== null) {
            $originalModalities = $this->modelConfig->getOutputModalities();
            $this->modelConfig->includeOutputModality($intendedOutput);
        }

        try {
            // Try to get a configured model - this will throw if no suitable model exists
            $this->getConfiguredModel();
            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        } finally {
            // Restore original modalities if we modified them
            if ($originalModalities !== null) {
                $this->modelConfig->setOutputModalities($originalModalities);
            }
        }
    }

    /**
     * Checks if the prompt is supported for text generation.
     *
     * @since n.e.x.t
     *
     * @return bool True if text generation is supported.
     */
    public function isSupportedForText(): bool
    {
        return $this->isSupported(ModalityEnum::text());
    }

    /**
     * Checks if the prompt is supported for image generation.
     *
     * @since n.e.x.t
     *
     * @return bool True if image generation is supported.
     */
    public function isSupportedForImage(): bool
    {
        return $this->isSupported(ModalityEnum::image());
    }

    /**
     * Checks if the prompt is supported for audio generation.
     *
     * @since n.e.x.t
     *
     * @return bool True if audio generation is supported.
     */
    public function isSupportedForAudio(): bool
    {
        return $this->isSupported(ModalityEnum::audio());
    }

    /**
     * Checks if the prompt is supported for video generation.
     *
     * @since n.e.x.t
     *
     * @return bool True if video generation is supported.
     */
    public function isSupportedForVideo(): bool
    {
        return $this->isSupported(ModalityEnum::video());
    }

    /**
     * Checks if the prompt is supported for speech generation.
     *
     * @since n.e.x.t
     *
     * @return bool True if speech generation is supported.
     */
    public function isSupportedForSpeech(): bool
    {
        return $this->isSupported(ModalityEnum::audio());
    }

    /**
     * Generates a result from the prompt.
     *
     * This is the primary execution method that generates a result (containing
     * potentially multiple candidates) based on the configured output modality.
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiResult The generated result containing candidates.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     * @throws RuntimeException If the model doesn't support the configured output modality.
     */
    public function generateResult(): GenerativeAiResult
    {
        $this->validateMessages();
        $model = $this->getConfiguredModel();

        // Get the configured output modalities
        $outputModalities = $this->modelConfig->getOutputModalities();

        // Default to text if no output modality is specified
        if ($outputModalities === null || empty($outputModalities)) {
            $outputModalities = [ModalityEnum::text()];
        }

        // Multi-modal output (multiple modalities) uses TextGenerationModelInterface
        if (count($outputModalities) > 1) {
            if (!$model instanceof TextGenerationModelInterface) {
                throw new RuntimeException(
                    sprintf(
                        'Model "%s" does not support multi-modal generation.',
                        $model->metadata()->getId()
                    )
                );
            }
            return $model->generateTextResult($this->messages);
        }

        // Single modality routing
        $outputModality = $outputModalities[0];

        // Route to the appropriate generation method based on output modality
        if ($outputModality->isText()) {
            if (!$model instanceof TextGenerationModelInterface) {
                throw new RuntimeException(
                    sprintf(
                        'Model "%s" does not support text generation.',
                        $model->metadata()->getId()
                    )
                );
            }
            return $model->generateTextResult($this->messages);
        }

        if ($outputModality->isImage()) {
            if (!$model instanceof ImageGenerationModelInterface) {
                throw new RuntimeException(
                    sprintf(
                        'Model "%s" does not support image generation.',
                        $model->metadata()->getId()
                    )
                );
            }
            return $model->generateImageResult($this->messages);
        }

        if ($outputModality->isAudio()) {
            if (!$model instanceof SpeechGenerationModelInterface) {
                throw new RuntimeException(
                    sprintf(
                        'Model "%s" does not support speech/audio generation.',
                        $model->metadata()->getId()
                    )
                );
            }
            return $model->generateSpeechResult($this->messages);
        }

        // TODO: Add support for video output modality when interface is available
        throw new RuntimeException(
            sprintf('Output modality "%s" is not yet supported.', $outputModality->value)
        );
    }

    /**
     * Generates a text result from the prompt.
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiResult The generated result containing text candidates.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     * @throws RuntimeException If the model doesn't support text generation.
     */
    public function generateTextResult(): GenerativeAiResult
    {
        // Include text in output modalities
        $this->modelConfig->includeOutputModality(ModalityEnum::text());

        // Generate and return the result
        return $this->generateResult();
    }

    /**
     * Generates an image result from the prompt.
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiResult The generated result containing image candidates.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     * @throws RuntimeException If the model doesn't support image generation.
     */
    public function generateImageResult(): GenerativeAiResult
    {
        // Include image in output modalities
        $this->modelConfig->includeOutputModality(ModalityEnum::image());

        // Generate and return the result
        return $this->generateResult();
    }

    /**
     * Generates a speech result from the prompt.
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiResult The generated result containing speech audio candidates.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     * @throws RuntimeException If the model doesn't support speech generation.
     */
    public function generateSpeechResult(): GenerativeAiResult
    {
        // Include audio in output modalities
        $this->modelConfig->includeOutputModality(ModalityEnum::audio());

        // Generate and return the result
        return $this->generateResult();
    }

    /**
     * Converts text to speech and returns the result.
     *
     * @since n.e.x.t
     *
     * @return GenerativeAiResult The generated result containing speech audio candidates.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     * @throws RuntimeException If the model doesn't support text-to-speech conversion.
     */
    public function convertTextToSpeechResult(): GenerativeAiResult
    {
        // Include audio in output modalities
        $this->modelConfig->includeOutputModality(ModalityEnum::audio());

        // Get the configured model
        $model = $this->getConfiguredModel();

        // Ensure the model supports text-to-speech conversion
        if (!$model instanceof TextToSpeechConversionModelInterface) {
            throw new RuntimeException(
                sprintf(
                    'Model "%s" does not support text-to-speech conversion.',
                    $model->metadata()->getId()
                )
            );
        }

        // Validate messages and convert
        $this->validateMessages();
        return $model->convertTextToSpeechResult($this->messages);
    }

    /**
     * Generates text from the prompt.
     *
     * @since n.e.x.t
     *
     * @return string The generated text.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     */
    public function generateText(): string
    {
        // Generate text result and extract text from first candidate
        $result = $this->generateTextResult();
        $candidates = $result->getCandidates();

        if (empty($candidates)) {
            throw new RuntimeException('No candidates were generated.');
        }

        // Get the text from the first message part
        $message = $candidates[0]->getMessage();
        $parts = $message->getParts();
        if (empty($parts)) {
            throw new RuntimeException('Generated message contains no parts.');
        }

        $text = $parts[0]->getText();
        if ($text === null) {
            throw new RuntimeException('Generated message part contains no text.');
        }

        return $text;
    }

    /**
     * Generates multiple text candidates from the prompt.
     *
     * @since n.e.x.t
     *
     * @param int|null $candidateCount The number of candidates to generate.
     * @return list<string> The generated texts.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     */
    public function generateTexts(?int $candidateCount = null): array
    {
        if ($candidateCount !== null) {
            $this->usingCandidateCount($candidateCount);
        }

        // Generate text result
        $results = $this->generateTextResult();
        $candidates = $results->getCandidates();

        // Extract text from each candidate
        $texts = [];
        foreach ($candidates as $candidate) {
            $message = $candidate->getMessage();
            $parts = $message->getParts();
            if (empty($parts)) {
                continue;
            }

            $text = $parts[0]->getText();
            if ($text !== null) {
                $texts[] = $text;
            }
        }

        if (empty($texts)) {
            throw new RuntimeException('No text was generated from any candidates.');
        }

        return $texts;
    }

    /**
     * Generates an image from the prompt.
     *
     * @since n.e.x.t
     *
     * @return File The generated image file.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     * @throws RuntimeException If no image is generated.
     */
    public function generateImage(): File
    {
        // Generate image result and extract image from first candidate
        $result = $this->generateImageResult();
        $candidates = $result->getCandidates();

        if (empty($candidates)) {
            throw new RuntimeException('No candidates were generated.');
        }

        // Get the image file from the first message part
        $message = $candidates[0]->getMessage();
        $parts = $message->getParts();
        if (empty($parts)) {
            throw new RuntimeException('Generated message contains no parts.');
        }

        $file = $parts[0]->getFile();
        if ($file === null) {
            throw new RuntimeException('Generated message part contains no image file.');
        }

        return $file;
    }

    /**
     * Generates multiple images from the prompt.
     *
     * @since n.e.x.t
     *
     * @param int|null $candidateCount The number of images to generate.
     * @return list<File> The generated image files.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     * @throws RuntimeException If no images are generated.
     */
    public function generateImages(?int $candidateCount = null): array
    {
        if ($candidateCount !== null) {
            $this->usingCandidateCount($candidateCount);
        }

        // Generate image result
        $results = $this->generateImageResult();
        $candidates = $results->getCandidates();

        // Extract image files from each candidate
        $images = [];
        foreach ($candidates as $candidate) {
            $message = $candidate->getMessage();
            $parts = $message->getParts();
            if (empty($parts)) {
                continue;
            }

            $file = $parts[0]->getFile();
            if ($file !== null) {
                $images[] = $file;
            }
        }

        if (empty($images)) {
            throw new RuntimeException('No images were generated from any candidates.');
        }

        return $images;
    }

    /**
     * Converts text to speech.
     *
     * @since n.e.x.t
     *
     * @return File The generated speech audio file.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     * @throws RuntimeException If no audio is generated.
     */
    public function convertTextToSpeech(): File
    {
        // Convert text to speech and extract audio from first candidate
        $result = $this->convertTextToSpeechResult();
        $candidates = $result->getCandidates();

        if (empty($candidates)) {
            throw new RuntimeException('No candidates were generated.');
        }

        $message = $candidates[0]->getMessage();
        $parts = $message->getParts();
        if (empty($parts)) {
            throw new RuntimeException('Generated message contains no parts.');
        }

        $file = $parts[0]->getFile();
        if ($file === null) {
            throw new RuntimeException('Generated message part contains no audio file.');
        }

        return $file;
    }

    /**
     * Converts text to multiple speech outputs.
     *
     * @since n.e.x.t
     *
     * @param int|null $candidateCount The number of speech outputs to generate.
     * @return list<File> The generated speech audio files.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     * @throws RuntimeException If no audio is generated.
     */
    public function convertTextToSpeeches(?int $candidateCount = null): array
    {
        if ($candidateCount !== null) {
            $this->usingCandidateCount($candidateCount);
        }

        // Convert text to speech
        $result = $this->convertTextToSpeechResult();

        // Extract audio files from each candidate
        $audioFiles = [];
        foreach ($result->getCandidates() as $candidate) {
            $message = $candidate->getMessage();
            $parts = $message->getParts();
            if (empty($parts)) {
                continue;
            }

            $file = $parts[0]->getFile();
            if ($file !== null) {
                $audioFiles[] = $file;
            }
        }

        if (empty($audioFiles)) {
            throw new RuntimeException('No audio files were generated from any candidates.');
        }

        return $audioFiles;
    }

    /**
     * Generates speech from the prompt.
     *
     * @since n.e.x.t
     *
     * @return File The generated speech audio file.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     * @throws RuntimeException If no audio is generated.
     */
    public function generateSpeech(): File
    {
        // Generate speech result and extract audio from first candidate
        $result = $this->generateSpeechResult();
        $candidates = $result->getCandidates();

        if (empty($candidates)) {
            throw new RuntimeException('No candidates were generated.');
        }

        // Get the audio file from the first message part
        $message = $candidates[0]->getMessage();
        $parts = $message->getParts();
        if (empty($parts)) {
            throw new RuntimeException('Generated message contains no parts.');
        }

        $file = $parts[0]->getFile();
        if ($file === null) {
            throw new RuntimeException('Generated message part contains no audio file.');
        }

        return $file;
    }

    /**
     * Generates multiple speech outputs from the prompt.
     *
     * @since n.e.x.t
     *
     * @param int|null $candidateCount The number of speech outputs to generate.
     * @return list<File> The generated speech audio files.
     * @throws InvalidArgumentException If the prompt or model validation fails.
     * @throws RuntimeException If no audio is generated.
     */
    public function generateSpeeches(?int $candidateCount = null): array
    {
        if ($candidateCount !== null) {
            $this->usingCandidateCount($candidateCount);
        }

        // Generate speech result
        $result = $this->generateSpeechResult();
        $candidates = $result->getCandidates();

        // Extract audio files from each candidate
        $audioFiles = [];
        foreach ($candidates as $candidate) {
            $message = $candidate->getMessage();
            $parts = $message->getParts();
            if (empty($parts)) {
                continue;
            }

            $file = $parts[0]->getFile();
            if ($file !== null) {
                $audioFiles[] = $file;
            }
        }

        if (empty($audioFiles)) {
            throw new RuntimeException('No audio files were generated from any candidates.');
        }

        return $audioFiles;
    }

    /**
     * Appends a MessagePart to the messages array.
     *
     * If the last message has a user role, the part is added to it.
     * Otherwise, a new UserMessage is created with the part.
     *
     * @since n.e.x.t
     *
     * @param MessagePart $part The part to append.
     * @return void
     */
    protected function appendPartToMessages(MessagePart $part): void
    {
        $lastMessage = end($this->messages);

        if ($lastMessage instanceof Message && $lastMessage->getRole()->isUser()) {
            // Replace the last message with a new one containing the appended part
            array_pop($this->messages);
            $this->messages[] = $lastMessage->withPart($part);
            return;
        }

        // Create new UserMessage with the part
        $this->messages[] = new UserMessage([$part]);
    }

    /**
     * Gets the model to use for generation.
     *
     * If a model has been explicitly set, validates it meets requirements and returns it.
     * Otherwise, finds a suitable model based on the prompt requirements.
     *
     * @since n.e.x.t
     *
     * @param ModelRequirements|null $requirements Optional requirements to use. If not provided, will be inferred.
     * @return ModelInterface The model to use.
     * @throws InvalidArgumentException If no suitable model is found or set model doesn't meet requirements.
     */
    public function getConfiguredModel(?ModelRequirements $requirements = null): ModelInterface
    {
        if ($requirements === null) {
            $requirements = $this->getModelRequirements();
        }

        // If a model has been explicitly set, return it
        if ($this->model !== null) {
            $this->model->setConfig($this->modelConfig);
            return $this->model;
        }

        // Find a suitable model based on requirements
        $modelsMetadata = $this->registry->findModelsMetadataForSupport($requirements);

        if (empty($modelsMetadata)) {
            throw new InvalidArgumentException(
                'No models found that support the required capabilities and options for this prompt. ' .
                'Required capabilities: ' . implode(', ', array_map(function ($cap) {
                    return $cap->value;
                }, $requirements->getRequiredCapabilities())) .
                '. Required options: ' . implode(', ', array_map(function ($opt) {
                    return $opt->getName() . '=' . json_encode($opt->getValue());
                }, $requirements->getRequiredOptions()))
            );
        }

        // Get the first available model from the first provider
        $firstProviderModels = $modelsMetadata[0];
        $firstModelMetadata = $firstProviderModels->getModels()[0];

        // Get the model instance from the provider
        return $this->registry->getProviderModel(
            $firstProviderModels->getProvider()->getId(),
            $firstModelMetadata->getId(),
            $this->modelConfig
        );
    }

    /**
     * Parses various input types into a Message with the given role.
     *
     * @since n.e.x.t
     *
     * @param mixed $input The input to parse.
     * @param MessageRoleEnum $role The role for the message.
     * @return Message The parsed message.
     * @throws InvalidArgumentException If the input type is not supported or results in empty message.
     */
    private function parseMessage($input, MessageRoleEnum $role): Message
    {
        // Handle Message input directly
        if ($input instanceof Message) {
            return $input;
        }

        // Handle single MessagePart
        if ($input instanceof MessagePart) {
            return new Message($role, [$input]);
        }

        // Handle string input
        if (is_string($input)) {
            if (trim($input) === '') {
                throw new InvalidArgumentException('Cannot create a message from an empty string.');
            }
            return new Message($role, [new MessagePart($input)]);
        }

        // Handle array input
        if (!is_array($input)) {
            throw new InvalidArgumentException(
                'Input must be a string, MessagePart, MessagePartArrayShape, ' .
                'a list of string|MessagePart|MessagePartArrayShape, or a Message instance.'
            );
        }

        // Check if it's a MessagePartArrayShape
        if (MessagePart::isArrayShape($input)) {
            return new Message($role, [MessagePart::fromArray($input)]);
        }

        // It should be a list of string|MessagePart|MessagePartArrayShape
        if (!array_is_list($input)) {
            throw new InvalidArgumentException('Array input must be a list array.');
        }

        // Empty array check
        if (empty($input)) {
            throw new InvalidArgumentException('Cannot create a message from an empty array.');
        }

        $parts = [];
        foreach ($input as $item) {
            if (is_string($item)) {
                $parts[] = new MessagePart($item);
            } elseif ($item instanceof MessagePart) {
                $parts[] = $item;
            } elseif (is_array($item) && MessagePart::isArrayShape($item)) {
                $parts[] = MessagePart::fromArray($item);
            } else {
                throw new InvalidArgumentException(
                    'Array items must be strings, MessagePart instances, or MessagePartArrayShape.'
                );
            }
        }

        return new Message($role, $parts);
    }

    /**
     * Validates the messages array for prompt generation.
     *
     * Ensures that:
     * - The first message is a user message
     * - The last message is a user message
     * - The last message has parts
     *
     * @since n.e.x.t
     *
     * @return void
     * @throws InvalidArgumentException If validation fails.
     */
    private function validateMessages(): void
    {
        if (empty($this->messages)) {
            throw new InvalidArgumentException(
                'Cannot generate from an empty prompt. Add content using withText() or similar methods.'
            );
        }

        $firstMessage = reset($this->messages);
        if (!$firstMessage->getRole()->isUser()) {
            throw new InvalidArgumentException(
                'The first message must be from a user role, not from ' . $firstMessage->getRole()->value
            );
        }

        $lastMessage = end($this->messages);
        if (!$lastMessage->getRole()->isUser()) {
            throw new InvalidArgumentException(
                'The last message must be from a user role, not from ' . $lastMessage->getRole()->value
            );
        }

        if (empty($lastMessage->getParts())) {
            throw new InvalidArgumentException(
                'The last message must have content parts. Add content using withText() or similar methods.'
            );
        }
    }

    /**
     * Checks if the value is a list of Message objects.
     *
     * @since n.e.x.t
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is a list of Message objects.
     *
     * @phpstan-assert-if-true list<Message> $value
     */
    private function isMessagesList($value): bool
    {
        if (!is_array($value) || empty($value) || !array_is_list($value)) {
            return false;
        }

        // Check if all items are Messages
        foreach ($value as $item) {
            if (!($item instanceof Message)) {
                return false;
            }
        }

        return true;
    }
}

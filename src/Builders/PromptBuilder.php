<?php

declare(strict_types=1);

namespace WordPress\AiClient\Builders;

use InvalidArgumentException;
use WordPress\AiClient\Common\Utilities\Prompts;
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
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
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
     * @phpstan-ignore-next-line
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

    /**
     * @var array<string, mixed> The inferred required options.
     */
    protected array $inferredOptions = [];

    /**
     * @var Message|null The system instruction message.
     */
    protected ?Message $systemInstruction = null;

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

        // Check if it's a single Message - add to messages
        if ($prompt instanceof Message) {
            $this->messages[] = $prompt;
            return;
        }

        // Check if it's a list of Messages - set as messages
        if (Prompts::isMessagesList($prompt)) {
            $this->messages = $prompt;
            return;
        }

        // Check if it's a MessageArrayShape - add to messages
        if (Prompts::isMessageArrayShape($prompt)) {
            $this->messages[] = Message::fromArray($prompt);
            return;
        }

        // Everything else becomes a UserMessage with parts
        $parts = [];

        if (is_string($prompt)) {
            $parts[] = new MessagePart($prompt);
        } elseif ($prompt instanceof MessagePart) {
            $parts[] = $prompt;
        } elseif (is_array($prompt)) {
            // It's a list of strings/MessageParts/MessagePartArrayShapes
            foreach ($prompt as $item) {
                if (is_string($item)) {
                    $parts[] = new MessagePart($item);
                } elseif ($item instanceof MessagePart) {
                    $parts[] = $item;
                } elseif (Prompts::isMessagePartArrayShape($item)) {
                    $parts[] = MessagePart::fromArray($item);
                }
            }
        }

        if (!empty($parts)) {
            $this->messages[] = new UserMessage($parts);
        }
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
     * @since n.e.x.t
     *
     * @param string|MessagePart[]|Message $systemInstruction The system instruction.
     * @return self
     */
    public function usingSystemInstruction($systemInstruction): self
    {
        $systemInstructionText = '';

        if ($systemInstruction instanceof Message) {
            $this->systemInstruction = $systemInstruction;
            // Extract text from message parts for ModelConfig
            foreach ($systemInstruction->getParts() as $part) {
                if ($part->getText() !== null) {
                    $systemInstructionText .= $part->getText() . ' ';
                }
            }
        } elseif (is_string($systemInstruction)) {
            $this->systemInstruction = new Message(
                MessageRoleEnum::system(),
                [new MessagePart($systemInstruction)]
            );
            $systemInstructionText = $systemInstruction;
        } elseif (is_array($systemInstruction)) {
            $this->systemInstruction = new Message(
                MessageRoleEnum::system(),
                $systemInstruction
            );
            // Extract text from message parts
            foreach ($systemInstruction as $part) {
                if ($part instanceof MessagePart && $part->getText() !== null) {
                    $systemInstructionText .= $part->getText() . ' ';
                }
            }
        }

        if (!empty($systemInstructionText)) {
            $this->modelConfig->setSystemInstruction(trim($systemInstructionText));
        }
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
        $this->inferredOptions[OptionEnum::outputMimeType()->value] = $mimeType;
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
        $this->inferredOptions[OptionEnum::outputSchema()->value] = true;
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
        $requiredOptions = [];
        foreach ($this->inferredOptions as $name => $value) {
            $requiredOptions[] = new RequiredOption($name, $value);
        }

        // TODO: Derive capabilities from messages and parts
        return new ModelRequirements(
            [],
            $requiredOptions
        );
    }

    /**
     * Checks if the current prompt is supported by the selected model.
     *
     * @since n.e.x.t
     *
     * @return bool True if supported, false otherwise.
     */
    public function isSupported(): bool
    {
        if ($this->model === null) {
            // Without a model selected, we can't determine support
            return true;
        }

        $requirements = $this->getModelRequirements();
        return $this->model->metadata()->meetsRequirements($requirements);
    }

    /**
     * Generates text from the prompt.
     *
     * @since n.e.x.t
     *
     * @return string The generated text.
     * @throws InvalidArgumentException If the selected model doesn't meet requirements.
     */
    public function generateText(): string
    {
        $this->validateModel();

        // This is a placeholder - actual implementation would call the model
        throw new \RuntimeException('Not implemented yet - requires AiClient integration.');
    }

    /**
     * Generates multiple text candidates from the prompt.
     *
     * @since n.e.x.t
     *
     * @param int|null $candidateCount The number of candidates to generate.
     * @return list<string> The generated texts.
     * @throws InvalidArgumentException If the selected model doesn't meet requirements.
     */
    public function generateTexts(?int $candidateCount = null): array
    {
        if ($candidateCount !== null) {
            $this->usingCandidateCount($candidateCount);
        }

        $this->validateModel();

        // This is a placeholder - actual implementation would call the model
        throw new \RuntimeException('Not implemented yet - requires AiClient integration.');
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
     * Validates that the selected model meets requirements.
     *
     * @since n.e.x.t
     *
     * @return void
     * @throws InvalidArgumentException If model doesn't meet requirements.
     */
    protected function validateModel(): void
    {
        if ($this->model === null) {
            // TODO: Use $this->registry to find a suitable model based on requirements
            // $requirements = $this->getModelRequirements();
            // $this->model = $this->registry->findModel($requirements);
            return;
        }

        $requirements = $this->getModelRequirements();
        if (!$this->model->metadata()->meetsRequirements($requirements)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The selected model "%s" does not meet the required capabilities and options for this prompt.',
                    $this->model->metadata()->getId()
                )
            );
        }
    }
}

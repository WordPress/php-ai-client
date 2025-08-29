<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\DTO;

use InvalidArgumentException;
use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

/**
 * Represents requirements that implementing code has for AI model selection.
 *
 * This class defines the capabilities and options that a model must support
 * in order to be considered suitable for the implementing code's needs.
 *
 * @since 0.1.0
 *
 * @phpstan-import-type RequiredOptionArrayShape from RequiredOption
 *
 * @phpstan-type ModelRequirementsArrayShape array{
 *     requiredCapabilities: list<string>,
 *     requiredOptions: list<RequiredOptionArrayShape>
 * }
 *
 * @extends AbstractDataTransferObject<ModelRequirementsArrayShape>
 */
class ModelRequirements extends AbstractDataTransferObject
{
    public const KEY_REQUIRED_CAPABILITIES = 'requiredCapabilities';
    public const KEY_REQUIRED_OPTIONS = 'requiredOptions';

    /**
     * @var list<CapabilityEnum> The capabilities that the model must support.
     */
    protected array $requiredCapabilities;

    /**
     * @var list<RequiredOption> The options that the model must support with specific values.
     */
    protected array $requiredOptions;

    /**
     * Constructor.
     *
     * @since 0.1.0
     *
     * @param list<CapabilityEnum> $requiredCapabilities The capabilities that the model must support.
     * @param list<RequiredOption> $requiredOptions The options that the model must support with specific values.
     *
     * @throws InvalidArgumentException If arrays are not lists.
     */
    public function __construct(array $requiredCapabilities, array $requiredOptions)
    {
        if (!array_is_list($requiredCapabilities)) {
            throw new InvalidArgumentException('Required capabilities must be a list array.');
        }

        if (!array_is_list($requiredOptions)) {
            throw new InvalidArgumentException('Required options must be a list array.');
        }

        $this->requiredCapabilities = $requiredCapabilities;
        $this->requiredOptions = $requiredOptions;
    }

    /**
     * Gets the capabilities that the model must support.
     *
     * @since 0.1.0
     *
     * @return list<CapabilityEnum> The required capabilities.
     */
    public function getRequiredCapabilities(): array
    {
        return $this->requiredCapabilities;
    }

    /**
     * Gets the options that the model must support with specific values.
     *
     * @since 0.1.0
     *
     * @return list<RequiredOption> The required options.
     */
    public function getRequiredOptions(): array
    {
        return $this->requiredOptions;
    }

    /**
     * Checks whether the given model metadata meets these requirements.
     *
     * @since n.e.x.t
     *
     * @param ModelMetadata $metadata The model metadata to check against.
     * @return bool True if the model meets all requirements, false otherwise.
     */
    public function areMetBy(ModelMetadata $metadata): bool
    {
        // Check if all required capabilities are supported
        foreach ($this->requiredCapabilities as $requiredCapability) {
            $supported = false;
            foreach ($metadata->getSupportedCapabilities() as $supportedCapability) {
                if ($supportedCapability->equals($requiredCapability)) {
                    $supported = true;
                    break;
                }
            }
            if (!$supported) {
                return false;
            }
        }

        // Check if all required options are supported with the specified values
        foreach ($this->requiredOptions as $requiredOption) {
            $optionSupported = false;
            $supportedOptions = $metadata->getSupportedOptions();

            foreach ($supportedOptions as $supportedOption) {
                if ($supportedOption->getName()->equals($requiredOption->getName())) {
                    // Check if the required value is supported by this option
                    if ($supportedOption->isSupportedValue($requiredOption->getValue())) {
                        $optionSupported = true;
                        break;
                    }
                }
            }

            // If no supported options at all, this is only OK if we have no required options
            if (!$optionSupported) {
                return false;
            }
        }

        return true;
    }

    /**
     * Creates ModelRequirements from prompt data and model configuration.
     *
     * @since n.e.x.t
     *
     * @param CapabilityEnum $capability The capability the model must support.
     * @param list<Message> $messages The messages in the conversation.
     * @param ModelConfig $modelConfig The model configuration.
     * @return self The created requirements.
     */
    public static function fromPromptData(CapabilityEnum $capability, array $messages, ModelConfig $modelConfig): self
    {
        // Start with base capability
        $capabilities = [$capability];
        $inputModalities = [];

        // Check if we have chat history (multiple messages)
        if (count($messages) > 1) {
            $capabilities[] = CapabilityEnum::chatHistory();
        }

        // Analyze all messages to determine required input modalities
        $hasFunctionMessageParts = false;
        foreach ($messages as $message) {
            foreach ($message->getParts() as $part) {
                // Check for text input
                if ($part->getType()->isText()) {
                    $inputModalities[] = ModalityEnum::text();
                }

                // Check for file inputs
                if ($part->getType()->isFile()) {
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

                // Check for function calls/responses (these might require special capabilities)
                if ($part->getType()->isFunctionCall() || $part->getType()->isFunctionResponse()) {
                    $hasFunctionMessageParts = true;
                }
            }
        }

        //
        // Convert ModelConfig to RequiredOptions (moved from ModelConfig::toRequiredOptions)
        $requiredOptions = [];

        // Map properties that have corresponding OptionEnum values
        if ($modelConfig->getOutputModalities() !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::outputModalities(),
                $modelConfig->getOutputModalities()
            );
        }

        if ($modelConfig->getSystemInstruction() !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::systemInstruction(),
                $modelConfig->getSystemInstruction()
            );
        }

        if ($modelConfig->getCandidateCount() !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::candidateCount(),
                $modelConfig->getCandidateCount()
            );
        }

        if ($modelConfig->getMaxTokens() !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::maxTokens(),
                $modelConfig->getMaxTokens()
            );
        }

        if ($modelConfig->getTemperature() !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::temperature(),
                $modelConfig->getTemperature()
            );
        }

        if ($modelConfig->getTopP() !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::topP(),
                $modelConfig->getTopP()
            );
        }

        if ($modelConfig->getTopK() !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::topK(),
                $modelConfig->getTopK()
            );
        }

        if ($modelConfig->getOutputMimeType() !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::outputMimeType(),
                $modelConfig->getOutputMimeType()
            );
        }

        if ($modelConfig->getOutputSchema() !== null) {
            $requiredOptions[] = new RequiredOption(
                OptionEnum::outputSchema(),
                $modelConfig->getOutputSchema()
            );
        }

        // Step 5: Add additional options based on message analysis
        if ($hasFunctionMessageParts) {
            $requiredOptions[] = new RequiredOption(OptionEnum::functionDeclarations(), true);
        }

        // Add input modalities if we have any inputs
        if (!empty($inputModalities)) {
            // Remove duplicates
            $inputModalities = array_unique($inputModalities, SORT_REGULAR);
            $requiredOptions[] = new RequiredOption(OptionEnum::inputModalities(), array_values($inputModalities));
        }

        // Step 6: Return new ModelRequirements
        return new self($capabilities, $requiredOptions);
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    public static function getJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                self::KEY_REQUIRED_CAPABILITIES => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => CapabilityEnum::getValues(),
                    ],
                    'description' => 'The capabilities that the model must support.',
                ],
                self::KEY_REQUIRED_OPTIONS => [
                    'type' => 'array',
                    'items' => RequiredOption::getJsonSchema(),
                    'description' => 'The options that the model must support with specific values.',
                ],
            ],
            'required' => [self::KEY_REQUIRED_CAPABILITIES, self::KEY_REQUIRED_OPTIONS],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     *
     * @return ModelRequirementsArrayShape
     */
    public function toArray(): array
    {
        return [
            self::KEY_REQUIRED_CAPABILITIES => array_map(
                static fn(CapabilityEnum $capability): string => $capability->value,
                $this->requiredCapabilities
            ),
            self::KEY_REQUIRED_OPTIONS => array_map(
                static fn(RequiredOption $option): array => $option->toArray(),
                $this->requiredOptions
            ),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    public static function fromArray(array $array): self
    {
        static::validateFromArrayData($array, [self::KEY_REQUIRED_CAPABILITIES, self::KEY_REQUIRED_OPTIONS]);

        return new self(
            array_map(
                static fn(string $capability): CapabilityEnum => CapabilityEnum::from($capability),
                $array[self::KEY_REQUIRED_CAPABILITIES]
            ),
            array_map(
                static fn(array $optionData): RequiredOption => RequiredOption::fromArray($optionData),
                $array[self::KEY_REQUIRED_OPTIONS]
            )
        );
    }
}

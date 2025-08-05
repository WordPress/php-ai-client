<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use InvalidArgumentException;
use RuntimeException;
use WordPress\AiClient\Common\AbstractDataTransferObject;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Represents a part of a message.
 *
 * Messages can contain multiple parts of different types, such as text, files,
 * function calls, etc. This DTO encapsulates one such part.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type FileArrayShape from File
 * @phpstan-import-type FunctionCallArrayShape from FunctionCall
 * @phpstan-import-type FunctionResponseArrayShape from FunctionResponse
 *
 * @phpstan-type MessagePartArrayShape array{
 *     type: string,
 *     text?: string,
 *     file?: FileArrayShape,
 *     functionCall?: FunctionCallArrayShape,
 *     functionResponse?: FunctionResponseArrayShape
 * }
 *
 * @extends AbstractDataTransferObject<MessagePartArrayShape>
 */
class MessagePart extends AbstractDataTransferObject
{
    public const KEY_TYPE = 'type';
    public const KEY_TEXT = 'text';
    public const KEY_FILE = 'file';
    public const KEY_FUNCTION_CALL = 'functionCall';
    public const KEY_FUNCTION_RESPONSE = 'functionResponse';
    /**
     * @var MessagePartTypeEnum The type of this message part.
     */
    private MessagePartTypeEnum $type;

    /**
     * @var string|null Text content (when type is TEXT).
     */
    private ?string $text = null;

    /**
     * @var File|null File data (when type is FILE).
     */
    private ?File $file = null;

    /**
     * @var FunctionCall|null Function call request (when type is FUNCTION_CALL).
     */
    private ?FunctionCall $functionCall = null;

    /**
     * @var FunctionResponse|null Function response (when type is FUNCTION_RESPONSE).
     */
    private ?FunctionResponse $functionResponse = null;

    /**
     * Constructor that accepts various content types and infers the message part type.
     *
     * @since n.e.x.t
     *
     * @param mixed $content The content of this message part.
     * @throws InvalidArgumentException If an unsupported content type is provided.
     */
    public function __construct($content)
    {
        if (is_string($content)) {
            $this->type = MessagePartTypeEnum::text();
            $this->text = $content;
        } elseif ($content instanceof File) {
            $this->type = MessagePartTypeEnum::file();
            $this->file = $content;
        } elseif ($content instanceof FunctionCall) {
            $this->type = MessagePartTypeEnum::functionCall();
            $this->functionCall = $content;
        } elseif ($content instanceof FunctionResponse) {
            $this->type = MessagePartTypeEnum::functionResponse();
            $this->functionResponse = $content;
        } else {
            $type = is_object($content) ? get_class($content) : gettype($content);
            throw new InvalidArgumentException(
                sprintf(
                    'Unsupported content type %s. Expected string, File, '
                    . 'FunctionCall, or FunctionResponse.',
                    $type
                )
            );
        }
    }

    /**
     * Gets the type of this message part.
     *
     * @since n.e.x.t
     *
     * @return MessagePartTypeEnum The type.
     */
    public function getType(): MessagePartTypeEnum
    {
        return $this->type;
    }

    /**
     * Gets the text content.
     *
     * @since n.e.x.t
     *
     * @return string|null The text content or null if not a text part.
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Gets the file.
     *
     * @since n.e.x.t
     *
     * @return File|null The file or null if not a file part.
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * Gets the function call.
     *
     * @since n.e.x.t
     *
     * @return FunctionCall|null The function call or null if not a function call part.
     */
    public function getFunctionCall(): ?FunctionCall
    {
        return $this->functionCall;
    }

    /**
     * Gets the function response.
     *
     * @since n.e.x.t
     *
     * @return FunctionResponse|null The function response or null if not a function response part.
     */
    public function getFunctionResponse(): ?FunctionResponse
    {
        return $this->functionResponse;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function getJsonSchema(): array
    {
        return [
            'oneOf' => [
                [
                    'type' => 'object',
                    'properties' => [
                        self::KEY_TYPE => [
                            'type' => 'string',
                            'const' => MessagePartTypeEnum::text()->value,
                        ],
                        self::KEY_TEXT => [
                            'type' => 'string',
                            'description' => 'Text content.',
                        ],
                    ],
                    'required' => [self::KEY_TYPE, self::KEY_TEXT],
                    'additionalProperties' => false,
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        self::KEY_TYPE => [
                            'type' => 'string',
                            'const' => MessagePartTypeEnum::file()->value,
                        ],
                        self::KEY_FILE => File::getJsonSchema(),
                    ],
                    'required' => [self::KEY_TYPE, self::KEY_FILE],
                    'additionalProperties' => false,
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        self::KEY_TYPE => [
                            'type' => 'string',
                            'const' => MessagePartTypeEnum::functionCall()->value,
                        ],
                        self::KEY_FUNCTION_CALL => FunctionCall::getJsonSchema(),
                    ],
                    'required' => [self::KEY_TYPE, self::KEY_FUNCTION_CALL],
                    'additionalProperties' => false,
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        self::KEY_TYPE => [
                            'type' => 'string',
                            'const' => MessagePartTypeEnum::functionResponse()->value,
                        ],
                        self::KEY_FUNCTION_RESPONSE => FunctionResponse::getJsonSchema(),
                    ],
                    'required' => [self::KEY_TYPE, self::KEY_FUNCTION_RESPONSE],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     *
     * @return MessagePartArrayShape
     */
    public function toArray(): array
    {
        $data = [self::KEY_TYPE => $this->type->value];

        if ($this->text !== null) {
            $data[self::KEY_TEXT] = $this->text;
        } elseif ($this->file !== null) {
            $data[self::KEY_FILE] = $this->file->toArray();
        } elseif ($this->functionCall !== null) {
            $data[self::KEY_FUNCTION_CALL] = $this->functionCall->toArray();
        } elseif ($this->functionResponse !== null) {
            $data[self::KEY_FUNCTION_RESPONSE] = $this->functionResponse->toArray();
        } else {
            throw new RuntimeException(
                'MessagePart requires one of: text, file, functionCall, or functionResponse. '
                . 'This should not be a possible condition.'
            );
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        // Check which properties are set to determine how to construct the MessagePart
        if (isset($array[self::KEY_TEXT])) {
            return new self($array[self::KEY_TEXT]);
        } elseif (isset($array[self::KEY_FILE])) {
            return new self(File::fromArray($array[self::KEY_FILE]));
        } elseif (isset($array[self::KEY_FUNCTION_CALL])) {
            return new self(FunctionCall::fromArray($array[self::KEY_FUNCTION_CALL]));
        } elseif (isset($array[self::KEY_FUNCTION_RESPONSE])) {
            return new self(FunctionResponse::fromArray($array[self::KEY_FUNCTION_RESPONSE]));
        } else {
            throw new InvalidArgumentException(
                'MessagePart requires one of: text, file, functionCall, or functionResponse.'
            );
        }
    }
}

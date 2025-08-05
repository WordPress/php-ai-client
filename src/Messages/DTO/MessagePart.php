<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use InvalidArgumentException;
use RuntimeException;
use WordPress\AiClient\Common\AbstractDataValueObject;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\Contracts\MessageContentInterface;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;
use WordPress\AiClient\Messages\ValueObjects\TextContent;
use WordPress\AiClient\Messages\ValueObjects\FileContent;
use WordPress\AiClient\Messages\ValueObjects\FunctionCallContent;
use WordPress\AiClient\Messages\ValueObjects\FunctionResponseContent;

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
 * @extends AbstractDataValueObject<MessagePartArrayShape>
 */
class MessagePart extends AbstractDataValueObject
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
     * @var MessageContentInterface The content of this message part.
     */
    private $content;

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
    public function __construct(MessageContentInterface $content)
    {
        $this->content = $content;
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
        return $this->content->getMessagePartType();
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
        return $this->content->getText();
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
        return $this->content->getFile();
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
        return $this->content->getFunctionCall();
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
        return $this->content->getFunctionResponse();
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
        return $this->content->toArray();
    }

    /**
     * {@inheritDoc}
     *
     * @since n.e.x.t
     */
    public static function fromArray(array $array): self
    {
        $factories = [
            self::KEY_TEXT => function ($data) {
                return new TextContent($data);
            },
            self::KEY_FILE => function ($data) {
                return new FileContent(File::fromArray($data));
            },
            self::KEY_FUNCTION_CALL => function ($data) {
                return new FunctionCallContent(FunctionCall::fromArray($data));
            },
            self::KEY_FUNCTION_RESPONSE => function ($data) {
                return new FunctionResponseContent(FunctionResponse::fromArray($data));
            },
        ];

        foreach ($factories as $key => $factory) {
            if (isset($array[$key])) {
                return new self($factory($array[$key]));
            }
        }

        throw new InvalidArgumentException(
            'MessagePart requires one of: text, file, functionCall, or functionResponse.'
        );
    }
}

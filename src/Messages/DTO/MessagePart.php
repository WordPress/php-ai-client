<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
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
 */
class MessagePart implements WithJsonSchemaInterface
{
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
     * @throws \InvalidArgumentException If an unsupported content type is provided.
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
            throw new \InvalidArgumentException(
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
                        'type' => [
                            'type' => 'string',
                            'const' => MessagePartTypeEnum::text()->value,
                        ],
                        'text' => [
                            'type' => 'string',
                            'description' => 'Text content.',
                        ],
                    ],
                    'required' => ['type', 'text'],
                    'additionalProperties' => false,
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'const' => MessagePartTypeEnum::file()->value,
                        ],
                        'file' => File::getJsonSchema(),
                    ],
                    'required' => ['type', 'file'],
                    'additionalProperties' => false,
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'const' => MessagePartTypeEnum::functionCall()->value,
                        ],
                        'functionCall' => FunctionCall::getJsonSchema(),
                    ],
                    'required' => ['type', 'functionCall'],
                    'additionalProperties' => false,
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'const' => MessagePartTypeEnum::functionResponse()->value,
                        ],
                        'functionResponse' => FunctionResponse::getJsonSchema(),
                    ],
                    'required' => ['type', 'functionResponse'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}

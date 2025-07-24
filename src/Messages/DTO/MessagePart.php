<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO;

use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Files\DTO\InlineFile;
use WordPress\AiClient\Files\DTO\RemoteFile;
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
     * @var InlineFile|null Inline file data (when type is INLINE_FILE).
     */
    private ?InlineFile $inlineFile = null;

    /**
     * @var RemoteFile|null Remote file reference (when type is REMOTE_FILE).
     */
    private ?RemoteFile $remoteFile = null;

    /**
     * @var FunctionCall|null Function call request (when type is FUNCTION_CALL).
     */
    private ?FunctionCall $functionCall = null;

    /**
     * @var FunctionResponse|null Function response (when type is FUNCTION_RESPONSE).
     */
    private ?FunctionResponse $functionResponse = null;

    /**
     * Private constructor to enforce factory method usage.
     *
     * @since n.e.x.t
     *
     * @param MessagePartTypeEnum $type The type of this message part.
     */
    private function __construct(MessagePartTypeEnum $type)
    {
        $this->type = $type;
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
     * Gets the inline file.
     *
     * @since n.e.x.t
     *
     * @return InlineFile|null The inline file or null if not an inline file part.
     */
    public function getInlineFile(): ?InlineFile
    {
        return $this->inlineFile;
    }

    /**
     * Gets the remote file.
     *
     * @since n.e.x.t
     *
     * @return RemoteFile|null The remote file or null if not a remote file part.
     */
    public function getRemoteFile(): ?RemoteFile
    {
        return $this->remoteFile;
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
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => ['text', 'inline_file', 'remote_file', 'function_call', 'function_response'],
                    'description' => 'The type of this message part.',
                ],
                'text' => [
                    'type' => ['string', 'null'],
                    'description' => 'Text content (when type is text).',
                ],
                'inlineFile' => [
                    'oneOf' => [
                        ['type' => 'null'],
                        InlineFile::getJsonSchema(),
                    ],
                    'description' => 'Inline file data (when type is inline_file).',
                ],
                'remoteFile' => [
                    'oneOf' => [
                        ['type' => 'null'],
                        RemoteFile::getJsonSchema(),
                    ],
                    'description' => 'Remote file reference (when type is remote_file).',
                ],
                'functionCall' => [
                    'oneOf' => [
                        ['type' => 'null'],
                        FunctionCall::getJsonSchema(),
                    ],
                    'description' => 'Function call request (when type is function_call).',
                ],
                'functionResponse' => [
                    'oneOf' => [
                        ['type' => 'null'],
                        FunctionResponse::getJsonSchema(),
                    ],
                    'description' => 'Function response (when type is function_response).',
                ],
            ],
            'required' => ['type'],
        ];
    }
}

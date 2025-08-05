<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\ValueObjects;

use WordPress\AiClient\Tools\DTO\FunctionResponse;
use WordPress\AiClient\Messages\Contracts\MessageContentInterface;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\ValueObjects\ContentGettersTrait;

/**
 * Value object representing function response content.
 *
 * This immutable value object encapsulates function response content and provides
 * convenient methods for accessing and manipulating it.
 *
 * @since n.e.x.t
 */
final class FunctionResponseContent implements MessageContentInterface
{
    /**
     * @use ContentGettersTrait
     */
    use ContentGettersTrait;

    /**
     * The function response content.
     *
     * @since n.e.x.t
     */
    private FunctionResponse $functionResponse;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param FunctionResponse $functionResponse The function response content.
     */
    public function __construct(FunctionResponse $functionResponse)
    {
        $this->functionResponse = $functionResponse;
    }

    /**
     * Gets the type of the function response content.
     *
     * @since n.e.x.t
     *
     * @return MessagePartTypeEnum Instance of the 'FUNCTION_RESPONSE' type.
     */
    public function getMessagePartType(): MessagePartTypeEnum
    {
        return MessagePartTypeEnum::functionResponse();
    }

    /**
     * Gets the function response content.
     *
     * @since n.e.x.t
     *
     * @return FunctionResponse The function response content.
     */
    public function getFunctionResponse(): FunctionResponse
    {
        return $this->functionResponse;
    }

    /**
     * Converts the function response content to an array.
     *
     * @since n.e.x.t
     *
     * @return array The function response content as an array.
     */
    public function toArray(): array
    {
        return [MessagePart::KEY_FUNCTION_RESPONSE => $this->functionResponse->toArray()];
    }
}

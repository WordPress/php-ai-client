<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\ValueObjects;

use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Messages\Contracts\MessageContentInterface;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;

final class FunctionCallContent implements MessageContentInterface
{
    /**
     * The function call content.
     *
     * @since n.e.x.t
     */
    private FunctionCall $functionCall;

    /**
     * Constructor.
     *
     * @since n.e.x.t
     *
     * @param FunctionCall $functionCall The function call content.
     */
    public function __construct(FunctionCall $functionCall)
    {
        $this->functionCall = $functionCall;
    }

    /**
     * Gets the type of the function call content.
     *
     * @since n.e.x.t
     *
     * @return MessagePartTypeEnum Instance of the 'FUNCTION_CALL' type.
     */
    public function getMessagePartType(): MessagePartTypeEnum
    {
        return MessagePartTypeEnum::functionCall();
    }

    /**
     * Gets the function call content.
     *
     * @since n.e.x.t
     *
     * @return FunctionCall The function call content.
     */
    public function getFunctionCall(): FunctionCall
    {
        return $this->functionCall;
    }

    /**
     * Converts the function call content to an array.
     *
     * @since n.e.x.t
     *
     * @return array The function call content as an array.
     */
    public function toArray(): array
    {
        return [ $this->getMessagePartType()->value => $this->functionCall->toArray() ];
    }
}

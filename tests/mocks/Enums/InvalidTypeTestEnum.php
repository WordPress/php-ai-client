<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks\Enums;

use WordPress\AiClient\Common\AbstractEnum;

/**
 * Invalid test enum with float value.
 */
class InvalidTypeTestEnum extends AbstractEnum
{
    public const VALID_VALUE = 'valid';
    public const INT_VALUE = 42; // This should cause an exception
}

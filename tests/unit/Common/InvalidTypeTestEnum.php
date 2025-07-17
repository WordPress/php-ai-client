<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Common;

use WordPress\AiClient\Common\AbstractEnum;

/**
 * Invalid test enum with float value
 */
class InvalidTypeTestEnum extends AbstractEnum
{
    public const VALID_VALUE = 'valid';
    public const FLOAT_VALUE = 3.14; // This should cause an exception
}

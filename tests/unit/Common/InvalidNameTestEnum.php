<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Common;

use WordPress\AiClient\Common\AbstractEnum;

/**
 * Invalid test enum with lowercase constant name
 */
class InvalidNameTestEnum extends AbstractEnum
{
    public const VALID_NAME = 'valid';
    // phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
    public const invalid_name = 'invalid'; // This should cause an exception
}

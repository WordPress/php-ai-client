<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Common;

use WordPress\AiClient\Common\AbstractEnum;

/**
 * Valid test enum for testing AbstractEnum functionality
 *
 * @method static self firstName() Create an instance for FIRST_NAME
 * @method static self lastName() Create an instance for LAST_NAME
 * @method static self age() Create an instance for AGE
 * @method bool isFirstName() Check if the value is FIRST_NAME
 * @method bool isLastName() Check if the value is LAST_NAME
 * @method bool isAge() Check if the value is AGE
 */
class ValidTestEnum extends AbstractEnum
{
    public const FIRST_NAME = 'first';
    public const LAST_NAME = 'last';
    public const AGE = 42;
}

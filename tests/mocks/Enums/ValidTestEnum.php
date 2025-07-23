<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\mocks\Enums;

use WordPress\AiClient\Common\AbstractEnum;

/**
 * Valid test enum for testing AbstractEnum functionality.
 *
 * @method static self firstName() Creates an instance for FIRST_NAME.
 * @method static self lastName() Creates an instance for LAST_NAME.
 * @method bool isFirstName() Checks if the value is FIRST_NAME.
 * @method bool isLastName() Checks if the value is LAST_NAME.
 */
class ValidTestEnum extends AbstractEnum
{
    public const FIRST_NAME = 'first';
    public const LAST_NAME = 'last';
}

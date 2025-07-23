<?php

declare(strict_types=1);

namespace WordPress\AiClient\Messages\Enums;

use WordPress\AiClient\Common\AbstractEnum;

/**
 * Enum for message roles in AI conversations.
 *
 * @since n.e.x.t
 *
 * @method static self user() Creates an instance for USER role.
 * @method static self model() Creates an instance for MODEL role.
 * @method static self system() Creates an instance for SYSTEM role.
 * @method bool isUser() Checks if the role is USER.
 * @method bool isModel() Checks if the role is MODEL.
 * @method bool isSystem() Checks if the role is SYSTEM.
 */
class MessageRoleEnum extends AbstractEnum
{
    /**
     * User role - messages from the user.
     */
    public const USER = 'user';

    /**
     * Model role - messages from the AI model.
     */
    public const MODEL = 'model';

    /**
     * System role - system instructions.
     */
    public const SYSTEM = 'system';
}

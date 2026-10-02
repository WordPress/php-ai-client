<?php

declare(strict_types=1);

namespace WordPress\AiClient\Providers\Models\Classification\Enums;

use WordPress\AiClient\Common\AbstractEnum;

/**
 * Enum for the types of question a classification model can answer.
 *
 * @since n.e.x.t
 *
 * @method static self binary() Creates an instance for BINARY type.
 * @method static self choice() Creates an instance for CHOICE type.
 * @method static self score() Creates an instance for SCORE type.
 * @method bool isBinary() Checks if the type is BINARY.
 * @method bool isChoice() Checks if the type is CHOICE.
 * @method bool isScore() Checks if the type is SCORE.
 */
class ClassificationQuestionTypeEnum extends AbstractEnum
{
    /**
     * Whether a statement is true, answered with the probability that it is.
     */
    public const BINARY = 'binary';

    /**
     * A choice among named options, answered with the chosen option.
     */
    public const CHOICE = 'choice';

    /**
     * A position on a scale of ordered levels, answered with the position on the scale.
     */
    public const SCORE = 'score';
}

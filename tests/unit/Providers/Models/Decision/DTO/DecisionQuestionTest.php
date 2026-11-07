<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\Decision\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\Models\Decision\DTO\DecisionOption;
use WordPress\AiClient\Providers\Models\Decision\DTO\DecisionQuestion;
use WordPress\AiClient\Providers\Models\Decision\Enums\DecisionQuestionTypeEnum;

/**
 * @covers \WordPress\AiClient\Providers\Models\Decision\DTO\DecisionQuestion
 */
class DecisionQuestionTest extends TestCase
{
    /**
     * Tests creating a binary question.
     *
     * @return void
     */
    public function testCreateBinaryQuestion(): void
    {
        $question = new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Is this comment spam?');

        $this->assertTrue($question->getType()->isBinary());
        $this->assertSame('Is this comment spam?', $question->getInstructions());
        $this->assertSame([], $question->getOptions());
        $this->assertSame([], $question->getLevels());
        $this->assertSame(
            [
                DecisionQuestion::KEY_TYPE => 'binary',
                DecisionQuestion::KEY_INSTRUCTIONS => 'Is this comment spam?',
            ],
            $question->toArray()
        );
    }

    /**
     * Tests creating a choice question from option values and options with descriptions.
     *
     * @return void
     */
    public function testCreateChoiceQuestion(): void
    {
        $approve = new DecisionOption('approve', 'The comment is fine to publish.');

        $question = new DecisionQuestion(
            DecisionQuestionTypeEnum::choice(),
            'How should a moderator handle it?',
            [$approve, 'hold']
        );

        $this->assertTrue($question->getType()->isChoice());
        $this->assertSame([], $question->getLevels());

        $options = $question->getOptions();
        $this->assertCount(2, $options);
        $this->assertSame($approve, $options[0]);
        $this->assertSame('hold', $options[1]->getValue());
        $this->assertNull($options[1]->getDescription());

        $this->assertSame(
            [
                ['value' => 'approve', 'description' => 'The comment is fine to publish.'],
                ['value' => 'hold'],
            ],
            $question->toArray()[DecisionQuestion::KEY_OPTIONS]
        );
    }

    /**
     * Tests that a choice question accepts option values that PHP would turn into integer array keys.
     *
     * @return void
     */
    public function testChoiceQuestionAcceptsIntegerLikeOptionValues(): void
    {
        $question = new DecisionQuestion(
            DecisionQuestionTypeEnum::choice(),
            'How many stars?',
            ['1', '2', new DecisionOption('3', 'Excellent.')]
        );

        $this->assertSame(
            ['1', '2', '3'],
            array_map(
                static function (DecisionOption $option): string {
                    return $option->getValue();
                },
                $question->getOptions()
            )
        );
    }

    /**
     * Tests creating a score question.
     *
     * @return void
     */
    public function testCreateScoreQuestion(): void
    {
        $levels = ['Not toxic.', 'Somewhat toxic.', 'Very toxic.'];

        $question = new DecisionQuestion(
            DecisionQuestionTypeEnum::score(),
            'How toxic is the comment?',
            $levels
        );

        $this->assertTrue($question->getType()->isScore());
        $this->assertSame($levels, $question->getLevels());
        $this->assertSame([], $question->getOptions());
        $this->assertSame($levels, $question->toArray()[DecisionQuestion::KEY_LEVELS]);
    }

    /**
     * Tests array round trip, including through JSON.
     *
     * @dataProvider provideQuestions
     *
     * @param DecisionQuestion $question The question.
     * @return void
     */
    public function testArrayRoundTrip(DecisionQuestion $question): void
    {
        $this->assertEquals($question, DecisionQuestion::fromArray($question->toArray()));

        $decoded = json_decode((string) json_encode($question->toArray()), true);
        $this->assertEquals($question, DecisionQuestion::fromArray($decoded));
    }

    /**
     * Provides a question of each type.
     *
     * @return array<string, array{DecisionQuestion}>
     */
    public function provideQuestions(): array
    {
        return [
            'binary' => [new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Is it spam?')],
            'choice' => [
                new DecisionQuestion(
                    DecisionQuestionTypeEnum::choice(),
                    'How should a moderator handle it?',
                    [new DecisionOption('approve', 'Publish it.'), 'trash']
                ),
            ],
            'choice with integer-like values' => [
                new DecisionQuestion(DecisionQuestionTypeEnum::choice(), 'How many stars?', ['1', '2', '3']),
            ],
            'score' => [
                new DecisionQuestion(
                    DecisionQuestionTypeEnum::score(),
                    'How toxic is it?',
                    ['Not toxic.', 'Very toxic.']
                ),
            ],
        ];
    }

    /**
     * Tests that empty instructions are rejected.
     *
     * @return void
     */
    public function testRejectsEmptyInstructions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision question instructions cannot be empty.');

        new DecisionQuestion(DecisionQuestionTypeEnum::binary(), '  ');
    }

    /**
     * Tests that binary questions reject criteria.
     *
     * @return void
     */
    public function testBinaryQuestionRejectsCriteria(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Binary decision questions do not accept criteria.');

        new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Is this comment spam?', ['yes', 'no']);
    }

    /**
     * Tests that choice and score questions require a list of at least two criteria.
     *
     * @dataProvider provideInvalidCriteriaLists
     *
     * @param DecisionQuestionTypeEnum $type The question type.
     * @param array<mixed> $criteria Criteria that are too few or not a list.
     * @return void
     */
    public function testRequiresListOfAtLeastTwoCriteria(DecisionQuestionTypeEnum $type, array $criteria): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('Decision questions of type "%s" require a list of at least two criteria.', $type->value)
        );

        new DecisionQuestion($type, 'Pick one.', $criteria);
    }

    /**
     * Provides criteria that are too few or not a list, for each question type that requires criteria.
     *
     * @return array<string, array{DecisionQuestionTypeEnum, array<mixed>}>
     */
    public function provideInvalidCriteriaLists(): array
    {
        $choice = DecisionQuestionTypeEnum::choice();
        $score = DecisionQuestionTypeEnum::score();

        return [
            'single option' => [$choice, ['only']],
            'options keyed by value' => [$choice, ['approve' => 'Publish it.', 'trash' => 'Remove it.']],
            'single level' => [$score, ['The only level.']],
            'named levels' => [$score, ['low' => 'Not toxic.', 'high' => 'Very toxic.']],
            'levels counted from one' => [$score, [1 => 'Not toxic.', 2 => 'Very toxic.']],
        ];
    }

    /**
     * Tests that choice questions reject options that are neither values nor DecisionOption instances.
     *
     * @return void
     */
    public function testChoiceQuestionRejectsInvalidOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Choice decision question options must be option values or DecisionOption instances.'
        );

        new DecisionQuestion(DecisionQuestionTypeEnum::choice(), 'How many stars?', [1, 2]);
    }

    /**
     * Tests that choice questions reject empty option values.
     *
     * @return void
     */
    public function testChoiceQuestionRejectsEmptyOptionValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision option values cannot be empty.');

        new DecisionQuestion(DecisionQuestionTypeEnum::choice(), 'Pick one.', ['approve', ' ']);
    }

    /**
     * Tests that choice questions reject options with the same value.
     *
     * @return void
     */
    public function testChoiceQuestionRejectsDuplicateOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Choice decision question options must be unique, but "approve" is given more than once.'
        );

        new DecisionQuestion(
            DecisionQuestionTypeEnum::choice(),
            'Pick one.',
            ['approve', 'trash', new DecisionOption('approve', 'Publish it.')]
        );
    }

    /**
     * Tests that score question levels must be non-empty descriptions.
     *
     * @dataProvider provideInvalidLevels
     *
     * @param array<mixed> $levels The levels with an invalid description.
     * @return void
     */
    public function testScoreQuestionRejectsInvalidLevels(array $levels): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Score decision question levels must be non-empty descriptions.');

        new DecisionQuestion(DecisionQuestionTypeEnum::score(), 'How toxic is it?', $levels);
    }

    /**
     * Provides levels with an invalid description.
     *
     * @return array<string, array{array<mixed>}>
     */
    public function provideInvalidLevels(): array
    {
        return [
            'non-string level' => [['Not toxic.', null]],
            'empty level' => [['Not toxic.', '']],
            'option instead of level' => [['Not toxic.', new DecisionOption('high', 'Very toxic.')]],
        ];
    }

    /**
     * Tests that fromArray() rejects data without required keys.
     *
     * @return void
     */
    public function testFromArrayRequiresTypeAndInstructions(): void
    {
        $this->assertFalse(DecisionQuestion::isArrayShape(['type' => 'binary']));
        $this->assertFalse(DecisionQuestion::isArrayShape(['type' => 'unknown', 'instructions' => 'Why?']));
        $this->assertTrue(DecisionQuestion::isArrayShape(['type' => 'binary', 'instructions' => 'Why?']));
    }

    /**
     * Tests the JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = DecisionQuestion::getJsonSchema();

        $this->assertSame(
            ['binary', 'choice', 'score'],
            $schema['properties'][DecisionQuestion::KEY_TYPE]['enum']
        );
        $this->assertSame(
            DecisionOption::getJsonSchema(),
            $schema['properties'][DecisionQuestion::KEY_OPTIONS]['items']
        );
        $this->assertSame('array', $schema['properties'][DecisionQuestion::KEY_LEVELS]['type']);
        $this->assertSame(
            [DecisionQuestion::KEY_TYPE, DecisionQuestion::KEY_INSTRUCTIONS],
            $schema['required']
        );
    }
}

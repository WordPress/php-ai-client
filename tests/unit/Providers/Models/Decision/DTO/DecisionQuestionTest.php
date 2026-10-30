<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\Decision\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
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
        $this->assertSame([], $question->getCriteria());
        $this->assertSame(
            [
                DecisionQuestion::KEY_TYPE => 'binary',
                DecisionQuestion::KEY_INSTRUCTIONS => 'Is this comment spam?',
            ],
            $question->toArray()
        );
    }

    /**
     * Tests creating a choice question.
     *
     * @return void
     */
    public function testCreateChoiceQuestion(): void
    {
        $criteria = [
            'approve' => 'The comment is fine to publish.',
            'hold' => 'The comment needs a closer look.',
            'trash' => 'The comment should be removed.',
        ];

        $question = new DecisionQuestion(
            DecisionQuestionTypeEnum::choice(),
            'How should a moderator handle it?',
            $criteria
        );

        $this->assertTrue($question->getType()->isChoice());
        $this->assertSame($criteria, $question->getCriteria());
        $this->assertSame($criteria, $question->toArray()[DecisionQuestion::KEY_CRITERIA]);
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
        $this->assertSame($levels, $question->getCriteria());
        $this->assertSame($levels, $question->toArray()[DecisionQuestion::KEY_CRITERIA]);
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
                    ['approve' => 'Publish it.', 'trash' => 'Remove it.']
                ),
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
     * Tests that a choice question serializes its options as a JSON object.
     *
     * @return void
     */
    public function testChoiceCriteriaEncodeAsJsonObject(): void
    {
        $question = new DecisionQuestion(
            DecisionQuestionTypeEnum::choice(),
            'How should a moderator handle it?',
            ['approve' => 'Publish it.', 'trash' => 'Remove it.']
        );

        $this->assertStringContainsString(
            '"criteria":{"approve":"Publish it.","trash":"Remove it."}',
            (string) json_encode($question->toArray())
        );
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

        new DecisionQuestion(
            DecisionQuestionTypeEnum::binary(),
            'Is this comment spam?',
            ['yes' => 'It is spam.', 'no' => 'It is not spam.']
        );
    }

    /**
     * Tests that choice and score questions require at least two criteria.
     *
     * @dataProvider provideSingleCriterion
     *
     * @param DecisionQuestionTypeEnum $type The question type.
     * @param array<string, string>|list<string> $criteria A single criterion of the shape the type takes.
     * @return void
     */
    public function testRequiresAtLeastTwoCriteria(DecisionQuestionTypeEnum $type, array $criteria): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('Decision questions of type "%s" require at least two criteria.', $type->value)
        );

        new DecisionQuestion($type, 'Pick one.', $criteria);
    }

    /**
     * Provides a single criterion for each question type that requires criteria.
     *
     * @return array<string, array{DecisionQuestionTypeEnum, array<string, string>|list<string>}>
     */
    public function provideSingleCriterion(): array
    {
        return [
            'choice' => [DecisionQuestionTypeEnum::choice(), ['only' => 'The only option.']],
            'score' => [DecisionQuestionTypeEnum::score(), ['The only level.']],
        ];
    }

    /**
     * Tests that choice questions reject option keys that are not names.
     *
     * @dataProvider provideInvalidChoiceCriteria
     *
     * @param array<mixed> $criteria The invalid criteria.
     * @return void
     */
    public function testChoiceQuestionRejectsInvalidOptionKeys(array $criteria): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Choice decision question option keys must be non-empty, non-integer strings.'
        );

        new DecisionQuestion(DecisionQuestionTypeEnum::choice(), 'Pick one.', $criteria);
    }

    /**
     * Provides choice criteria with invalid option keys.
     *
     * @return array<string, array{array<mixed>}>
     */
    public function provideInvalidChoiceCriteria(): array
    {
        return [
            'list' => [['Publish it.', 'Remove it.']],
            'integer-like keys' => [['1' => 'Publish it.', '2' => 'Remove it.']],
            'empty key' => [['' => 'Publish it.', 'trash' => 'Remove it.']],
            'whitespace key' => [[' ' => 'Publish it.', 'trash' => 'Remove it.']],
        ];
    }

    /**
     * Tests that score questions require a list of levels.
     *
     * @dataProvider provideInvalidScoreCriteria
     *
     * @param array<mixed> $criteria The invalid criteria.
     * @return void
     */
    public function testScoreQuestionRequiresListOfLevels(array $criteria): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Score decision question criteria must be a list of level descriptions, from lowest to highest.'
        );

        new DecisionQuestion(DecisionQuestionTypeEnum::score(), 'How toxic is it?', $criteria);
    }

    /**
     * Provides score criteria that are not a list.
     *
     * @return array<string, array{array<mixed>}>
     */
    public function provideInvalidScoreCriteria(): array
    {
        return [
            'named levels' => [['low' => 'Not toxic.', 'high' => 'Very toxic.']],
            'levels counted from one' => [[1 => 'Not toxic.', 2 => 'Very toxic.']],
        ];
    }

    /**
     * Tests that criteria descriptions must be non-empty strings.
     *
     * @dataProvider provideInvalidDescriptions
     *
     * @param DecisionQuestionTypeEnum $type The question type.
     * @param array<mixed> $criteria The criteria with an invalid description.
     * @return void
     */
    public function testRejectsInvalidCriteriaDescriptions(DecisionQuestionTypeEnum $type, array $criteria): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision question criteria descriptions must be non-empty strings.');

        new DecisionQuestion($type, 'Pick one.', $criteria);
    }

    /**
     * Provides criteria with an invalid description.
     *
     * @return array<string, array{DecisionQuestionTypeEnum, array<mixed>}>
     */
    public function provideInvalidDescriptions(): array
    {
        $choice = DecisionQuestionTypeEnum::choice();
        $score = DecisionQuestionTypeEnum::score();

        return [
            'non-string option' => [$choice, ['approve' => 'Publish it.', 'trash' => 1]],
            'empty option' => [$choice, ['approve' => 'Publish it.', 'trash' => ' ']],
            'non-string level' => [$score, ['Not toxic.', null]],
            'empty level' => [$score, ['Not toxic.', '']],
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
            ['object', 'array'],
            array_column($schema['properties'][DecisionQuestion::KEY_CRITERIA]['oneOf'], 'type')
        );
        $this->assertSame(
            [DecisionQuestion::KEY_TYPE, DecisionQuestion::KEY_INSTRUCTIONS],
            $schema['required']
        );
    }
}

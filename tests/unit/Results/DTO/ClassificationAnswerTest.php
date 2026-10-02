<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\Models\Classification\Enums\ClassificationQuestionTypeEnum;
use WordPress\AiClient\Results\DTO\ClassificationAnswer;

/**
 * @covers \WordPress\AiClient\Results\DTO\ClassificationAnswer
 */
class ClassificationAnswerTest extends TestCase
{
    /**
     * Tests an answer to a binary question.
     *
     * @return void
     */
    public function testBinaryAnswer(): void
    {
        $answer = new ClassificationAnswer(ClassificationQuestionTypeEnum::binary(), 0.03);

        $this->assertTrue($answer->getType()->isBinary());
        $this->assertSame(0.03, $answer->getProbability());
        $this->assertNull($answer->getConfidence());
        $this->assertSame([], $answer->getProbabilities());
        $this->assertSame(
            [
                ClassificationAnswer::KEY_TYPE => 'binary',
                ClassificationAnswer::KEY_VALUE => 0.03,
            ],
            $answer->toArray()
        );
    }

    /**
     * Tests an answer to a choice question.
     *
     * @return void
     */
    public function testChoiceAnswer(): void
    {
        $probabilities = ['approve' => 0.91, 'hold' => 0.07, 'trash' => 0.02];
        $answer = new ClassificationAnswer(ClassificationQuestionTypeEnum::choice(), 'approve', 0.91, $probabilities);

        $this->assertTrue($answer->getType()->isChoice());
        $this->assertSame('approve', $answer->getChoice());
        $this->assertSame(0.91, $answer->getConfidence());
        $this->assertSame($probabilities, $answer->getProbabilities());
        $this->assertSame(
            [
                ClassificationAnswer::KEY_TYPE => 'choice',
                ClassificationAnswer::KEY_VALUE => 'approve',
                ClassificationAnswer::KEY_CONFIDENCE => 0.91,
                ClassificationAnswer::KEY_PROBABILITIES => $probabilities,
            ],
            $answer->toArray()
        );
    }

    /**
     * Tests an answer to a score question, including a position between levels.
     *
     * @return void
     */
    public function testScoreAnswer(): void
    {
        $answer = new ClassificationAnswer(ClassificationQuestionTypeEnum::score(), 2.3, 0.26, [0.05, 0.1, 0.35, 0.5]);

        $this->assertTrue($answer->getType()->isScore());
        $this->assertSame(2.3, $answer->getScore());
        $this->assertSame(0.26, $answer->getConfidence());
        $this->assertSame([0.05, 0.1, 0.35, 0.5], $answer->getProbabilities());
    }

    /**
     * Tests that integer values are normalized to floats.
     *
     * @return void
     */
    public function testNormalizesIntegers(): void
    {
        $binary = new ClassificationAnswer(ClassificationQuestionTypeEnum::binary(), 1, 1);
        $score = new ClassificationAnswer(ClassificationQuestionTypeEnum::score(), 3, null, [0, 0, 0, 1]);

        $this->assertSame(1.0, $binary->getProbability());
        $this->assertSame(1.0, $binary->getConfidence());
        $this->assertSame(3.0, $score->getScore());
        $this->assertSame([0.0, 0.0, 0.0, 1.0], $score->getProbabilities());
    }

    /**
     * Tests array round trip, including through JSON.
     *
     * @dataProvider provideAnswers
     *
     * @param ClassificationAnswer $answer The answer.
     * @return void
     */
    public function testArrayRoundTrip(ClassificationAnswer $answer): void
    {
        $this->assertEquals($answer, ClassificationAnswer::fromArray($answer->toArray()));

        $decoded = json_decode((string) json_encode($answer->toArray()), true);
        $this->assertEquals($answer, ClassificationAnswer::fromArray($decoded));
    }

    /**
     * Provides an answer of each type.
     *
     * @return array<string, array{ClassificationAnswer}>
     */
    public function provideAnswers(): array
    {
        return [
            'binary' => [new ClassificationAnswer(ClassificationQuestionTypeEnum::binary(), 0.03)],
            'choice' => [
                new ClassificationAnswer(
                    ClassificationQuestionTypeEnum::choice(),
                    'hold',
                    0.6,
                    ['approve' => 0.4, 'hold' => 0.6]
                ),
            ],
            'score' => [new ClassificationAnswer(ClassificationQuestionTypeEnum::score(), 1.4, 0.5, [0.1, 0.4, 0.5])],
        ];
    }

    /**
     * Tests that values that do not suit the question type are rejected.
     *
     * @dataProvider provideInvalidValues
     *
     * @param ClassificationQuestionTypeEnum $type The question type.
     * @param mixed $value The invalid value.
     * @param string $message The expected exception message.
     * @return void
     */
    public function testRejectsInvalidValues(ClassificationQuestionTypeEnum $type, $value, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new ClassificationAnswer($type, $value);
    }

    /**
     * Provides values that do not suit the question type.
     *
     * @return array<string, array{ClassificationQuestionTypeEnum, mixed, string}>
     */
    public function provideInvalidValues(): array
    {
        $binary = ClassificationQuestionTypeEnum::binary();
        $choice = ClassificationQuestionTypeEnum::choice();
        $score = ClassificationQuestionTypeEnum::score();

        $probabilityMessage = 'Classification answer probability must be a number between 0 and 1.';
        $choiceMessage = 'Classification answer choice must be a non-empty option key.';
        $scoreMessage = 'Classification answer score must be a non-negative number.';

        return [
            'binary below zero' => [$binary, -0.1, $probabilityMessage],
            'binary above one' => [$binary, 1.5, $probabilityMessage],
            'binary NaN' => [$binary, NAN, $probabilityMessage],
            'binary option key' => [$binary, 'yes', $probabilityMessage],
            'binary boolean' => [$binary, true, $probabilityMessage],
            'choice number' => [$choice, 1, $choiceMessage],
            'choice empty string' => [$choice, ' ', $choiceMessage],
            'score below zero' => [$score, -1, $scoreMessage],
            'score NaN' => [$score, NAN, $scoreMessage],
            'score infinite' => [$score, INF, $scoreMessage],
            'score level key' => [$score, 'high', $scoreMessage],
        ];
    }

    /**
     * Tests that an invalid confidence is rejected.
     *
     * @dataProvider provideInvalidProbabilities
     *
     * @param float $confidence The invalid confidence.
     * @return void
     */
    public function testRejectsInvalidConfidence(float $confidence): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Classification answer confidence must be a number between 0 and 1.');

        new ClassificationAnswer(ClassificationQuestionTypeEnum::choice(), 'approve', $confidence);
    }

    /**
     * Tests that invalid probabilities are rejected.
     *
     * @dataProvider provideInvalidProbabilities
     *
     * @param float $probability The invalid probability.
     * @return void
     */
    public function testRejectsInvalidProbabilities(float $probability): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Classification answer probabilities must be a number between 0 and 1.');

        new ClassificationAnswer(
            ClassificationQuestionTypeEnum::choice(),
            'approve',
            null,
            ['approve' => 0.9, 'trash' => $probability]
        );
    }

    /**
     * Provides numbers that are not valid probabilities.
     *
     * @return array<string, array{float}>
     */
    public function provideInvalidProbabilities(): array
    {
        return [
            'below zero' => [-0.1],
            'above one' => [1.2],
            'NaN' => [NAN],
        ];
    }

    /**
     * Tests that answers to binary questions reject probabilities.
     *
     * @return void
     */
    public function testBinaryAnswerRejectsProbabilities(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Classification answers to binary questions do not accept probabilities, as their value is the probability.'
        );

        new ClassificationAnswer(ClassificationQuestionTypeEnum::binary(), 0.9, null, ['true' => 0.9, 'false' => 0.1]);
    }

    /**
     * Tests that answers to choice questions require probabilities keyed by option key.
     *
     * @return void
     */
    public function testChoiceAnswerRequiresProbabilitiesKeyedByOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Classification answer probabilities for a choice question must be keyed by option key.'
        );

        new ClassificationAnswer(ClassificationQuestionTypeEnum::choice(), 'approve', null, [0.9, 0.1]);
    }

    /**
     * Tests that answers to score questions require a list of probabilities.
     *
     * @return void
     */
    public function testScoreAnswerRequiresListOfProbabilities(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Classification answer probabilities for a score question must be a list, '
            . 'from the lowest level to the highest.'
        );

        new ClassificationAnswer(ClassificationQuestionTypeEnum::score(), 1.0, null, ['low' => 0.2, 'high' => 0.8]);
    }

    /**
     * Tests that reading the value of another question type throws.
     *
     * @dataProvider provideMismatchedGetters
     *
     * @param ClassificationAnswer $answer The answer.
     * @param string $getter The getter for another question type.
     * @param string $message The expected exception message.
     * @return void
     */
    public function testGetterForAnotherTypeThrows(ClassificationAnswer $answer, string $getter, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $answer->$getter();
    }

    /**
     * Provides answers with a getter for another question type.
     *
     * @return array<string, array{ClassificationAnswer, string, string}>
     */
    public function provideMismatchedGetters(): array
    {
        $binary = new ClassificationAnswer(ClassificationQuestionTypeEnum::binary(), 0.5);
        $choice = new ClassificationAnswer(ClassificationQuestionTypeEnum::choice(), 'approve');
        $score = new ClassificationAnswer(ClassificationQuestionTypeEnum::score(), 1.0);

        return [
            'choice of binary' => [
                $binary,
                'getChoice',
                'This is an answer to a binary question, not a choice question.',
            ],
            'score of binary' => [$binary, 'getScore', 'This is an answer to a binary question, not a score question.'],
            'probability of choice' => [
                $choice,
                'getProbability',
                'This is an answer to a choice question, not a binary question.',
            ],
            'score of choice' => [$choice, 'getScore', 'This is an answer to a choice question, not a score question.'],
            'probability of score' => [
                $score,
                'getProbability',
                'This is an answer to a score question, not a binary question.',
            ],
            'choice of score' => [$score, 'getChoice', 'This is an answer to a score question, not a choice question.'],
        ];
    }

    /**
     * Tests the JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = ClassificationAnswer::getJsonSchema();

        $this->assertSame(
            ['binary', 'choice', 'score'],
            $schema['properties'][ClassificationAnswer::KEY_TYPE]['enum']
        );
        $this->assertSame(
            ['object', 'array'],
            array_column($schema['properties'][ClassificationAnswer::KEY_PROBABILITIES]['oneOf'], 'type')
        );
        $this->assertSame([ClassificationAnswer::KEY_TYPE, ClassificationAnswer::KEY_VALUE], $schema['required']);
    }
}

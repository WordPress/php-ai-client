<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Decisions\Enums\DecisionQuestionTypeEnum;
use WordPress\AiClient\Results\DTO\DecisionAnswer;

/**
 * @covers \WordPress\AiClient\Results\DTO\DecisionAnswer
 */
class DecisionAnswerTest extends TestCase
{
    /**
     * Tests an answer to a binary question.
     *
     * @return void
     */
    public function testBinaryAnswer(): void
    {
        $answer = new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 0.03);

        $this->assertTrue($answer->getType()->isBinary());
        $this->assertFalse($answer->isRefusal());
        $this->assertSame(0.03, $answer->getProbability());
        $this->assertNull($answer->getConfidence());
        $this->assertSame([], $answer->getProbabilities());
        $this->assertSame(
            [
                DecisionAnswer::KEY_TYPE => 'binary',
                DecisionAnswer::KEY_VALUE => 0.03,
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
        $answer = new DecisionAnswer(DecisionQuestionTypeEnum::choice(), 'approve', 0.91, $probabilities);

        $this->assertTrue($answer->getType()->isChoice());
        $this->assertSame('approve', $answer->getChoice());
        $this->assertSame(0.91, $answer->getConfidence());
        $this->assertSame($probabilities, $answer->getProbabilities());
        $this->assertSame(
            [
                DecisionAnswer::KEY_TYPE => 'choice',
                DecisionAnswer::KEY_VALUE => 'approve',
                DecisionAnswer::KEY_CONFIDENCE => 0.91,
                DecisionAnswer::KEY_PROBABILITIES => [
                    ['value' => 'approve', 'probability' => 0.91],
                    ['value' => 'hold', 'probability' => 0.07],
                    ['value' => 'trash', 'probability' => 0.02],
                ],
            ],
            $answer->toArray()
        );
    }

    /**
     * Tests that probabilities for integer-like option values serialize with the values as strings.
     *
     * @return void
     */
    public function testChoiceAnswerWithIntegerLikeOptionValues(): void
    {
        $answer = new DecisionAnswer(DecisionQuestionTypeEnum::choice(), '2', 0.8, ['1' => 0.2, '2' => 0.8]);

        $this->assertSame('2', $answer->getChoice());
        $this->assertSame(0.8, $answer->getProbabilities()['2']);
        $this->assertSame(
            '[{"value":"1","probability":0.2},{"value":"2","probability":0.8}]',
            json_encode($answer->toArray()[DecisionAnswer::KEY_PROBABILITIES])
        );
    }

    /**
     * Tests a refusal to answer.
     *
     * @return void
     */
    public function testRefusal(): void
    {
        $answer = new DecisionAnswer(DecisionQuestionTypeEnum::choice(), null);

        $this->assertTrue($answer->getType()->isChoice());
        $this->assertTrue($answer->isRefusal());
        $this->assertNull($answer->getConfidence());
        $this->assertSame([], $answer->getProbabilities());
        $this->assertSame(
            [
                DecisionAnswer::KEY_TYPE => 'choice',
                DecisionAnswer::KEY_VALUE => null,
            ],
            $answer->toArray()
        );
    }

    /**
     * Tests that reading the value of a refusal throws.
     *
     * @return void
     */
    public function testRefusalGetterThrows(): void
    {
        $answer = new DecisionAnswer(DecisionQuestionTypeEnum::choice(), null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The model refused to answer this question, so the answer has no value.');

        $answer->getChoice();
    }

    /**
     * Tests that a refusal rejects a confidence or probabilities.
     *
     * @dataProvider provideRefusalExtras
     *
     * @param float|null $confidence The confidence.
     * @param array<float> $probabilities The probabilities.
     * @return void
     */
    public function testRefusalRejectsConfidenceAndProbabilities(?float $confidence, array $probabilities): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Decision answers that refuse to answer do not accept a confidence or probabilities.'
        );

        new DecisionAnswer(DecisionQuestionTypeEnum::score(), null, $confidence, $probabilities);
    }

    /**
     * Provides a confidence or probabilities for a refusal.
     *
     * @return array<string, array{float|null, array<float>}>
     */
    public function provideRefusalExtras(): array
    {
        return [
            'confidence' => [0.5, []],
            'probabilities' => [null, [0.5, 0.5]],
        ];
    }

    /**
     * Tests an answer to a score question, including a position between levels.
     *
     * @return void
     */
    public function testScoreAnswer(): void
    {
        $answer = new DecisionAnswer(DecisionQuestionTypeEnum::score(), 2.3, 0.26, [0.05, 0.1, 0.35, 0.5]);

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
        $binary = new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 1);
        $score = new DecisionAnswer(DecisionQuestionTypeEnum::score(), 3, 1, [0, 0, 0, 1]);

        $this->assertSame(1.0, $binary->getProbability());
        $this->assertSame(1.0, $score->getConfidence());
        $this->assertSame(3.0, $score->getScore());
        $this->assertSame([0.0, 0.0, 0.0, 1.0], $score->getProbabilities());
    }

    /**
     * Tests that numbers just outside their range because of floating-point error are clamped into it.
     *
     * @return void
     */
    public function testClampsFloatingPointError(): void
    {
        $binary = new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 1.0000000000000002);
        $negativeBinary = new DecisionAnswer(DecisionQuestionTypeEnum::binary(), -1.0E-12);
        $choice = new DecisionAnswer(
            DecisionQuestionTypeEnum::choice(),
            'approve',
            1.0000000000000002,
            ['approve' => 1.0000000000000002, 'trash' => -1.0E-12]
        );
        $score = new DecisionAnswer(DecisionQuestionTypeEnum::score(), -1.0E-12);

        $this->assertSame(1.0, $binary->getProbability());
        $this->assertSame(0.0, $negativeBinary->getProbability());
        $this->assertSame(1.0, $choice->getConfidence());
        $this->assertSame(['approve' => 1.0, 'trash' => 0.0], $choice->getProbabilities());
        $this->assertSame(0.0, $score->getScore());
    }

    /**
     * Tests that answers to binary questions reject a confidence.
     *
     * @return void
     */
    public function testBinaryAnswerRejectsConfidence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Decision answers to binary questions do not accept a confidence, as their value is the probability.'
        );

        new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 0.9, 0.8);
    }

    /**
     * Tests array round trip, including through JSON.
     *
     * @dataProvider provideAnswers
     *
     * @param DecisionAnswer $answer The answer.
     * @return void
     */
    public function testArrayRoundTrip(DecisionAnswer $answer): void
    {
        $this->assertEquals($answer, DecisionAnswer::fromArray($answer->toArray()));

        $decoded = json_decode((string) json_encode($answer->toArray()), true);
        $this->assertEquals($answer, DecisionAnswer::fromArray($decoded));
    }

    /**
     * Provides an answer of each type.
     *
     * @return array<string, array{DecisionAnswer}>
     */
    public function provideAnswers(): array
    {
        return [
            'binary' => [new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 0.03)],
            'choice' => [
                new DecisionAnswer(
                    DecisionQuestionTypeEnum::choice(),
                    'hold',
                    0.6,
                    ['approve' => 0.4, 'hold' => 0.6]
                ),
            ],
            'choice with integer-like values' => [
                new DecisionAnswer(DecisionQuestionTypeEnum::choice(), '5', 0.7, ['4' => 0.3, '5' => 0.7]),
            ],
            'score' => [new DecisionAnswer(DecisionQuestionTypeEnum::score(), 1.4, 0.5, [0.1, 0.4, 0.5])],
            'refusal' => [new DecisionAnswer(DecisionQuestionTypeEnum::binary(), null)],
        ];
    }

    /**
     * Tests that values that do not suit the question type are rejected.
     *
     * @dataProvider provideInvalidValues
     *
     * @param DecisionQuestionTypeEnum $type The question type.
     * @param mixed $value The invalid value.
     * @param string $message The expected exception message.
     * @return void
     */
    public function testRejectsInvalidValues(DecisionQuestionTypeEnum $type, $value, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new DecisionAnswer($type, $value);
    }

    /**
     * Provides values that do not suit the question type.
     *
     * @return array<string, array{DecisionQuestionTypeEnum, mixed, string}>
     */
    public function provideInvalidValues(): array
    {
        $binary = DecisionQuestionTypeEnum::binary();
        $choice = DecisionQuestionTypeEnum::choice();
        $score = DecisionQuestionTypeEnum::score();

        $probabilityMessage = 'Decision answer probability must be a number between 0 and 1.';
        $choiceMessage = 'Decision answer choice must be a non-empty option value.';
        $scoreMessage = 'Decision answer score must be a non-negative number.';

        return [
            'binary below zero' => [$binary, -0.1, $probabilityMessage],
            'binary above one' => [$binary, 1.5, $probabilityMessage],
            'binary beyond the tolerance' => [$binary, 1.00001, $probabilityMessage],
            'binary NaN' => [$binary, NAN, $probabilityMessage],
            'binary option key' => [$binary, 'yes', $probabilityMessage],
            'binary boolean' => [$binary, true, $probabilityMessage],
            'choice number' => [$choice, 1, $choiceMessage],
            'choice empty string' => [$choice, ' ', $choiceMessage],
            'score below zero' => [$score, -1, $scoreMessage],
            'score below zero beyond the tolerance' => [$score, -0.00001, $scoreMessage],
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
        $this->expectExceptionMessage('Decision answer confidence must be a number between 0 and 1.');

        new DecisionAnswer(DecisionQuestionTypeEnum::choice(), 'approve', $confidence);
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
        $this->expectExceptionMessage('Decision answer probabilities must be a number between 0 and 1.');

        new DecisionAnswer(
            DecisionQuestionTypeEnum::choice(),
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
            'Decision answers to binary questions do not accept probabilities, as their value is the probability.'
        );

        new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 0.9, null, ['true' => 0.9, 'false' => 0.1]);
    }

    /**
     * Tests that answers to choice questions require probabilities keyed by option value.
     *
     * @return void
     */
    public function testChoiceAnswerRequiresProbabilitiesKeyedByOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Decision answer probabilities for a choice question must be keyed by option value.'
        );

        new DecisionAnswer(DecisionQuestionTypeEnum::choice(), 'approve', null, ['approve' => 0.9, ' ' => 0.1]);
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
            'Decision answer probabilities for a score question must be a list, '
            . 'from the lowest level to the highest.'
        );

        new DecisionAnswer(DecisionQuestionTypeEnum::score(), 1.0, null, ['low' => 0.2, 'high' => 0.8]);
    }

    /**
     * Tests that reading the value of another question type throws.
     *
     * @dataProvider provideMismatchedGetters
     *
     * @param DecisionAnswer $answer The answer.
     * @param string $getter The getter for another question type.
     * @param string $message The expected exception message.
     * @return void
     */
    public function testGetterForAnotherTypeThrows(DecisionAnswer $answer, string $getter, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $answer->$getter();
    }

    /**
     * Provides answers with a getter for another question type.
     *
     * @return array<string, array{DecisionAnswer, string, string}>
     */
    public function provideMismatchedGetters(): array
    {
        $binary = new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 0.5);
        $choice = new DecisionAnswer(DecisionQuestionTypeEnum::choice(), 'approve');
        $score = new DecisionAnswer(DecisionQuestionTypeEnum::score(), 1.0);

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
        $schema = DecisionAnswer::getJsonSchema();

        $this->assertSame(
            ['binary', 'choice', 'score'],
            $schema['properties'][DecisionAnswer::KEY_TYPE]['enum']
        );
        $this->assertSame(
            ['number', 'string', 'null'],
            array_column($schema['properties'][DecisionAnswer::KEY_VALUE]['oneOf'], 'type')
        );
        $this->assertSame(
            ['object', 'number'],
            array_column(
                array_column($schema['properties'][DecisionAnswer::KEY_PROBABILITIES]['oneOf'], 'items'),
                'type'
            )
        );
        $this->assertSame([DecisionAnswer::KEY_TYPE, DecisionAnswer::KEY_VALUE], $schema['required']);
    }
}

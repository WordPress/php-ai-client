<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Decisions\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Decisions\DTO\DecisionQuestion;
use WordPress\AiClient\Decisions\DTO\DecisionRequest;
use WordPress\AiClient\Decisions\Enums\DecisionQuestionTypeEnum;
use WordPress\AiClient\Files\DTO\File;

/**
 * @covers \WordPress\AiClient\Decisions\DTO\DecisionRequest
 */
class DecisionRequestTest extends TestCase
{
    /**
     * Creates questions to ask.
     *
     * @return array<string, DecisionQuestion>
     */
    private static function createQuestions(): array
    {
        return ['spam' => new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Is this spam?')];
    }

    /**
     * Tests that the request keeps the state and questions unchanged.
     *
     * @dataProvider provideValidState
     *
     * @param string|File|array<mixed> $state The state.
     * @return void
     */
    public function testKeepsStateAndQuestions($state): void
    {
        $questions = self::createQuestions();

        $request = new DecisionRequest($state, $questions);

        $this->assertSame($state, $request->getState());
        $this->assertSame($questions, $request->getQuestions());
    }

    /**
     * Provides state of each accepted form.
     *
     * @return array<string, array{string|File|array<mixed>}>
     */
    public function provideValidState(): array
    {
        $photo = new File('https://example.com/photo.png', 'image/png');

        return [
            'string' => ['Buy cheap pills!'],
            'file' => [$photo],
            'map' => [['comment' => 'Buy cheap pills!', 'author' => ['name' => 'Jane', 'posts' => 3]]],
            'list' => [['First comment.', 'Second comment.']],
            'JSON scalars' => [['flagged' => false, 'score' => 0.5, 'parent' => null]],
            'nested files' => [['post' => ['title' => 'Holiday', 'photos' => [$photo, $photo]]]],
        ];
    }

    /**
     * Tests that invalid state is rejected.
     *
     * @dataProvider provideInvalidState
     *
     * @param mixed $state The invalid state.
     * @param string $message The expected exception message.
     * @return void
     */
    public function testRejectsInvalidState($state, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new DecisionRequest($state, self::createQuestions());
    }

    /**
     * Provides invalid state and the expected exception message.
     *
     * @return array<string, array{mixed, string}>
     */
    public function provideInvalidState(): array
    {
        return [
            'empty string' => [' ', 'Decision state cannot be empty.'],
            'empty array' => [[], 'Decision state cannot be empty.'],
            'integer' => [42, 'Decision state must be a string, a file, or an array.'],
            'object' => [new \stdClass(), 'Decision state must be a string, a file, or an array.'],
            'nested object' => [
                ['comment' => ['author' => new \stdClass()]],
                'Decision state values must be strings, numbers, booleans, null, arrays, or files.',
            ],
            'infinite number' => [['score' => INF], 'Decision state numbers must be finite.'],
            'NaN' => [['score' => NAN], 'Decision state numbers must be finite.'],
        ];
    }

    /**
     * Tests that a request requires questions.
     *
     * @return void
     */
    public function testRequiresQuestions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision requests require at least one question.');

        new DecisionRequest('Buy cheap pills!', []);
    }

    /**
     * Tests that questions must be keyed by name.
     *
     * @return void
     */
    public function testRejectsQuestionsNotKeyedByName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision question keys must be non-empty, non-integer strings.');

        new DecisionRequest('Buy cheap pills!', array_values(self::createQuestions()));
    }

    /**
     * Tests that questions must be DecisionQuestion instances.
     *
     * @return void
     */
    public function testRejectsInvalidQuestions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision questions must be DecisionQuestion instances.');

        new DecisionRequest('Buy cheap pills!', ['spam' => 'Is this spam?']);
    }
}

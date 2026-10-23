<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Builders\DecisionBuilder;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\DTO\ProviderModelsMetadata;
use WordPress\AiClient\Providers\Models\Decision\DTO\DecisionQuestion;
use WordPress\AiClient\Providers\Models\Decision\Enums\DecisionQuestionTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\DecisionAnswer;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Builders\DecisionBuilder
 */
class DecisionBuilderTest extends TestCase
{
    use MockModelCreationTrait;

    /**
     * @var ProviderRegistry&\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = $this->createMock(ProviderRegistry::class);
    }

    /**
     * Reads a property from a builder.
     *
     * @param DecisionBuilder $builder The builder to inspect.
     * @param string $propertyName The property name.
     * @return mixed The property value.
     */
    private function getBuilderProperty(DecisionBuilder $builder, string $propertyName)
    {
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($builder);
    }

    /**
     * Creates a binary question.
     *
     * @return DecisionQuestion
     */
    private function createSpamQuestion(): DecisionQuestion
    {
        return new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Is this comment spam?');
    }

    /**
     * Creates a builder with one question of each type, answered by a model with the given answers.
     *
     * @param array<string, DecisionAnswer> $answers The answers the model returns.
     * @return DecisionBuilder
     */
    private function createBuilderWithAnswers(array $answers): DecisionBuilder
    {
        $builder = new DecisionBuilder($this->registry, ['comment' => 'Nice post!']);

        return $builder
            ->withQuestion('spam', $this->createSpamQuestion())
            ->withQuestion(
                'route',
                new DecisionQuestion(
                    DecisionQuestionTypeEnum::choice(),
                    'How should a moderator handle it?',
                    ['approve' => 'Publish it.', 'hold' => 'Look closer.', 'trash' => 'Remove it.']
                )
            )
            ->withQuestion(
                'tone',
                new DecisionQuestion(
                    DecisionQuestionTypeEnum::score(),
                    'How civil is it?',
                    ['Hostile.', 'Neutral.', 'Friendly.']
                )
            )
            ->usingModel($this->createMockDecisionModel($this->createTestDecisionResult($answers)));
    }

    /**
     * Creates a valid answer to each question asked by createBuilderWithAnswers().
     *
     * @return array<string, DecisionAnswer>
     */
    private static function createValidAnswers(): array
    {
        return [
            'spam' => new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 0.03),
            'route' => new DecisionAnswer(
                DecisionQuestionTypeEnum::choice(),
                'approve',
                0.91,
                ['approve' => 0.91, 'hold' => 0.07, 'trash' => 0.02]
            ),
            // The highest level is a valid position on the scale.
            'tone' => new DecisionAnswer(DecisionQuestionTypeEnum::score(), 2, 0.6, [0.1, 0.2, 0.7]),
        ];
    }

    /**
     * Tests that the constructor accepts initial state.
     *
     * @return void
     */
    public function testConstructorWithState(): void
    {
        $builder = new DecisionBuilder($this->registry, ['comment' => 'Nice post!']);

        $this->assertSame(['comment' => 'Nice post!'], $this->getBuilderProperty($builder, 'state'));
    }

    /**
     * Tests that withState() adds to and overwrites existing state.
     *
     * @return void
     */
    public function testWithStateMergesState(): void
    {
        $builder = new DecisionBuilder($this->registry, ['comment' => 'First', 'author' => 'Jane']);

        $builder->withState(['comment' => 'Second', 'post' => 'Hello world']);

        $this->assertSame(
            ['comment' => 'Second', 'author' => 'Jane', 'post' => 'Hello world'],
            $this->getBuilderProperty($builder, 'state')
        );
    }

    /**
     * Tests that withState() rejects empty state.
     *
     * @return void
     */
    public function testWithStateRejectsEmptyState(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision state cannot be empty.');

        new DecisionBuilder($this->registry, []);
    }

    /**
     * Tests that withState() rejects state that is not keyed by name.
     *
     * @dataProvider provideStateWithInvalidKeys
     *
     * @param array<mixed> $state The state with invalid keys.
     * @return void
     */
    public function testWithStateRejectsInvalidKeys(array $state): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision state keys must be non-empty, non-integer strings.');

        (new DecisionBuilder($this->registry))->withState($state);
    }

    /**
     * Provides state with keys that are not names.
     *
     * @return array<string, array{array<mixed>}>
     */
    public function provideStateWithInvalidKeys(): array
    {
        return [
            'list' => [['Nice post!']],
            'integer-like key' => [['1' => 'Nice post!']],
            'empty key' => [['' => 'Nice post!']],
        ];
    }

    /**
     * Tests that withQuestion() replaces a question with the same key.
     *
     * @return void
     */
    public function testWithQuestionReplacesExistingKey(): void
    {
        $replacement = new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Is this comment rude?');

        $builder = new DecisionBuilder($this->registry);
        $builder->withQuestion('spam', $this->createSpamQuestion());
        $builder->withQuestion('spam', $replacement);

        $this->assertSame(['spam' => $replacement], $this->getBuilderProperty($builder, 'questions'));
    }

    /**
     * Tests that withQuestion() rejects keys that PHP would not keep as strings.
     *
     * @dataProvider provideInvalidQuestionKeys
     *
     * @param string $key The invalid key.
     * @return void
     */
    public function testWithQuestionRejectsInvalidKeys(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision question keys must be non-empty, non-integer strings.');

        (new DecisionBuilder($this->registry))->withQuestion($key, $this->createSpamQuestion());
    }

    /**
     * Provides question keys that are empty or integer-like.
     *
     * @return array<string, array{string}>
     */
    public function provideInvalidQuestionKeys(): array
    {
        return [
            'empty' => [''],
            'whitespace' => [' '],
            'zero' => ['0'],
            'positive integer' => ['12'],
            'negative integer' => ['-3'],
        ];
    }

    /**
     * Tests that withQuestion() accepts numeric-looking keys that PHP keeps as strings.
     *
     * @return void
     */
    public function testWithQuestionAcceptsNonIntegerNumericKeys(): void
    {
        $builder = new DecisionBuilder($this->registry);
        $builder->withQuestion('01', $this->createSpamQuestion());
        $builder->withQuestion('1.5', $this->createSpamQuestion());

        $this->assertSame(['01', '1.5'], array_keys($this->getBuilderProperty($builder, 'questions')));
    }

    /**
     * Tests decideResult() with an explicitly set model.
     *
     * @return void
     */
    public function testDecideResultWithModel(): void
    {
        $result = $this->createTestDecisionResult();
        $model = $this->createMockDecisionModel($result);
        $question = $this->createSpamQuestion();

        $this->registry->expects($this->once())
            ->method('bindModelDependencies')
            ->with($model);

        $builder = new DecisionBuilder($this->registry, ['comment' => 'Buy cheap pills!']);
        $builder->withQuestion('spam', $question)->usingModel($model);

        $this->assertSame($result, $builder->decideResult());
        $this->assertSame([['comment' => 'Buy cheap pills!'], ['spam' => $question]], $model->lastCall);
    }

    /**
     * Tests that decideResult() discovers a decision model when none is set.
     *
     * @return void
     */
    public function testDecideResultDiscoversModel(): void
    {
        $result = $this->createTestDecisionResult();
        $model = $this->createMockDecisionModel($result);

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->with($this->callback(static function (ModelRequirements $requirements): bool {
                return $requirements->getRequiredCapabilities() == [CapabilityEnum::decision()];
            }))
            ->willReturn([new ProviderModelsMetadata($model->providerMetadata(), [$model->metadata()])]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('mock', 'test-decision-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $builder = new DecisionBuilder($this->registry, ['comment' => 'Buy cheap pills!']);
        $builder->withQuestion('spam', $this->createSpamQuestion());

        $this->assertSame($result, $builder->decideResult());
    }

    /**
     * Tests that decideResult() accepts a valid answer to a question of each type.
     *
     * @return void
     */
    public function testDecideResultAcceptsValidAnswers(): void
    {
        $answers = self::createValidAnswers();

        $result = $this->createBuilderWithAnswers($answers)->decideResult();

        $this->assertSame($answers, $result->getAnswers());
        $this->assertSame(0.03, $result->getAnswer('spam')->getProbability());
        $this->assertSame('approve', $result->getAnswer('route')->getChoice());
        $this->assertSame(2.0, $result->getAnswer('tone')->getScore());
    }

    /**
     * Tests that decideResult() requires state.
     *
     * @return void
     */
    public function testDecideResultRequiresState(): void
    {
        $builder = new DecisionBuilder($this->registry);
        $builder->withQuestion('spam', $this->createSpamQuestion());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot decide on empty state. Add state using withState().');

        $builder->decideResult();
    }

    /**
     * Tests that decideResult() requires questions.
     *
     * @return void
     */
    public function testDecideResultRequiresQuestions(): void
    {
        $builder = new DecisionBuilder($this->registry, ['comment' => 'Nice post!']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot decide without questions. Add questions using withQuestion().');

        $builder->decideResult();
    }

    /**
     * Tests that decideResult() rejects a model that does not support decisions.
     *
     * @return void
     */
    public function testDecideResultRejectsUnsupportedModel(): void
    {
        $builder = new DecisionBuilder($this->registry, ['comment' => 'Nice post!']);
        $builder->withQuestion('spam', $this->createSpamQuestion())
            ->usingModel($this->createMockUnsupportedModel('text-only-model'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "text-only-model" does not support decisions.');

        $builder->decideResult();
    }

    /**
     * Tests that decideResult() throws when the model leaves a question unanswered.
     *
     * @return void
     */
    public function testDecideResultRequiresAnswerForEveryQuestion(): void
    {
        $answers = self::createValidAnswers();
        unset($answers['route'], $answers['tone']);

        $builder = $this->createBuilderWithAnswers($answers);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The model did not answer the following questions: route, tone.');

        $builder->decideResult();
    }

    /**
     * Tests that decideResult() throws when an answer does not fit its question.
     *
     * @dataProvider provideInvalidAnswers
     *
     * @param string $key The key of the question answered invalidly.
     * @param DecisionAnswer $answer The invalid answer.
     * @param string $message The expected exception message.
     * @return void
     */
    public function testDecideResultRejectsInvalidAnswers(
        string $key,
        DecisionAnswer $answer,
        string $message
    ): void {
        $answers = self::createValidAnswers();
        $answers[$key] = $answer;

        $builder = $this->createBuilderWithAnswers($answers);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf('The model gave an invalid answer to the question "%s". %s', $key, $message)
        );

        $builder->decideResult();
    }

    /**
     * Provides answers that do not fit their question.
     *
     * @return array<string, array{string, DecisionAnswer, string}>
     */
    public function provideInvalidAnswers(): array
    {
        $choice = DecisionQuestionTypeEnum::choice();
        $score = DecisionQuestionTypeEnum::score();

        return [
            'wrong type' => [
                'spam',
                new DecisionAnswer($choice, 'approve'),
                'Expected an answer to a binary question, but received an answer to a choice question.',
            ],
            // A probability of 1 must not be mistaken for the first level of a scale, or vice versa.
            'score answered as binary' => [
                'tone',
                new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 1),
                'Expected an answer to a score question, but received an answer to a binary question.',
            ],
            'unknown option' => [
                'route',
                new DecisionAnswer($choice, 'publish'),
                '"publish" is not one of its options.',
            ],
            'probabilities for unknown options' => [
                'route',
                new DecisionAnswer($choice, 'approve', null, ['approve' => 0.8, 'delete' => 0.1, 'spam' => 0.1]),
                'It has probabilities for options the question does not have: delete, spam.',
            ],
            'score beyond the scale' => [
                'tone',
                new DecisionAnswer($score, 2.5),
                'The score 2.5 is outside the scale, which runs from 0 to 2.',
            ],
            'probabilities for too few levels' => [
                'tone',
                new DecisionAnswer($score, 1, null, [0.5, 0.5]),
                'It has probabilities for 2 levels, but the question has 3.',
            ],
        ];
    }

    /**
     * Tests isSupported() when no registered model supports decisions.
     *
     * @return void
     */
    public function testIsSupportedReturnsFalseWithoutModels(): void
    {
        $this->registry->method('findModelsMetadataForSupport')->willReturn([]);

        $builder = new DecisionBuilder($this->registry, ['comment' => 'Nice post!']);

        $this->assertFalse($builder->isSupported());
    }

    /**
     * Tests isSupported() with an explicitly set model.
     *
     * @return void
     */
    public function testIsSupportedWithModel(): void
    {
        $builder = new DecisionBuilder($this->registry);

        $builder->usingModel($this->createMockDecisionModel($this->createTestDecisionResult()));
        $this->assertTrue($builder->isSupported());

        $builder->usingModel($this->createMockTextGenerationModel($this->createTestResult()));
        $this->assertFalse($builder->isSupported());
    }

    /**
     * Tests that a cloned builder does not share model selection with the original.
     *
     * @return void
     */
    public function testCloneDoesNotShareModelSelection(): void
    {
        $this->registry->method('findModelsMetadataForSupport')->willReturn([]);

        $builder = new DecisionBuilder($this->registry);
        $clone = clone $builder;

        $clone->usingModel($this->createMockDecisionModel($this->createTestDecisionResult()));

        $this->assertTrue($clone->isSupported());
        $this->assertFalse($builder->isSupported());
    }
}

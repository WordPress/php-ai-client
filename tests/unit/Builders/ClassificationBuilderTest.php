<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Builders;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Builders\ClassificationBuilder;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\DTO\ProviderModelsMetadata;
use WordPress\AiClient\Providers\Models\Classification\DTO\ClassificationQuestion;
use WordPress\AiClient\Providers\Models\Classification\Enums\ClassificationQuestionTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\ClassificationAnswer;
use WordPress\AiClient\Tests\traits\MockModelCreationTrait;

/**
 * @covers \WordPress\AiClient\Builders\ClassificationBuilder
 */
class ClassificationBuilderTest extends TestCase
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
     * @param ClassificationBuilder $builder The builder to inspect.
     * @param string $propertyName The property name.
     * @return mixed The property value.
     */
    private function getBuilderProperty(ClassificationBuilder $builder, string $propertyName)
    {
        $reflection = new \ReflectionClass($builder);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($builder);
    }

    /**
     * Creates a binary question.
     *
     * @return ClassificationQuestion
     */
    private function createSpamQuestion(): ClassificationQuestion
    {
        return new ClassificationQuestion(ClassificationQuestionTypeEnum::binary(), 'Is this comment spam?');
    }

    /**
     * Creates a builder with one question of each type, answered by a model with the given answers.
     *
     * @param array<string, ClassificationAnswer> $answers The answers the model returns.
     * @return ClassificationBuilder
     */
    private function createBuilderWithAnswers(array $answers): ClassificationBuilder
    {
        $builder = new ClassificationBuilder($this->registry, ['comment' => 'Nice post!']);

        return $builder
            ->withQuestion('spam', $this->createSpamQuestion())
            ->withQuestion(
                'route',
                new ClassificationQuestion(
                    ClassificationQuestionTypeEnum::choice(),
                    'How should a moderator handle it?',
                    ['approve' => 'Publish it.', 'hold' => 'Look closer.', 'trash' => 'Remove it.']
                )
            )
            ->withQuestion(
                'tone',
                new ClassificationQuestion(
                    ClassificationQuestionTypeEnum::score(),
                    'How civil is it?',
                    ['Hostile.', 'Neutral.', 'Friendly.']
                )
            )
            ->usingModel($this->createMockClassificationModel($this->createTestClassificationResult($answers)));
    }

    /**
     * Creates a valid answer to each question asked by createBuilderWithAnswers().
     *
     * @return array<string, ClassificationAnswer>
     */
    private static function createValidAnswers(): array
    {
        return [
            'spam' => new ClassificationAnswer(ClassificationQuestionTypeEnum::binary(), 0.03),
            'route' => new ClassificationAnswer(
                ClassificationQuestionTypeEnum::choice(),
                'approve',
                0.91,
                ['approve' => 0.91, 'hold' => 0.07, 'trash' => 0.02]
            ),
            // The highest level is a valid position on the scale.
            'tone' => new ClassificationAnswer(ClassificationQuestionTypeEnum::score(), 2, 0.6, [0.1, 0.2, 0.7]),
        ];
    }

    /**
     * Tests that the constructor accepts initial state.
     *
     * @return void
     */
    public function testConstructorWithState(): void
    {
        $builder = new ClassificationBuilder($this->registry, ['comment' => 'Nice post!']);

        $this->assertSame(['comment' => 'Nice post!'], $this->getBuilderProperty($builder, 'state'));
    }

    /**
     * Tests that withState() adds to and overwrites existing state.
     *
     * @return void
     */
    public function testWithStateMergesState(): void
    {
        $builder = new ClassificationBuilder($this->registry, ['comment' => 'First', 'author' => 'Jane']);

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
        $this->expectExceptionMessage('Classification state cannot be empty.');

        new ClassificationBuilder($this->registry, []);
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
        $this->expectExceptionMessage('Classification state keys must be non-empty, non-integer strings.');

        (new ClassificationBuilder($this->registry))->withState($state);
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
        $replacement = new ClassificationQuestion(ClassificationQuestionTypeEnum::binary(), 'Is this comment rude?');

        $builder = new ClassificationBuilder($this->registry);
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
        $this->expectExceptionMessage('Classification question keys must be non-empty, non-integer strings.');

        (new ClassificationBuilder($this->registry))->withQuestion($key, $this->createSpamQuestion());
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
        $builder = new ClassificationBuilder($this->registry);
        $builder->withQuestion('01', $this->createSpamQuestion());
        $builder->withQuestion('1.5', $this->createSpamQuestion());

        $this->assertSame(['01', '1.5'], array_keys($this->getBuilderProperty($builder, 'questions')));
    }

    /**
     * Tests classifyResult() with an explicitly set model.
     *
     * @return void
     */
    public function testClassifyResultWithModel(): void
    {
        $result = $this->createTestClassificationResult();
        $model = $this->createMockClassificationModel($result);
        $question = $this->createSpamQuestion();

        $this->registry->expects($this->once())
            ->method('bindModelDependencies')
            ->with($model);

        $builder = new ClassificationBuilder($this->registry, ['comment' => 'Buy cheap pills!']);
        $builder->withQuestion('spam', $question)->usingModel($model);

        $this->assertSame($result, $builder->classifyResult());
        $this->assertSame([['comment' => 'Buy cheap pills!'], ['spam' => $question]], $model->lastCall);
    }

    /**
     * Tests that classifyResult() discovers a classification model when none is set.
     *
     * @return void
     */
    public function testClassifyResultDiscoversModel(): void
    {
        $result = $this->createTestClassificationResult();
        $model = $this->createMockClassificationModel($result);

        $this->registry->expects($this->once())
            ->method('findModelsMetadataForSupport')
            ->with($this->callback(static function (ModelRequirements $requirements): bool {
                return $requirements->getRequiredCapabilities() == [CapabilityEnum::classification()];
            }))
            ->willReturn([new ProviderModelsMetadata($model->providerMetadata(), [$model->metadata()])]);

        $this->registry->expects($this->once())
            ->method('getProviderModel')
            ->with('mock', 'test-classification-model', $this->isInstanceOf(ModelConfig::class))
            ->willReturn($model);

        $builder = new ClassificationBuilder($this->registry, ['comment' => 'Buy cheap pills!']);
        $builder->withQuestion('spam', $this->createSpamQuestion());

        $this->assertSame($result, $builder->classifyResult());
    }

    /**
     * Tests that classifyResult() accepts a valid answer to a question of each type.
     *
     * @return void
     */
    public function testClassifyResultAcceptsValidAnswers(): void
    {
        $answers = self::createValidAnswers();

        $result = $this->createBuilderWithAnswers($answers)->classifyResult();

        $this->assertSame($answers, $result->getAnswers());
        $this->assertSame(0.03, $result->getAnswer('spam')->getProbability());
        $this->assertSame('approve', $result->getAnswer('route')->getChoice());
        $this->assertSame(2.0, $result->getAnswer('tone')->getScore());
    }

    /**
     * Tests that classifyResult() requires state.
     *
     * @return void
     */
    public function testClassifyResultRequiresState(): void
    {
        $builder = new ClassificationBuilder($this->registry);
        $builder->withQuestion('spam', $this->createSpamQuestion());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot classify empty state. Add state using withState().');

        $builder->classifyResult();
    }

    /**
     * Tests that classifyResult() requires questions.
     *
     * @return void
     */
    public function testClassifyResultRequiresQuestions(): void
    {
        $builder = new ClassificationBuilder($this->registry, ['comment' => 'Nice post!']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot classify without questions. Add questions using withQuestion().');

        $builder->classifyResult();
    }

    /**
     * Tests that classifyResult() rejects a model that does not support classification.
     *
     * @return void
     */
    public function testClassifyResultRejectsUnsupportedModel(): void
    {
        $builder = new ClassificationBuilder($this->registry, ['comment' => 'Nice post!']);
        $builder->withQuestion('spam', $this->createSpamQuestion())
            ->usingModel($this->createMockUnsupportedModel('text-only-model'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Model "text-only-model" does not support classification.');

        $builder->classifyResult();
    }

    /**
     * Tests that classifyResult() throws when the model leaves a question unanswered.
     *
     * @return void
     */
    public function testClassifyResultRequiresAnswerForEveryQuestion(): void
    {
        $answers = self::createValidAnswers();
        unset($answers['route'], $answers['tone']);

        $builder = $this->createBuilderWithAnswers($answers);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The model did not answer the following questions: route, tone.');

        $builder->classifyResult();
    }

    /**
     * Tests that classifyResult() throws when an answer does not fit its question.
     *
     * @dataProvider provideInvalidAnswers
     *
     * @param string $key The key of the question answered invalidly.
     * @param ClassificationAnswer $answer The invalid answer.
     * @param string $message The expected exception message.
     * @return void
     */
    public function testClassifyResultRejectsInvalidAnswers(
        string $key,
        ClassificationAnswer $answer,
        string $message
    ): void {
        $answers = self::createValidAnswers();
        $answers[$key] = $answer;

        $builder = $this->createBuilderWithAnswers($answers);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf('The model gave an invalid answer to the question "%s". %s', $key, $message)
        );

        $builder->classifyResult();
    }

    /**
     * Provides answers that do not fit their question.
     *
     * @return array<string, array{string, ClassificationAnswer, string}>
     */
    public function provideInvalidAnswers(): array
    {
        $choice = ClassificationQuestionTypeEnum::choice();
        $score = ClassificationQuestionTypeEnum::score();

        return [
            'wrong type' => [
                'spam',
                new ClassificationAnswer($choice, 'approve'),
                'Expected an answer to a binary question, but received an answer to a choice question.',
            ],
            // A probability of 1 must not be mistaken for the first level of a scale, or vice versa.
            'score answered as binary' => [
                'tone',
                new ClassificationAnswer(ClassificationQuestionTypeEnum::binary(), 1),
                'Expected an answer to a score question, but received an answer to a binary question.',
            ],
            'unknown option' => [
                'route',
                new ClassificationAnswer($choice, 'publish'),
                '"publish" is not one of its options.',
            ],
            'probabilities for unknown options' => [
                'route',
                new ClassificationAnswer($choice, 'approve', null, ['approve' => 0.8, 'delete' => 0.1, 'spam' => 0.1]),
                'It has probabilities for options the question does not have: delete, spam.',
            ],
            'score beyond the scale' => [
                'tone',
                new ClassificationAnswer($score, 2.5),
                'The score 2.5 is outside the scale, which runs from 0 to 2.',
            ],
            'probabilities for too few levels' => [
                'tone',
                new ClassificationAnswer($score, 1, null, [0.5, 0.5]),
                'It has probabilities for 2 levels, but the question has 3.',
            ],
        ];
    }

    /**
     * Tests isSupported() when no registered model supports classification.
     *
     * @return void
     */
    public function testIsSupportedReturnsFalseWithoutModels(): void
    {
        $this->registry->method('findModelsMetadataForSupport')->willReturn([]);

        $builder = new ClassificationBuilder($this->registry, ['comment' => 'Nice post!']);

        $this->assertFalse($builder->isSupported());
    }

    /**
     * Tests isSupported() with an explicitly set model.
     *
     * @return void
     */
    public function testIsSupportedWithModel(): void
    {
        $builder = new ClassificationBuilder($this->registry);

        $builder->usingModel($this->createMockClassificationModel($this->createTestClassificationResult()));
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

        $builder = new ClassificationBuilder($this->registry);
        $clone = clone $builder;

        $clone->usingModel($this->createMockClassificationModel($this->createTestClassificationResult()));

        $this->assertTrue($clone->isSupported());
        $this->assertFalse($builder->isSupported());
    }
}

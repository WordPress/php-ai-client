<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Classification\Enums\ClassificationQuestionTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\ClassificationAnswer;
use WordPress\AiClient\Results\DTO\ClassificationResult;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * @covers \WordPress\AiClient\Results\DTO\ClassificationResult
 */
class ClassificationResultTest extends TestCase
{
    /**
     * Creates a classification result with the given answers.
     *
     * @param array<string, ClassificationAnswer> $answers The answers.
     * @return ClassificationResult
     */
    private function createClassificationResult(array $answers): ClassificationResult
    {
        return new ClassificationResult(
            'classification-result-id',
            $answers,
            new TokenUsage(312, 9, 321),
            new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud()),
            new ModelMetadata(
                'mock-classification-model',
                'Mock Classification Model',
                [CapabilityEnum::classification()],
                []
            ),
            ['providerResultId' => 'provider-123']
        );
    }

    /**
     * Tests the getters.
     *
     * @return void
     */
    public function testGetters(): void
    {
        $spam = new ClassificationAnswer(ClassificationQuestionTypeEnum::binary(), 0.03);
        $route = new ClassificationAnswer(
            ClassificationQuestionTypeEnum::choice(),
            'approve',
            0.91,
            ['approve' => 0.91, 'hold' => 0.07, 'trash' => 0.02]
        );

        $result = $this->createClassificationResult(['spam' => $spam, 'route' => $route]);

        $this->assertSame('classification-result-id', $result->getId());
        $this->assertSame(['spam' => $spam, 'route' => $route], $result->getAnswers());
        $this->assertSame($spam, $result->getAnswer('spam'));
        $this->assertSame($route, $result->getAnswer('route'));
        $this->assertSame(312, $result->getTokenUsage()->getPromptTokens());
        $this->assertSame('mock', $result->getProviderMetadata()->getId());
        $this->assertSame('mock-classification-model', $result->getModelMetadata()->getId());
        $this->assertSame(['providerResultId' => 'provider-123'], $result->getAdditionalData());
    }

    /**
     * Tests array round trip, including through JSON.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $result = $this->createClassificationResult([
            'spam' => new ClassificationAnswer(ClassificationQuestionTypeEnum::binary(), 0.03),
            'route' => new ClassificationAnswer(
                ClassificationQuestionTypeEnum::choice(),
                'approve',
                0.91,
                ['approve' => 0.91, 'trash' => 0.09]
            ),
            'tone' => new ClassificationAnswer(ClassificationQuestionTypeEnum::score(), 1.7, 0.8, [0.05, 0.2, 0.75]),
        ]);

        $array = $result->toArray();

        $this->assertSame(
            [ClassificationAnswer::KEY_TYPE => 'binary', ClassificationAnswer::KEY_VALUE => 0.03],
            $array[ClassificationResult::KEY_ANSWERS]['spam']
        );
        $this->assertEquals($result, ClassificationResult::fromArray($array));

        $decoded = json_decode((string) json_encode($array), true);
        $this->assertEquals($result, ClassificationResult::fromArray($decoded));
    }

    /**
     * Tests that at least one answer is required.
     *
     * @return void
     */
    public function testRequiresAtLeastOneAnswer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one answer must be provided.');

        $this->createClassificationResult([]);
    }

    /**
     * Tests that getting an unknown answer throws.
     *
     * @return void
     */
    public function testGetAnswerThrowsForUnknownQuestion(): void
    {
        $result = $this->createClassificationResult([
            'spam' => new ClassificationAnswer(ClassificationQuestionTypeEnum::binary(), 0.03),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No answer found for question "route".');

        $result->getAnswer('route');
    }

    /**
     * Tests the JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = ClassificationResult::getJsonSchema();

        $this->assertSame(
            ClassificationAnswer::getJsonSchema(),
            $schema['properties'][ClassificationResult::KEY_ANSWERS]['additionalProperties']
        );
        $this->assertContains(ClassificationResult::KEY_ANSWERS, $schema['required']);
    }
}

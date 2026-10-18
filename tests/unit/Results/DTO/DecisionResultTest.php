<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Decision\Enums\DecisionQuestionTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\DecisionAnswer;
use WordPress\AiClient\Results\DTO\DecisionResult;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * @covers \WordPress\AiClient\Results\DTO\DecisionResult
 */
class DecisionResultTest extends TestCase
{
    /**
     * Creates a decision result with the given answers.
     *
     * @param array<string, DecisionAnswer> $answers The answers.
     * @return DecisionResult
     */
    private function createDecisionResult(array $answers): DecisionResult
    {
        return new DecisionResult(
            'decision-result-id',
            $answers,
            new TokenUsage(312, 9, 321),
            new ProviderMetadata('mock', 'Mock Provider', ProviderTypeEnum::cloud()),
            new ModelMetadata(
                'mock-decision-model',
                'Mock Decision Model',
                [CapabilityEnum::decision()],
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
        $spam = new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 0.03);
        $route = new DecisionAnswer(
            DecisionQuestionTypeEnum::choice(),
            'approve',
            0.91,
            ['approve' => 0.91, 'hold' => 0.07, 'trash' => 0.02]
        );

        $result = $this->createDecisionResult(['spam' => $spam, 'route' => $route]);

        $this->assertSame('decision-result-id', $result->getId());
        $this->assertSame(['spam' => $spam, 'route' => $route], $result->getAnswers());
        $this->assertSame($spam, $result->getAnswer('spam'));
        $this->assertSame($route, $result->getAnswer('route'));
        $this->assertSame(312, $result->getTokenUsage()->getPromptTokens());
        $this->assertSame('mock', $result->getProviderMetadata()->getId());
        $this->assertSame('mock-decision-model', $result->getModelMetadata()->getId());
        $this->assertSame(['providerResultId' => 'provider-123'], $result->getAdditionalData());
    }

    /**
     * Tests array round trip, including through JSON.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $result = $this->createDecisionResult([
            'spam' => new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 0.03),
            'route' => new DecisionAnswer(
                DecisionQuestionTypeEnum::choice(),
                'approve',
                0.91,
                ['approve' => 0.91, 'trash' => 0.09]
            ),
            'tone' => new DecisionAnswer(DecisionQuestionTypeEnum::score(), 1.7, 0.8, [0.05, 0.2, 0.75]),
        ]);

        $array = $result->toArray();

        $this->assertSame(
            [DecisionAnswer::KEY_TYPE => 'binary', DecisionAnswer::KEY_VALUE => 0.03],
            $array[DecisionResult::KEY_ANSWERS]['spam']
        );
        $this->assertEquals($result, DecisionResult::fromArray($array));

        $decoded = json_decode((string) json_encode($array), true);
        $this->assertEquals($result, DecisionResult::fromArray($decoded));
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

        $this->createDecisionResult([]);
    }

    /**
     * Tests that getting an unknown answer throws.
     *
     * @return void
     */
    public function testGetAnswerThrowsForUnknownQuestion(): void
    {
        $result = $this->createDecisionResult([
            'spam' => new DecisionAnswer(DecisionQuestionTypeEnum::binary(), 0.03),
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
        $schema = DecisionResult::getJsonSchema();

        $this->assertSame(
            DecisionAnswer::getJsonSchema(),
            $schema['properties'][DecisionResult::KEY_ANSWERS]['additionalProperties']
        );
        $this->assertContains(DecisionResult::KEY_ANSWERS, $schema['required']);
    }
}

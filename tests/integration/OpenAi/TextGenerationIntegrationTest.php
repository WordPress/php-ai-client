<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\integration\OpenAi;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Tests\integration\traits\IntegrationTestTrait;

/**
 * Integration tests for OpenAI text generation.
 *
 * These tests make real API calls to OpenAI and require the OPENAI_API_KEY
 * environment variable to be set.
 *
 * @group integration
 * @group openai
 *
 * @coversNothing
 */
class TextGenerationIntegrationTest extends TestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireApiKey('OPENAI_API_KEY');
    }

    /**
     * Tests basic text generation with a simple prompt.
     */
    public function testSimpleTextGeneration(): void
    {
        $result = AiClient::prompt('Say "hello" and nothing else.')
            ->usingProvider('openai')
            ->generateTextResult();

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertStringContainsStringIgnoringCase('hello', $result->toText());
    }

    /**
     * Tests text generation with a system message.
     */
    public function testTextGenerationWithSystemMessage(): void
    {
        $result = AiClient::prompt('What is your name?')
            ->usingProvider('openai')
            ->usingSystemInstruction('You are an assistant named "TestBot". Always introduce yourself by name.')
            ->generateTextResult();

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertStringContainsStringIgnoringCase('TestBot', $result->toText());
    }

    /**
     * Tests that text generation returns token usage information.
     */
    public function testTextGenerationReturnsTokenUsage(): void
    {
        $result = AiClient::prompt('Say "hello" and nothing else.')
            ->usingProvider('openai')
            ->generateTextResult();

        $tokenUsage = $result->getTokenUsage();
        $this->assertGreaterThan(0, $tokenUsage->getPromptTokens());
        $this->assertGreaterThan(0, $tokenUsage->getCompletionTokens());
        $this->assertGreaterThan(0, $tokenUsage->getTotalTokens());
    }
}

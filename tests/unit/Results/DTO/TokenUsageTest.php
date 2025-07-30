<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Results\DTO\TokenUsage;

/**
 * @covers \WordPress\AiClient\Results\DTO\TokenUsage
 */
class TokenUsageTest extends TestCase
{
    /**
     * Tests creating TokenUsage with valid values.
     *
     * @return void
     */
    public function testCreateWithValidValues(): void
    {
        $tokenUsage = new TokenUsage(100, 50, 150);
        
        $this->assertEquals(100, $tokenUsage->getPromptTokens());
        $this->assertEquals(50, $tokenUsage->getCompletionTokens());
        $this->assertEquals(150, $tokenUsage->getTotalTokens());
    }

    /**
     * Tests creating TokenUsage with zero values.
     *
     * @return void
     */
    public function testCreateWithZeroValues(): void
    {
        $tokenUsage = new TokenUsage(0, 0, 0);
        
        $this->assertEquals(0, $tokenUsage->getPromptTokens());
        $this->assertEquals(0, $tokenUsage->getCompletionTokens());
        $this->assertEquals(0, $tokenUsage->getTotalTokens());
    }

    /**
     * Tests creating TokenUsage with large values.
     *
     * @return void
     */
    public function testCreateWithLargeValues(): void
    {
        $tokenUsage = new TokenUsage(1000000, 500000, 1500000);
        
        $this->assertEquals(1000000, $tokenUsage->getPromptTokens());
        $this->assertEquals(500000, $tokenUsage->getCompletionTokens());
        $this->assertEquals(1500000, $tokenUsage->getTotalTokens());
    }

    /**
     * Tests different token usage scenarios.
     *
     * @dataProvider tokenUsageScenarioProvider
     * @param int $promptTokens
     * @param int $completionTokens
     * @param int $totalTokens
     * @return void
     */
    public function testDifferentTokenUsageScenarios(int $promptTokens, int $completionTokens, int $totalTokens): void
    {
        $tokenUsage = new TokenUsage($promptTokens, $completionTokens, $totalTokens);
        
        $this->assertEquals($promptTokens, $tokenUsage->getPromptTokens());
        $this->assertEquals($completionTokens, $tokenUsage->getCompletionTokens());
        $this->assertEquals($totalTokens, $tokenUsage->getTotalTokens());
    }

    /**
     * Provides different token usage scenarios.
     *
     * @return array
     */
    public function tokenUsageScenarioProvider(): array
    {
        return [
            'small_prompt_large_completion' => [10, 1000, 1010],
            'large_prompt_small_completion' => [1000, 10, 1010],
            'equal_prompt_and_completion' => [500, 500, 1000],
            'only_prompt_tokens' => [100, 0, 100],
            'only_completion_tokens' => [0, 100, 100],
            'typical_chat_response' => [250, 750, 1000],
            'code_generation' => [50, 2000, 2050],
            'summarization' => [5000, 150, 5150],
            'max_context_window' => [4096, 4096, 8192],
            'minimal_usage' => [1, 1, 2],
        ];
    }

    /**
     * Tests JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = TokenUsage::getJsonSchema();
        
        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);
        
        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey(TokenUsage::KEY_PROMPT_TOKENS, $schema['properties']);
        $this->assertArrayHasKey(TokenUsage::KEY_COMPLETION_TOKENS, $schema['properties']);
        $this->assertArrayHasKey(TokenUsage::KEY_TOTAL_TOKENS, $schema['properties']);
        
        // Check each property type
        $this->assertEquals('integer', $schema['properties'][TokenUsage::KEY_PROMPT_TOKENS]['type']);
        $this->assertEquals('integer', $schema['properties'][TokenUsage::KEY_COMPLETION_TOKENS]['type']);
        $this->assertEquals('integer', $schema['properties'][TokenUsage::KEY_TOTAL_TOKENS]['type']);
        
        // Check descriptions
        $this->assertArrayHasKey('description', $schema['properties'][TokenUsage::KEY_PROMPT_TOKENS]);
        $this->assertArrayHasKey('description', $schema['properties'][TokenUsage::KEY_COMPLETION_TOKENS]);
        $this->assertArrayHasKey('description', $schema['properties'][TokenUsage::KEY_TOTAL_TOKENS]);
        
        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals([TokenUsage::KEY_PROMPT_TOKENS, TokenUsage::KEY_COMPLETION_TOKENS, TokenUsage::KEY_TOTAL_TOKENS], $schema['required']);
    }

    /**
     * Tests TokenUsage with GPT-3.5 typical usage.
     *
     * @return void
     */
    public function testGpt35TypicalUsage(): void
    {
        // Typical GPT-3.5 conversation
        $tokenUsage = new TokenUsage(127, 89, 216);
        
        $this->assertEquals(127, $tokenUsage->getPromptTokens());
        $this->assertEquals(89, $tokenUsage->getCompletionTokens());
        $this->assertEquals(216, $tokenUsage->getTotalTokens());
    }

    /**
     * Tests TokenUsage with GPT-4 typical usage.
     *
     * @return void
     */
    public function testGpt4TypicalUsage(): void
    {
        // Typical GPT-4 conversation with more context
        $tokenUsage = new TokenUsage(512, 256, 768);
        
        $this->assertEquals(512, $tokenUsage->getPromptTokens());
        $this->assertEquals(256, $tokenUsage->getCompletionTokens());
        $this->assertEquals(768, $tokenUsage->getTotalTokens());
    }

    /**
     * Tests TokenUsage for embedding models.
     *
     * @return void
     */
    public function testEmbeddingModelUsage(): void
    {
        // Embedding models only use prompt tokens
        $tokenUsage = new TokenUsage(1536, 0, 1536);
        
        $this->assertEquals(1536, $tokenUsage->getPromptTokens());
        $this->assertEquals(0, $tokenUsage->getCompletionTokens());
        $this->assertEquals(1536, $tokenUsage->getTotalTokens());
    }

    /**
     * Tests TokenUsage implements WithJsonSchemaInterface.
     *
     * @return void
     */
    public function testImplementsWithJsonSchemaInterface(): void
    {
        $tokenUsage = new TokenUsage(10, 20, 30);
        
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface::class,
            $tokenUsage
        );
    }

    /**
     * Tests creating multiple TokenUsage instances.
     *
     * @return void
     */
    public function testMultipleInstances(): void
    {
        $usage1 = new TokenUsage(100, 50, 150);
        $usage2 = new TokenUsage(200, 100, 300);
        $usage3 = new TokenUsage(100, 50, 150);
        
        // Different instances with different values
        $this->assertNotSame($usage1, $usage2);
        $this->assertNotEquals($usage1->getPromptTokens(), $usage2->getPromptTokens());
        
        // Different instances with same values
        $this->assertNotSame($usage1, $usage3);
        $this->assertEquals($usage1->getPromptTokens(), $usage3->getPromptTokens());
        $this->assertEquals($usage1->getCompletionTokens(), $usage3->getCompletionTokens());
        $this->assertEquals($usage1->getTotalTokens(), $usage3->getTotalTokens());
    }

    /**
     * Tests array transformation.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $tokenUsage = new TokenUsage(100, 50, 150);
        $json = $tokenUsage->toArray();
        
        $this->assertIsArray($json);
        $this->assertArrayHasKey(TokenUsage::KEY_PROMPT_TOKENS, $json);
        $this->assertArrayHasKey(TokenUsage::KEY_COMPLETION_TOKENS, $json);
        $this->assertArrayHasKey(TokenUsage::KEY_TOTAL_TOKENS, $json);
        
        $this->assertEquals(100, $json[TokenUsage::KEY_PROMPT_TOKENS]);
        $this->assertEquals(50, $json[TokenUsage::KEY_COMPLETION_TOKENS]);
        $this->assertEquals(150, $json[TokenUsage::KEY_TOTAL_TOKENS]);
    }

    /**
     * Tests fromJson method.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $json = [
            TokenUsage::KEY_PROMPT_TOKENS => 100,
            TokenUsage::KEY_COMPLETION_TOKENS => 50,
            TokenUsage::KEY_TOTAL_TOKENS => 150,
        ];
        
        $tokenUsage = TokenUsage::fromArray($json);
        
        $this->assertInstanceOf(TokenUsage::class, $tokenUsage);
        $this->assertEquals(100, $tokenUsage->getPromptTokens());
        $this->assertEquals(50, $tokenUsage->getCompletionTokens());
        $this->assertEquals(150, $tokenUsage->getTotalTokens());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $original = new TokenUsage(123, 456, 579);
        $json = $original->toArray();
        $restored = TokenUsage::fromArray($json);
        
        $this->assertEquals($original->getPromptTokens(), $restored->getPromptTokens());
        $this->assertEquals($original->getCompletionTokens(), $restored->getCompletionTokens());
        $this->assertEquals($original->getTotalTokens(), $restored->getTotalTokens());
    }

    /**
     * Tests TokenUsage implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $tokenUsage = new TokenUsage(10, 20, 30);
        
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface::class,
            $tokenUsage
        );
        
    }

    /**
     * Tests TokenUsage with streaming response simulation.
     *
     * @return void
     */
    public function testStreamingResponseUsage(): void
    {
        // Simulating a streaming response where tokens accumulate
        $initialUsage = new TokenUsage(50, 10, 60);
        $midUsage = new TokenUsage(50, 50, 100);
        $finalUsage = new TokenUsage(50, 150, 200);
        
        // Prompt tokens stay the same
        $this->assertEquals($initialUsage->getPromptTokens(), $midUsage->getPromptTokens());
        $this->assertEquals($midUsage->getPromptTokens(), $finalUsage->getPromptTokens());
        
        // Completion tokens increase
        $this->assertLessThan($midUsage->getCompletionTokens(), $initialUsage->getCompletionTokens());
        $this->assertLessThan($finalUsage->getCompletionTokens(), $midUsage->getCompletionTokens());
        
        // Total tokens increase accordingly
        $this->assertLessThan($midUsage->getTotalTokens(), $initialUsage->getTotalTokens());
        $this->assertLessThan($finalUsage->getTotalTokens(), $midUsage->getTotalTokens());
    }
}
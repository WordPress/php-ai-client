<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\integration;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WordPress\AiClient\Tests\integration\traits\IntegrationTestTrait;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Integration tests for sending parallel tool results back to a provider.
 *
 * Context for https://github.com/WordPress/php-ai-client/issues/286.
 *
 * When a model answers with several tool calls in one turn, every result has to be sent
 * back, and the SDK represents that as a *single* user message holding one
 * function-response `MessagePart` per call: `PromptBuilder::withFunctionResponse()` appends
 * to the current user message, so calling it twice produces one message with two
 * function-response parts. Any agent loop that executes a batch of tool calls and collects
 * the results arrives at the same shape.
 *
 * These tests establish whether that shape is the correct canonical representation. If
 * providers accept it, the shape is right and the defect is isolated to
 * `AbstractOpenAiCompatibleTextGenerationModel::prepareMessagesParam()`, which special-cases
 * only a single-part message. If providers reject it, the builder itself needs to emit
 * separate messages instead.
 *
 * These tests make real API calls and require the relevant provider API key to be set.
 *
 * @group integration
 * @group function-calling
 * @group parallel-tool-calls
 *
 * @coversNothing
 */
class ParallelToolCallIntegrationTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Providers that map function responses through their normal per-part conversion.
     *
     * OpenAI is intentionally absent: its provider uses the Responses API and no longer
     * extends the OpenAI-compatible chat completions implementation.
     *
     * @return array<string, array{string, string}>
     */
    public function parallelToolCallProvider(): array
    {
        return [
            'anthropic' => ['anthropic', 'ANTHROPIC_API_KEY'],
            'google' => ['google', 'GOOGLE_API_KEY'],
        ];
    }

    /**
     * Tests that several function responses in one message can be sent back.
     *
     * @dataProvider parallelToolCallProvider
     */
    public function testParallelFunctionResponsesInSingleMessage(string $providerId, string $envVar): void
    {
        $this->requireApiKey($envVar);

        $task = 'What is the weather and the current local time in Tokyo? '
            . 'Call both get_weather and get_time before answering.';

        $result1 = AiClient::prompt($task)
            ->usingProvider($providerId)
            ->usingFunctionDeclarations(...$this->functionDeclarations())
            ->generateTextResult();

        $modelMessage = $result1->toMessage();
        $functionCalls = $this->extractFunctionCalls($modelMessage);

        if (count($functionCalls) < 2) {
            $this->markTestSkipped(
                sprintf(
                    'The %s model returned %d tool call(s); this test needs a parallel tool call.',
                    $providerId,
                    count($functionCalls)
                )
            );
        }

        // Build one user message holding every function response, via the public builder API.
        $builder = AiClient::prompt()
            ->usingProvider($providerId)
            ->withHistory(
                new UserMessage([new MessagePart($task)]),
                new ModelMessage($this->sendableParts($modelMessage))
            )
            ->usingFunctionDeclarations(...$this->functionDeclarations());

        foreach ($functionCalls as $functionCall) {
            $builder->withFunctionResponse(
                new FunctionResponse(
                    $functionCall->getId() ?? 'call_' . $functionCall->getName(),
                    (string) $functionCall->getName(),
                    $this->fakeToolResult((string) $functionCall->getName())
                )
            );
        }

        // The builder has now collapsed every response into one user message; that shape is
        // pinned by PromptBuilderTest::testWithFunctionResponseCollapsesMultipleResponsesIntoOneMessage().
        try {
            $responseText = $builder->generateTextResult()->toText();
        } catch (ClientException $e) {
            /*
             * The Google provider never round-trips MessagePart::getThoughtSignature(), and Gemini
             * rejects any echoed functionCall part that lacks one. That breaks every multi-turn
             * function call, single responses included: Google's own
             * FunctionCallingIntegrationTest::testMultiTurnFunctionCalling() fails the same way.
             * It happens before the function responses are even considered, so this provider
             * cannot answer the question this test asks until that gap is fixed.
             */
            if (strpos($e->getMessage(), 'thought_signature') !== false) {
                $this->markTestSkipped(
                    sprintf(
                        'The %s provider cannot round-trip tool calls yet: %s',
                        $providerId,
                        $e->getMessage()
                    )
                );
            }
            throw $e;
        }

        $this->assertNotEmpty($responseText, 'Expected a text response that uses both tool results');
        $this->assertTrue(
            stripos($responseText, '22') !== false || stripos($responseText, 'sunny') !== false,
            'Expected the model to use the get_weather result. Got: ' . $responseText
        );
        $this->assertTrue(
            stripos($responseText, '14:30') !== false || stripos($responseText, '2:30') !== false,
            'Expected the model to use the get_time result. Got: ' . $responseText
        );
    }

    /**
     * The two tools used to provoke a parallel tool call.
     *
     * @return list<FunctionDeclaration>
     */
    private function functionDeclarations(): array
    {
        return [
            new FunctionDeclaration(
                'get_weather',
                'Get the current weather for a location',
                [
                    'type' => 'object',
                    'properties' => ['location' => ['type' => 'string', 'description' => 'City name']],
                    'required' => ['location'],
                ]
            ),
            new FunctionDeclaration(
                'get_time',
                'Get the current local time for a location',
                [
                    'type' => 'object',
                    'properties' => ['location' => ['type' => 'string', 'description' => 'City name']],
                    'required' => ['location'],
                ]
            ),
        ];
    }

    /**
     * Returns every function call in a message, in order.
     *
     * @return list<FunctionCall>
     */
    private function extractFunctionCalls(Message $message): array
    {
        $functionCalls = [];
        foreach ($message->getParts() as $part) {
            if ($part->getType()->isFunctionCall()) {
                $functionCall = $part->getFunctionCall();
                if ($functionCall instanceof FunctionCall) {
                    $functionCalls[] = $functionCall;
                }
            }
        }

        return $functionCalls;
    }

    /**
     * Strips parts that cannot be sent back to the provider.
     *
     * The Anthropic provider cannot round-trip thinking blocks.
     *
     * @return list<MessagePart>
     */
    private function sendableParts(Message $message): array
    {
        $parts = [];
        foreach ($message->getParts() as $part) {
            if ($part->getChannel()->isThought()) {
                continue;
            }
            $parts[] = $part;
        }

        return $parts;
    }

    /**
     * Canned tool results, so the assertions can look for known values.
     *
     * @return array<string, mixed>
     */
    private function fakeToolResult(string $functionName): array
    {
        if ($functionName === 'get_time') {
            return ['time' => '14:30', 'timezone' => 'Asia/Tokyo'];
        }

        return ['temperature' => 22, 'unit' => 'celsius', 'condition' => 'sunny'];
    }
}

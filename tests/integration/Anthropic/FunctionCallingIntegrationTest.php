<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\integration\Anthropic;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Tests\integration\traits\IntegrationTestTrait;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;

/**
 * Integration tests for Anthropic function calling.
 *
 * These tests make real API calls to Anthropic and require the ANTHROPIC_API_KEY
 * environment variable to be set.
 *
 * @group integration
 * @group anthropic
 * @group function-calling
 *
 * @coversNothing
 */
class FunctionCallingIntegrationTest extends TestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireApiKey('ANTHROPIC_API_KEY');
    }

    /**
     * Tests function calling with multiple arguments.
     */
    public function testFunctionCallingWithMultipleArguments(): void
    {
        $getWeather = new FunctionDeclaration(
            'get_weather',
            'Get the current weather for a location',
            [
                'type' => 'object',
                'properties' => [
                    'location' => [
                        'type' => 'string',
                        'description' => 'The city and state, e.g. San Francisco, CA',
                    ],
                    'unit' => [
                        'type' => 'string',
                        'enum' => ['celsius', 'fahrenheit'],
                        'description' => 'The temperature unit',
                    ],
                ],
                'required' => ['location', 'unit'],
            ]
        );

        $result = AiClient::prompt('What is the weather in Paris, France? Use celsius.')
            ->usingProvider('anthropic')
            ->usingFunctionDeclarations($getWeather)
            ->generateTextResult();

        $this->assertInstanceOf(GenerativeAiResult::class, $result);

        $functionCall = $this->extractFunctionCall($result);
        $this->assertNotNull($functionCall, 'Expected a function call in the response');
        $this->assertEquals('get_weather', $functionCall->getName());

        $args = $functionCall->getArgs();
        $this->assertIsArray($args);
        $this->assertArrayHasKey('location', $args);
        $this->assertArrayHasKey('unit', $args);
        $this->assertStringContainsStringIgnoringCase('paris', $args['location']);
        $this->assertEquals('celsius', $args['unit']);
    }

    /**
     * Tests function calling with no arguments.
     */
    public function testFunctionCallingWithNoArguments(): void
    {
        $sayHi = new FunctionDeclaration(
            'say_hi',
            'Says hi to the user. Call this function when the user asks for a greeting.',
            null
        );

        $result = AiClient::prompt('Please greet me.')
            ->usingProvider('anthropic')
            ->usingFunctionDeclarations($sayHi)
            ->generateTextResult();

        $this->assertInstanceOf(GenerativeAiResult::class, $result);

        $functionCall = $this->extractFunctionCall($result);
        $this->assertNotNull($functionCall, 'Expected a function call in the response');
        $this->assertEquals('say_hi', $functionCall->getName());

        // Args should be null or empty for a no-argument function
        $args = $functionCall->getArgs();
        $this->assertTrue(
            $args === null || $args === [] || $args === (object) [],
            'Expected no arguments for say_hi function'
        );
    }

    /**
     * Tests function calling with a non-object parameter (string).
     *
     * Note: This tests a function where the parameter schema is a simple string,
     * not an object with properties. This is a less common but valid use case.
     */
    public function testFunctionCallingWithStringParameter(): void
    {
        $sayHiTo = new FunctionDeclaration(
            'say_hi_to',
            'Says hi to a specific person by name',
            [
                'type' => 'string',
                'description' => 'The name of the person to greet',
            ]
        );

        $result = AiClient::prompt('Say hi to Bob.')
            ->usingProvider('anthropic')
            ->usingFunctionDeclarations($sayHiTo)
            ->generateTextResult();

        $this->assertInstanceOf(GenerativeAiResult::class, $result);

        $functionCall = $this->extractFunctionCall($result);
        $this->assertNotNull($functionCall, 'Expected a function call in the response');
        $this->assertEquals('say_hi_to', $functionCall->getName());

        $args = $functionCall->getArgs();
        // The args should be the string "Bob" or contain "Bob" somewhere
        $this->assertNotNull($args, 'Expected arguments for say_hi_to function');
        if (is_string($args)) {
            $this->assertStringContainsStringIgnoringCase('bob', $args);
        } elseif (is_array($args)) {
            // Some providers may wrap the string in an object/array
            $argsJson = json_encode($args);
            $this->assertStringContainsStringIgnoringCase('bob', $argsJson);
        }
    }

    /**
     * Extracts the first function call from a result.
     *
     * @param GenerativeAiResult $result The result to extract from.
     * @return FunctionCall|null The function call or null if not found.
     */
    private function extractFunctionCall(GenerativeAiResult $result): ?FunctionCall
    {
        $candidates = $result->getCandidates();
        if (empty($candidates)) {
            return null;
        }

        $message = $candidates[0]->getMessage();
        foreach ($message->getParts() as $part) {
            if ($part->getType()->isFunctionCall()) {
                return $part->getFunctionCall();
            }
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\integration\Google;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Tests\integration\traits\IntegrationTestTrait;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Integration tests for Google function calling.
 *
 * These tests make real API calls to Google and require the GOOGLE_API_KEY
 * environment variable to be set.
 *
 * @group integration
 * @group google
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
        $this->requireApiKey('GOOGLE_API_KEY');
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
            ->usingProvider('google')
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
            ->usingProvider('google')
            ->usingFunctionDeclarations($sayHi)
            ->generateTextResult();

        $this->assertInstanceOf(GenerativeAiResult::class, $result);

        $functionCall = $this->extractFunctionCall($result);
        $this->assertNotNull($functionCall, 'Expected a function call in the response');
        $this->assertEquals('say_hi', $functionCall->getName());

        // Args should be null for a no-argument function
        $this->assertNull($functionCall->getArgs(), 'Expected null arguments for say_hi function');
    }

    /**
     * Tests multi-turn function calling with function response.
     */
    public function testMultiTurnFunctionCalling(): void
    {
        $getWeather = new FunctionDeclaration(
            'get_weather',
            'Get the current weather for a location',
            [
                'type' => 'object',
                'properties' => [
                    'location' => ['type' => 'string', 'description' => 'City name'],
                ],
                'required' => ['location'],
            ]
        );

        // Step 1: Initial request that triggers function call
        $result1 = AiClient::prompt('What is the weather in Tokyo?')
            ->usingProvider('google')
            ->usingFunctionDeclarations($getWeather)
            ->generateTextResult();

        $functionCall = $this->extractFunctionCall($result1);
        $this->assertNotNull($functionCall, 'Expected a function call in the response');
        $this->assertEquals('get_weather', $functionCall->getName());

        // Step 2: Build history from first response
        $userMessage = new UserMessage([new MessagePart('What is the weather in Tokyo?')]);
        $assistantMessage = $result1->getCandidates()[0]->getMessage();

        // Step 3: Create function response with simulated result
        // Note: Google doesn't return function call IDs, so we use a generated one
        $functionResponse = new FunctionResponse(
            $functionCall->getId() ?? 'call_123',
            'get_weather',
            ['temperature' => 22, 'condition' => 'sunny']
        );

        // Step 4: Send follow-up with history + function response
        $result2 = AiClient::prompt()
            ->usingProvider('google')
            ->withHistory($userMessage, $assistantMessage)
            ->withFunctionResponse($functionResponse)
            ->usingFunctionDeclarations($getWeather)
            ->generateTextResult();

        // Step 5: Verify model uses the function result
        $responseText = $result2->toText();
        $this->assertNotEmpty($responseText, 'Expected a text response');
        $this->assertTrue(
            stripos($responseText, '22') !== false ||
            stripos($responseText, 'sunny') !== false ||
            stripos($responseText, 'Tokyo') !== false,
            'Expected model to use function result in response. Got: ' . $responseText
        );
    }

    /**
     * Tests function calling with multiple function declarations.
     */
    public function testMultipleFunctionDeclarations(): void
    {
        $getWeather = new FunctionDeclaration(
            'get_weather',
            'Get weather for a location',
            ['type' => 'object', 'properties' => ['location' => ['type' => 'string']], 'required' => ['location']]
        );

        $getTime = new FunctionDeclaration(
            'get_time',
            'Get the current local time in a specific timezone or city. Use this when the user asks what time it is.',
            [
                'type' => 'object',
                'properties' => ['timezone' => ['type' => 'string', 'description' => 'The timezone or city name']],
                'required' => ['timezone'],
            ]
        );

        $searchWeb = new FunctionDeclaration(
            'search_web',
            'Search the web for general information or news articles',
            ['type' => 'object', 'properties' => ['query' => ['type' => 'string']], 'required' => ['query']]
        );

        // Prompt should trigger get_time specifically
        $result = AiClient::prompt('Use the get_time function to tell me what time it is in London.')
            ->usingProvider('google')
            ->usingFunctionDeclarations($getWeather, $getTime, $searchWeb)
            ->generateTextResult();

        $functionCall = $this->extractFunctionCall($result);
        $this->assertNotNull($functionCall, 'Expected a function call');
        $this->assertEquals('get_time', $functionCall->getName(), 'Expected get_time to be selected');

        $args = $functionCall->getArgs();
        $this->assertIsArray($args);
        $this->assertArrayHasKey('timezone', $args);
        $this->assertStringContainsStringIgnoringCase('london', $args['timezone']);
    }

    /**
     * Tests function calling with optional parameters.
     */
    public function testFunctionCallingWithOptionalParameters(): void
    {
        $searchProducts = new FunctionDeclaration(
            'search_products',
            'Search for products in the catalog',
            [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Search query'],
                    'category' => ['type' => 'string', 'description' => 'Optional category filter'],
                    'max_price' => ['type' => 'number', 'description' => 'Optional max price'],
                ],
                'required' => ['query'],
            ]
        );

        $result = AiClient::prompt('Find me some laptops')
            ->usingProvider('google')
            ->usingFunctionDeclarations($searchProducts)
            ->generateTextResult();

        $functionCall = $this->extractFunctionCall($result);
        $this->assertNotNull($functionCall, 'Expected a function call');
        $this->assertEquals('search_products', $functionCall->getName());

        $args = $functionCall->getArgs();
        $this->assertIsArray($args);
        $this->assertArrayHasKey('query', $args);
        $this->assertStringContainsStringIgnoringCase('laptop', $args['query']);
    }

    /**
     * Tests function calling with nested object parameters.
     */
    public function testFunctionCallingWithNestedParameters(): void
    {
        $createOrder = new FunctionDeclaration(
            'create_order',
            'Create an order with shipping address',
            [
                'type' => 'object',
                'properties' => [
                    'product' => ['type' => 'string', 'description' => 'Product name'],
                    'shipping_address' => [
                        'type' => 'object',
                        'properties' => [
                            'street' => ['type' => 'string'],
                            'city' => ['type' => 'string'],
                            'zip' => ['type' => 'string'],
                        ],
                        'required' => ['street', 'city'],
                    ],
                ],
                'required' => ['product', 'shipping_address'],
            ]
        );

        $result = AiClient::prompt('Order a book and ship it to 123 Main St, New York, 10001')
            ->usingProvider('google')
            ->usingFunctionDeclarations($createOrder)
            ->generateTextResult();

        $functionCall = $this->extractFunctionCall($result);
        $this->assertNotNull($functionCall, 'Expected a function call');
        $this->assertEquals('create_order', $functionCall->getName());

        $args = $functionCall->getArgs();
        $this->assertIsArray($args);
        $this->assertArrayHasKey('shipping_address', $args);
        $this->assertIsArray($args['shipping_address']);
        $this->assertArrayHasKey('city', $args['shipping_address']);
        $this->assertStringContainsStringIgnoringCase('new york', $args['shipping_address']['city']);
    }

    /**
     * Tests function calling with array parameters.
     */
    public function testFunctionCallingWithArrayParameters(): void
    {
        $addToCart = new FunctionDeclaration(
            'add_to_cart',
            'Add multiple items to shopping cart',
            [
                'type' => 'object',
                'properties' => [
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'quantity' => ['type' => 'integer'],
                            ],
                            'required' => ['name', 'quantity'],
                        ],
                        'description' => 'List of items to add',
                    ],
                ],
                'required' => ['items'],
            ]
        );

        $result = AiClient::prompt('Add 2 apples and 3 oranges to my cart')
            ->usingProvider('google')
            ->usingFunctionDeclarations($addToCart)
            ->generateTextResult();

        $functionCall = $this->extractFunctionCall($result);
        $this->assertNotNull($functionCall, 'Expected a function call');
        $this->assertEquals('add_to_cart', $functionCall->getName());

        $args = $functionCall->getArgs();
        $this->assertIsArray($args);
        $this->assertArrayHasKey('items', $args);
        $this->assertIsArray($args['items']);
        $this->assertCount(2, $args['items']);

        foreach ($args['items'] as $item) {
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('quantity', $item);
            $this->assertIsInt($item['quantity']);
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

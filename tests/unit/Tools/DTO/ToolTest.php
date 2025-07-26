<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Tools\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Enums\ToolTypeEnum;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\Tool;
use WordPress\AiClient\Tools\DTO\WebSearch;

/**
 * @covers \WordPress\AiClient\Tools\DTO\Tool
 */
class ToolTest extends TestCase
{
    /**
     * Tests creating tool with function declarations.
     *
     * @return void
     */
    public function testCreateWithFunctionDeclarations(): void
    {
        $function1 = new FunctionDeclaration(
            'searchDatabase',
            'Searches the database for records',
            ['query' => ['type' => 'string', 'description' => 'Search query']]
        );
        $function2 = new FunctionDeclaration(
            'sendEmail',
            'Sends an email',
            ['to' => ['type' => 'string'], 'subject' => ['type' => 'string']]
        );
        
        $tool = new Tool([$function1, $function2]);
        
        $this->assertEquals(ToolTypeEnum::functionDeclarations(), $tool->getType());
        $this->assertTrue($tool->getType()->isFunctionDeclarations());
        $this->assertCount(2, $tool->getFunctionDeclarations());
        $this->assertSame([$function1, $function2], $tool->getFunctionDeclarations());
        $this->assertNull($tool->getWebSearch());
    }

    /**
     * Tests creating tool with single function declaration.
     *
     * @return void
     */
    public function testCreateWithSingleFunctionDeclaration(): void
    {
        $function = new FunctionDeclaration(
            'getCurrentWeather',
            'Gets the current weather for a location'
        );
        
        $tool = new Tool([$function]);
        
        $this->assertEquals(ToolTypeEnum::functionDeclarations(), $tool->getType());
        $this->assertCount(1, $tool->getFunctionDeclarations());
        $this->assertSame($function, $tool->getFunctionDeclarations()[0]);
    }

    /**
     * Tests creating tool with empty function declarations array.
     *
     * @return void
     */
    public function testCreateWithEmptyFunctionDeclarationsArray(): void
    {
        $tool = new Tool([]);
        
        $this->assertEquals(ToolTypeEnum::functionDeclarations(), $tool->getType());
        $this->assertCount(0, $tool->getFunctionDeclarations());
        $this->assertEquals([], $tool->getFunctionDeclarations());
    }

    /**
     * Tests creating tool with web search.
     *
     * @return void
     */
    public function testCreateWithWebSearch(): void
    {
        $webSearch = new WebSearch(
            ['example.com', 'docs.example.com'],
            ['spam.com', 'malware.com']
        );
        
        $tool = new Tool($webSearch);
        
        $this->assertEquals(ToolTypeEnum::webSearch(), $tool->getType());
        $this->assertTrue($tool->getType()->isWebSearch());
        $this->assertSame($webSearch, $tool->getWebSearch());
        $this->assertNull($tool->getFunctionDeclarations());
    }

    /**
     * Tests creating tool with invalid content throws exception.
     *
     * @return void
     */
    public function testCreateWithInvalidContentThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Tool content must be an array of FunctionDeclaration instances or a WebSearch instance'
        );
        
        new Tool('invalid content');
    }

    /**
     * Tests creating tool with object that is not WebSearch throws exception.
     *
     * @return void
     */
    public function testCreateWithInvalidObjectThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Tool content must be an array of FunctionDeclaration instances or a WebSearch instance'
        );
        
        new Tool(new \stdClass());
    }

    /**
     * Tests JSON schema for function declarations tool.
     *
     * @return void
     */
    public function testJsonSchemaForFunctionDeclarationsTool(): void
    {
        $schema = Tool::getJsonSchema();
        
        $this->assertArrayHasKey('oneOf', $schema);
        $this->assertCount(2, $schema['oneOf']);
        
        // First schema is for function declarations
        $functionSchema = $schema['oneOf'][0];
        $this->assertEquals('object', $functionSchema['type']);
        $this->assertArrayHasKey('properties', $functionSchema);
        $this->assertArrayHasKey('type', $functionSchema['properties']);
        $this->assertArrayHasKey('functionDeclarations', $functionSchema['properties']);
        
        // Type property
        $typeProperty = $functionSchema['properties']['type'];
        $this->assertEquals('string', $typeProperty['type']);
        $this->assertEquals(ToolTypeEnum::functionDeclarations()->value, $typeProperty['const']);
        
        // Function declarations property
        $functionsProperty = $functionSchema['properties']['functionDeclarations'];
        $this->assertEquals('array', $functionsProperty['type']);
        $this->assertArrayHasKey('items', $functionsProperty);
        
        // Required fields
        $this->assertEquals(['type', 'functionDeclarations'], $functionSchema['required']);
    }

    /**
     * Tests JSON schema for web search tool.
     *
     * @return void
     */
    public function testJsonSchemaForWebSearchTool(): void
    {
        $schema = Tool::getJsonSchema();
        
        // Second schema is for web search
        $webSearchSchema = $schema['oneOf'][1];
        $this->assertEquals('object', $webSearchSchema['type']);
        $this->assertArrayHasKey('properties', $webSearchSchema);
        $this->assertArrayHasKey('type', $webSearchSchema['properties']);
        $this->assertArrayHasKey('webSearch', $webSearchSchema['properties']);
        
        // Type property
        $typeProperty = $webSearchSchema['properties']['type'];
        $this->assertEquals('string', $typeProperty['type']);
        $this->assertEquals(ToolTypeEnum::webSearch()->value, $typeProperty['const']);
        
        // Web search property
        $this->assertArrayHasKey('webSearch', $webSearchSchema['properties']);
        
        // Required fields
        $this->assertEquals(['type', 'webSearch'], $webSearchSchema['required']);
    }

    /**
     * Tests tool with multiple complex function declarations.
     *
     * @return void
     */
    public function testWithMultipleComplexFunctionDeclarations(): void
    {
        $functions = [
            new FunctionDeclaration(
                'createUser',
                'Creates a new user in the system',
                [
                    'username' => [
                        'type' => 'string',
                        'description' => 'The username',
                        'minLength' => 3,
                        'maxLength' => 20
                    ],
                    'email' => [
                        'type' => 'string',
                        'format' => 'email',
                        'description' => 'The user email'
                    ],
                    'role' => [
                        'type' => 'string',
                        'enum' => ['admin', 'user', 'guest'],
                        'description' => 'The user role'
                    ]
                ]
            ),
            new FunctionDeclaration(
                'deleteUser',
                'Deletes a user from the system',
                [
                    'userId' => [
                        'type' => 'integer',
                        'description' => 'The user ID to delete'
                    ]
                ]
            ),
            new FunctionDeclaration(
                'listUsers',
                'Lists all users with optional filtering',
                [
                    'role' => [
                        'type' => 'string',
                        'enum' => ['admin', 'user', 'guest'],
                        'description' => 'Filter by role'
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 100,
                        'default' => 10
                    ]
                ]
            )
        ];
        
        $tool = new Tool($functions);
        
        $this->assertCount(3, $tool->getFunctionDeclarations());
        $this->assertEquals('createUser', $tool->getFunctionDeclarations()[0]->getName());
        $this->assertEquals('deleteUser', $tool->getFunctionDeclarations()[1]->getName());
        $this->assertEquals('listUsers', $tool->getFunctionDeclarations()[2]->getName());
    }

    /**
     * Tests tool implements WithJsonSchemaInterface.
     *
     * @return void
     */
    public function testImplementsWithJsonSchemaInterface(): void
    {
        $tool = new Tool([]);
        
        $this->assertInstanceOf(
            \WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface::class,
            $tool
        );
    }

    /**
     * Tests creating multiple tool instances.
     *
     * @return void
     */
    public function testMultipleToolInstances(): void
    {
        $function = new FunctionDeclaration('test', 'Test function');
        $webSearch = new WebSearch(['example.com'], ['spam.com']);
        
        $tool1 = new Tool([$function]);
        $tool2 = new Tool($webSearch);
        $tool3 = new Tool([$function]);
        
        // Different tool types
        $this->assertNotEquals($tool1->getType(), $tool2->getType());
        
        // Same content type but different instances
        $this->assertNotSame($tool1, $tool3);
        $this->assertEquals($tool1->getType(), $tool3->getType());
        
        // Check content accessors
        $this->assertNotNull($tool1->getFunctionDeclarations());
        $this->assertNull($tool1->getWebSearch());
        $this->assertNull($tool2->getFunctionDeclarations());
        $this->assertNotNull($tool2->getWebSearch());
    }
}
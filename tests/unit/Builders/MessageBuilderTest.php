<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\Unit\Builders;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Builders\MessageBuilder;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Unit tests for the MessageBuilder class.
 *
 * @covers \WordPress\AiClient\Builders\MessageBuilder
 */
class MessageBuilderTest extends TestCase
{
    /**
     * Tests that a simple message with text can be built.
     *
     * @return void
     */
    public function testBuildsSimpleTextMessage(): void
    {
        $builder = new MessageBuilder('Hello, AI!');
        $message = $builder->usingUserRole()->get();

        $this->assertInstanceOf(Message::class, $message);
        $this->assertTrue($message->getRole()->isUser());

        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isText());
        $this->assertEquals('Hello, AI!', $parts[0]->getText());
    }

    /**
     * Tests that text can be added with the withText method.
     *
     * @return void
     */
    public function testWithTextAddsTextPart(): void
    {
        $builder = new MessageBuilder();
        $message = $builder
            ->withText('First text')
            ->withText('Second text')
            ->usingModelRole()
            ->get();

        $parts = $message->getParts();
        $this->assertCount(2, $parts);
        $this->assertEquals('First text', $parts[0]->getText());
        $this->assertEquals('Second text', $parts[1]->getText());
    }

    /**
     * Tests that empty text throws an exception.
     *
     * @return void
     */
    public function testWithTextThrowsExceptionForEmptyText(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Text content cannot be empty.');

        $builder = new MessageBuilder();
        $builder->withText('   ');
    }

    /**
     * Tests that a file can be added to the message.
     *
     * @return void
     */
    public function testWithFileAddsFilePart(): void
    {
        $builder = new MessageBuilder();
        $message = $builder
            ->withFile('data:image/png;base64,iVBORw0KGgo=', 'image/png')
            ->usingUserRole()
            ->get();

        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isFile());

        $file = $parts[0]->getFile();
        $this->assertInstanceOf(File::class, $file);
        $this->assertEquals('image/png', $file->getMimeType());
    }

    /**
     * Tests that a File object can be passed directly.
     *
     * @return void
     */
    public function testWithFileAcceptsFileObject(): void
    {
        $file = new File('data:image/png;base64,iVBORw0KGgo=', 'image/png');

        $builder = new MessageBuilder();
        $message = $builder
            ->withFile($file)
            ->usingUserRole()
            ->get();

        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isFile());
        $this->assertSame($file, $parts[0]->getFile());
    }

    /**
     * Tests that function calls can be added to model messages.
     *
     * @return void
     */
    public function testWithFunctionCallAddsToModelMessage(): void
    {
        $functionCall = new FunctionCall('call_id', 'test_function', ['arg' => 'value']);

        $builder = new MessageBuilder();
        $message = $builder
            ->usingModelRole()
            ->withFunctionCall($functionCall)
            ->get();

        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isFunctionCall());
        $this->assertSame($functionCall, $parts[0]->getFunctionCall());
    }

    /**
     * Tests that function responses can be added to user messages.
     *
     * @return void
     */
    public function testWithFunctionResponseAddsToUserMessage(): void
    {
        $functionResponse = new FunctionResponse('response_id', 'test_function', ['result' => 'success']);

        $builder = new MessageBuilder();
        $message = $builder
            ->usingUserRole()
            ->withFunctionResponse($functionResponse)
            ->get();

        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isFunctionResponse());
        $this->assertSame($functionResponse, $parts[0]->getFunctionResponse());
    }

    /**
     * Tests that multiple message parts can be added at once.
     *
     * @return void
     */
    public function testWithMessagePartsAddsMultipleParts(): void
    {
        $part1 = new MessagePart('Text 1');
        $part2 = new MessagePart('Text 2');
        $part3 = new MessagePart(new File('data:image/png;base64,test', 'image/png'));

        $builder = new MessageBuilder();
        $message = $builder
            ->usingUserRole()
            ->withMessageParts($part1, $part2, $part3)
            ->get();

        $parts = $message->getParts();
        $this->assertCount(3, $parts);
        $this->assertSame($part1, $parts[0]);
        $this->assertSame($part2, $parts[1]);
        $this->assertSame($part3, $parts[2]);
    }

    /**
     * Tests that roles can be set using usingRole method.
     *
     * @return void
     */
    public function testUsingRoleSetsRole(): void
    {
        $builder = new MessageBuilder('Test');

        $userMessage = $builder->usingRole(MessageRoleEnum::user())->get();
        $this->assertTrue($userMessage->getRole()->isUser());

        $builder = new MessageBuilder('Test');
        $modelMessage = $builder->usingRole(MessageRoleEnum::model())->get();
        $this->assertTrue($modelMessage->getRole()->isModel());
    }

    /**
     * Tests that usingUserRole sets the role to user.
     *
     * @return void
     */
    public function testUsingUserRoleSetsUserRole(): void
    {
        $builder = new MessageBuilder('Test');
        $message = $builder->usingUserRole()->get();

        $this->assertTrue($message->getRole()->isUser());
    }

    /**
     * Tests that usingModelRole sets the role to model.
     *
     * @return void
     */
    public function testUsingModelRoleSetsModelRole(): void
    {
        $builder = new MessageBuilder('Test');
        $message = $builder->usingModelRole()->get();

        $this->assertTrue($message->getRole()->isModel());
    }

    /**
     * Tests that building without parts throws an exception.
     *
     * @return void
     */
    public function testGetThrowsExceptionForEmptyParts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot build an empty message. Add content using withText() or similar methods.'
        );

        $builder = new MessageBuilder();
        $builder->usingUserRole()->get();
    }

    /**
     * Tests that building without a role throws an exception.
     *
     * @return void
     */
    public function testGetThrowsExceptionForNoRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Cannot build a message with no role. Set a role using usingRole() or similar methods.'
        );

        $builder = new MessageBuilder();
        $builder->withText('Test')->get();
    }

    /**
     * Tests that function calls in user messages are rejected during validation.
     *
     * @return void
     */
    public function testValidationRejectsFunctionCallsInUserMessages(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User messages cannot contain function calls.');

        $functionCall = new FunctionCall(null, 'test', []);

        $builder = new MessageBuilder();
        $builder
            ->withFunctionCall($functionCall)
            ->usingUserRole()
            ->get();
    }

    /**
     * Tests that function responses in model messages are rejected during validation.
     *
     * @return void
     */
    public function testValidationRejectsFunctionResponsesInModelMessages(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Model messages cannot contain function responses.');

        $functionResponse = new FunctionResponse('id', 'test', []);

        $builder = new MessageBuilder();
        $builder
            ->withFunctionResponse($functionResponse)
            ->usingModelRole()
            ->get();
    }

    /**
     * Tests that role can be set after adding parts.
     *
     * @return void
     */
    public function testRoleCanBeSetAfterAddingParts(): void
    {
        $builder = new MessageBuilder();
        $message = $builder
            ->withText('Hello')
            ->withText('World')
            ->usingUserRole()
            ->get();

        $this->assertTrue($message->getRole()->isUser());
        $this->assertCount(2, $message->getParts());
    }

    /**
     * Tests that the builder is fluent.
     *
     * @return void
     */
    public function testBuilderIsFluent(): void
    {
        $builder = new MessageBuilder();

        $result1 = $builder->withText('Test');
        $this->assertSame($builder, $result1);

        $result2 = $builder->usingUserRole();
        $this->assertSame($builder, $result2);

        $result3 = $builder->withFile('data:text/plain;base64,test', 'text/plain');
        $this->assertSame($builder, $result3);
    }

    /**
     * Tests that mixed content types can be added to a message.
     *
     * @return void
     */
    public function testMixedContentMessage(): void
    {
        $file = new File('data:image/png;base64,test', 'image/png');

        $builder = new MessageBuilder();
        $message = $builder
            ->withText('Analyze this image:')
            ->withFile($file)
            ->withText('What do you see?')
            ->usingUserRole()
            ->get();

        $parts = $message->getParts();
        $this->assertCount(3, $parts);
        $this->assertTrue($parts[0]->getType()->isText());
        $this->assertTrue($parts[1]->getType()->isFile());
        $this->assertTrue($parts[2]->getType()->isText());
    }

    /**
     * Tests constructor with initial text and role.
     *
     * @return void
     */
    public function testConstructorWithTextAndRole(): void
    {
        $builder = new MessageBuilder('Initial text', MessageRoleEnum::model());
        $message = $builder->get();

        $this->assertTrue($message->getRole()->isModel());
        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertEquals('Initial text', $parts[0]->getText());
    }

    /**
     * Tests that validation allows valid combinations.
     *
     * @return void
     */
    public function testValidationAllowsValidCombinations(): void
    {
        // User message with function response only - should work
        // (Function responses must be the only part in a message)
        $functionResponse = new FunctionResponse('resp_id', 'test', ['result' => 'ok']);
        $builder1 = new MessageBuilder();
        $message1 = $builder1
            ->usingUserRole()
            ->withFunctionResponse($functionResponse)
            ->get();

        $this->assertTrue($message1->getRole()->isUser());
        $this->assertCount(1, $message1->getParts());

        // Model message with text and function call - should work
        // (Model can combine text with function calls)
        $functionCall = new FunctionCall(null, 'test', ['param' => 'value']);
        $builder2 = new MessageBuilder();
        $message2 = $builder2
            ->usingModelRole()
            ->withText('I will call a function:')
            ->withFunctionCall($functionCall)
            ->get();

        $this->assertTrue($message2->getRole()->isModel());
        $this->assertCount(2, $message2->getParts());
    }

    /**
     * Tests constructor with MessagePart input.
     *
     * @return void
     */
    public function testConstructorWithMessagePartInput(): void
    {
        $messagePart = new MessagePart('Test text');
        $builder = new MessageBuilder($messagePart, MessageRoleEnum::user());
        $message = $builder->get();

        $this->assertTrue($message->getRole()->isUser());
        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertSame($messagePart, $parts[0]);
    }

    /**
     * Tests constructor with File input.
     *
     * @return void
     */
    public function testConstructorWithFileInput(): void
    {
        $file = new File('data:image/png;base64,test', 'image/png');
        $builder = new MessageBuilder($file, MessageRoleEnum::user());
        $message = $builder->get();

        $this->assertTrue($message->getRole()->isUser());
        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isFile());
        $this->assertSame($file, $parts[0]->getFile());
    }

    /**
     * Tests constructor with FunctionCall input.
     *
     * @return void
     */
    public function testConstructorWithFunctionCallInput(): void
    {
        $functionCall = new FunctionCall('id', 'test_func', ['arg' => 'val']);
        $builder = new MessageBuilder($functionCall, MessageRoleEnum::model());
        $message = $builder->get();

        $this->assertTrue($message->getRole()->isModel());
        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isFunctionCall());
        $this->assertSame($functionCall, $parts[0]->getFunctionCall());
    }

    /**
     * Tests constructor with FunctionResponse input.
     *
     * @return void
     */
    public function testConstructorWithFunctionResponseInput(): void
    {
        $functionResponse = new FunctionResponse('id', 'test_func', ['result' => 'success']);
        $builder = new MessageBuilder($functionResponse, MessageRoleEnum::user());
        $message = $builder->get();

        $this->assertTrue($message->getRole()->isUser());
        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isFunctionResponse());
        $this->assertSame($functionResponse, $parts[0]->getFunctionResponse());
    }

    /**
     * Tests constructor with MessagePartArrayShape input.
     *
     * @return void
     */
    public function testConstructorWithMessagePartArrayShapeInput(): void
    {
        $partArray = ['text' => 'Hello from array'];
        $builder = new MessageBuilder($partArray, MessageRoleEnum::user());
        $message = $builder->get();

        $this->assertTrue($message->getRole()->isUser());
        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertTrue($parts[0]->getType()->isText());
        $this->assertEquals('Hello from array', $parts[0]->getText());
    }

    /**
     * Tests constructor with invalid input throws exception.
     *
     * @return void
     */
    public function testConstructorWithInvalidInputThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Input must be a string, MessagePart, MessagePartArrayShape, File, FunctionCall, or FunctionResponse.'
        );

        new MessageBuilder(['invalid' => 'array']);
    }

    /**
     * Tests constructor with null input creates empty builder.
     *
     * @return void
     */
    public function testConstructorWithNullInputCreatesEmptyBuilder(): void
    {
        $builder = new MessageBuilder(null, MessageRoleEnum::user());

        // Should be able to add content and build
        $message = $builder->withText('Added later')->get();

        $this->assertTrue($message->getRole()->isUser());
        $parts = $message->getParts();
        $this->assertCount(1, $parts);
        $this->assertEquals('Added later', $parts[0]->getText());
    }

    /**
     * Tests that cloning creates independent message part references.
     *
     * @return void
     */
    public function testCloneCreatesDifferentPartsReferences(): void
    {
        $original = new MessageBuilder();
        $original->withText('Hello')
            ->withText('World')
            ->usingUserRole();

        $cloned = clone $original;

        // Build both to compare the parts
        $originalMessage = $original->get();
        $clonedMessage = $cloned->get();

        $originalParts = $originalMessage->getParts();
        $clonedParts = $clonedMessage->getParts();

        // Should have same count and equivalent content
        $this->assertCount(2, $clonedParts);
        $this->assertEquals($originalParts[0]->getText(), $clonedParts[0]->getText());
        $this->assertEquals($originalParts[1]->getText(), $clonedParts[1]->getText());

        // But parts should be different instances
        $this->assertNotSame($originalParts[0], $clonedParts[0]);
        $this->assertNotSame($originalParts[1], $clonedParts[1]);
    }

    /**
     * Tests that cloning works correctly with empty parts.
     *
     * @return void
     */
    public function testCloneWorksWithEmptyParts(): void
    {
        $original = new MessageBuilder(null, MessageRoleEnum::user());

        $cloned = clone $original;

        // Add content to cloned builder and verify it works
        $message = $cloned->withText('Cloned content')->get();
        $this->assertEquals('Cloned content', $message->getParts()[0]->getText());
    }

    /**
     * Tests that modifications to cloned builder don't affect original.
     *
     * @return void
     */
    public function testClonedBuilderIsIndependent(): void
    {
        $original = new MessageBuilder();
        $original->withText('Original text')->usingUserRole();

        $cloned = clone $original;
        $cloned->withText('Additional cloned text');

        // Original should still only have one part
        $originalMessage = $original->get();
        $this->assertCount(1, $originalMessage->getParts());
        $this->assertEquals('Original text', $originalMessage->getParts()[0]->getText());
    }
}

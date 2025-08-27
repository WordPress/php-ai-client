<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Utils;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\RequiredOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Utils\RequirementsUtil;

/**
 * @covers \WordPress\AiClient\Utils\RequirementsUtil
 */
class RequirementsUtilTest extends TestCase
{
    /**
     * Tests basic requirements creation with just a capability.
     */
    public function testBasicCreatesRequirementsWithSingleCapability(): void
    {
        $requirements = RequirementsUtil::basic(CapabilityEnum::textGeneration());

        $capabilities = $requirements->getRequiredCapabilities();
        $this->assertCount(1, $capabilities);
        $this->assertTrue($capabilities[0]->equals(CapabilityEnum::textGeneration()));
        $this->assertEmpty($requirements->getRequiredOptions());
    }

    /**
     * Tests basic requirements with model configuration.
     */
    public function testBasicIncludesModelConfigRequirements(): void
    {
        $modelConfig = new ModelConfig();
        $modelConfig->setMaxTokens(100);

        $requirements = RequirementsUtil::basic(CapabilityEnum::textGeneration(), $modelConfig);

        $this->assertCount(1, $requirements->getRequiredCapabilities());
        $this->assertNotEmpty($requirements->getRequiredOptions());

        // Should include max tokens from config
        $hasMaxTokens = false;
        foreach ($requirements->getRequiredOptions() as $option) {
            if ($option->getName()->equals(OptionEnum::maxTokens())) {
                $hasMaxTokens = true;
                $this->assertEquals(100, $option->getValue());
                break;
            }
        }
        $this->assertTrue($hasMaxTokens, 'Should include maxTokens requirement from config');
    }

    /**
     * Tests requirements creation from single message.
     */
    public function testFromMessagesWithSingleTextMessage(): void
    {
        $message = new UserMessage([new MessagePart('Hello, world!')]);
        $messages = [$message];

        $requirements = RequirementsUtil::fromMessages($messages, CapabilityEnum::textGeneration());

        $capabilities = $requirements->getRequiredCapabilities();
        $this->assertCount(1, $capabilities);
        $this->assertTrue($capabilities[0]->equals(CapabilityEnum::textGeneration()));

        // Should include text input modality
        $inputModalitiesOption = $this->findRequiredOption($requirements, OptionEnum::inputModalities());
        $this->assertNotNull($inputModalitiesOption);
        $this->assertContains(ModalityEnum::text(), $inputModalitiesOption->getValue());
    }

    /**
     * Tests requirements with multiple messages (chat history).
     */
    public function testFromMessagesWithMultipleMessagesAddsChatHistory(): void
    {
        $messages = [
            new UserMessage([new MessagePart('First message')]),
            new Message(MessageRoleEnum::model(), [new MessagePart('Response')]),
            new UserMessage([new MessagePart('Second message')]),
        ];

        $requirements = RequirementsUtil::fromMessages($messages, CapabilityEnum::textGeneration());

        $capabilities = $requirements->getRequiredCapabilities();
        $this->assertCount(2, $capabilities);

        $hasTextGeneration = false;
        $hasChatHistory = false;
        foreach ($capabilities as $capability) {
            if ($capability->equals(CapabilityEnum::textGeneration())) {
                $hasTextGeneration = true;
            } elseif ($capability->equals(CapabilityEnum::chatHistory())) {
                $hasChatHistory = true;
            }
        }

        $this->assertTrue($hasTextGeneration, 'Should include primary capability');
        $this->assertTrue($hasChatHistory, 'Should include chat history capability');
    }

    /**
     * Tests message analysis functionality.
     */
    public function testAnalyzeMessagesWithTextOnly(): void
    {
        $messages = [new UserMessage([new MessagePart('Hello')])];

        $analysis = RequirementsUtil::analyzeMessages($messages);

        $this->assertFalse($analysis['requiresChatHistory']);
        $this->assertFalse($analysis['requiresFunctionCalling']);
        $this->assertTrue($analysis['hasTextInput']);
        $this->assertFalse($analysis['hasFileInput']);
        $this->assertContains(ModalityEnum::text(), $analysis['inputModalities']);
    }

    /**
     * Tests message analysis with multiple messages.
     */
    public function testAnalyzeMessagesWithMultipleMessages(): void
    {
        $messages = [
            new UserMessage([new MessagePart('First')]),
            new UserMessage([new MessagePart('Second')]),
        ];

        $analysis = RequirementsUtil::analyzeMessages($messages);

        $this->assertTrue($analysis['requiresChatHistory']);
        $this->assertTrue($analysis['hasTextInput']);
    }

    /**
     * Tests multi-modal requirements creation.
     */
    public function testMultiModalCreatesCorrectRequirements(): void
    {
        $inputModalities = [ModalityEnum::text(), ModalityEnum::image()];
        $outputModalities = [ModalityEnum::text()];

        $requirements = RequirementsUtil::multiModal(
            CapabilityEnum::textGeneration(),
            $inputModalities,
            $outputModalities
        );

        // Should have primary capability
        $capabilities = $requirements->getRequiredCapabilities();
        $this->assertCount(1, $capabilities);
        $this->assertTrue($capabilities[0]->equals(CapabilityEnum::textGeneration()));

        // Should include input modalities
        $inputOption = $this->findRequiredOption($requirements, OptionEnum::inputModalities());
        $this->assertNotNull($inputOption);
        $this->assertEquals($inputModalities, $inputOption->getValue());

        // Should include output modalities
        $outputOption = $this->findRequiredOption($requirements, OptionEnum::outputModalities());
        $this->assertNotNull($outputOption);
        $this->assertEquals($outputModalities, $outputOption->getValue());
    }


    /**
     * Tests merging required options.
     */
    public function testMergeRequiredOptionsReplacesExisting(): void
    {
        $existing = [
            new RequiredOption(OptionEnum::maxTokens(), 50),
            new RequiredOption(OptionEnum::temperature(), 0.5),
        ];

        $new = [
            new RequiredOption(OptionEnum::maxTokens(), 100), // Should replace existing
            new RequiredOption(OptionEnum::topP(), 0.9), // Should be added
        ];

        $merged = RequirementsUtil::mergeRequiredOptions($existing, $new);

        $this->assertCount(3, $merged);

        // Check maxTokens was replaced
        $maxTokensOption = $this->findRequiredOptionInArray($merged, OptionEnum::maxTokens());
        $this->assertNotNull($maxTokensOption);
        $this->assertEquals(100, $maxTokensOption->getValue());

        // Check temperature was preserved
        $tempOption = $this->findRequiredOptionInArray($merged, OptionEnum::temperature());
        $this->assertNotNull($tempOption);
        $this->assertEquals(0.5, $tempOption->getValue());

        // Check topP was added
        $topPOption = $this->findRequiredOptionInArray($merged, OptionEnum::topP());
        $this->assertNotNull($topPOption);
        $this->assertEquals(0.9, $topPOption->getValue());
    }

    /**
     * Tests empty message array handling.
     */
    public function testFromMessagesWithEmptyArray(): void
    {
        $requirements = RequirementsUtil::fromMessages([], CapabilityEnum::textGeneration());

        $capabilities = $requirements->getRequiredCapabilities();
        $this->assertCount(1, $capabilities);
        $this->assertTrue($capabilities[0]->equals(CapabilityEnum::textGeneration()));

        // Should have minimal requirements
        $this->assertEmpty($requirements->getRequiredOptions());
    }

    /**
     * Tests multi-modal with empty modalities.
     */
    public function testMultiModalWithEmptyModalities(): void
    {
        $requirements = RequirementsUtil::multiModal(CapabilityEnum::textGeneration(), []);

        $capabilities = $requirements->getRequiredCapabilities();
        $this->assertCount(1, $capabilities);
        $this->assertTrue($capabilities[0]->equals(CapabilityEnum::textGeneration()));

        // Should not have modality requirements for empty arrays
        $inputOption = $this->findRequiredOption($requirements, OptionEnum::inputModalities());
        $outputOption = $this->findRequiredOption($requirements, OptionEnum::outputModalities());

        $this->assertNull($inputOption, 'Should not add input modalities option for empty array');
        $this->assertNull($outputOption, 'Should not add output modalities option when not specified');
    }

    /**
     * Helper method to find a required option by option enum.
     *
     * @param ModelRequirements $requirements The requirements to search.
     * @param OptionEnum $targetOption The option to find.
     * @return RequiredOption|null The found option or null.
     */
    private function findRequiredOption($requirements, OptionEnum $targetOption): ?RequiredOption
    {
        return $this->findRequiredOptionInArray($requirements->getRequiredOptions(), $targetOption);
    }

    /**
     * Helper method to find a required option in an array.
     *
     * @param list<RequiredOption> $options The options array to search.
     * @param OptionEnum $targetOption The option to find.
     * @return RequiredOption|null The found option or null.
     */
    private function findRequiredOptionInArray(array $options, OptionEnum $targetOption): ?RequiredOption
    {
        foreach ($options as $option) {
            if ($option->getName()->equals($targetOption)) {
                return $option;
            }
        }
        return null;
    }
}

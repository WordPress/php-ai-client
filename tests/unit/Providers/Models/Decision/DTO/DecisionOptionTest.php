<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\Decision\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\Models\Decision\DTO\DecisionOption;

/**
 * @covers \WordPress\AiClient\Providers\Models\Decision\DTO\DecisionOption
 */
class DecisionOptionTest extends TestCase
{
    /**
     * Tests creating an option with a description.
     *
     * @return void
     */
    public function testCreateWithDescription(): void
    {
        $option = new DecisionOption('approve', 'The comment is fine to publish.');

        $this->assertSame('approve', $option->getValue());
        $this->assertSame('The comment is fine to publish.', $option->getDescription());
        $this->assertSame(
            [
                DecisionOption::KEY_VALUE => 'approve',
                DecisionOption::KEY_DESCRIPTION => 'The comment is fine to publish.',
            ],
            $option->toArray()
        );
    }

    /**
     * Tests creating an option without a description.
     *
     * @return void
     */
    public function testCreateWithoutDescription(): void
    {
        $option = new DecisionOption('5');

        $this->assertSame('5', $option->getValue());
        $this->assertNull($option->getDescription());
        $this->assertSame([DecisionOption::KEY_VALUE => '5'], $option->toArray());
    }

    /**
     * Tests array round trip, including through JSON.
     *
     * @dataProvider provideOptions
     *
     * @param DecisionOption $option The option.
     * @return void
     */
    public function testArrayRoundTrip(DecisionOption $option): void
    {
        $this->assertEquals($option, DecisionOption::fromArray($option->toArray()));

        $decoded = json_decode((string) json_encode($option->toArray()), true);
        $this->assertEquals($option, DecisionOption::fromArray($decoded));
    }

    /**
     * Provides options with and without a description.
     *
     * @return array<string, array{DecisionOption}>
     */
    public function provideOptions(): array
    {
        return [
            'with description' => [new DecisionOption('approve', 'Publish it.')],
            'without description' => [new DecisionOption('hold')],
            'integer-like value' => [new DecisionOption('1', 'One star.')],
        ];
    }

    /**
     * Tests that empty values are rejected.
     *
     * @return void
     */
    public function testRejectsEmptyValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision option values cannot be empty.');

        new DecisionOption(' ');
    }

    /**
     * Tests that empty descriptions are rejected.
     *
     * @return void
     */
    public function testRejectsEmptyDescription(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decision option descriptions cannot be empty. Omit the description instead.');

        new DecisionOption('approve', '');
    }

    /**
     * Tests the JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = DecisionOption::getJsonSchema();

        $this->assertSame('string', $schema['properties'][DecisionOption::KEY_VALUE]['type']);
        $this->assertSame('string', $schema['properties'][DecisionOption::KEY_DESCRIPTION]['type']);
        $this->assertSame([DecisionOption::KEY_VALUE], $schema['required']);
    }
}

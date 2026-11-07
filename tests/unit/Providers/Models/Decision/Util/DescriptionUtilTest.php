<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Models\Decision\Util;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\Models\Decision\Util\DescriptionUtil;

/**
 * @covers \WordPress\AiClient\Providers\Models\Decision\Util\DescriptionUtil
 */
class DescriptionUtilTest extends TestCase
{
    /**
     * Tests that valid descriptions are returned unchanged.
     *
     * @dataProvider provideValidDescriptions
     *
     * @param string|array<mixed> $description The description.
     * @return void
     */
    public function testAcceptsValidDescriptions($description): void
    {
        $this->assertSame($description, DescriptionUtil::validate($description, 'The description'));
    }

    /**
     * Provides valid descriptions.
     *
     * @return array<string, array{string|array<mixed>}>
     */
    public function provideValidDescriptions(): array
    {
        return [
            'sentence' => ['Payments and refunds.'],
            'object' => [['what' => 'Payments and refunds.', 'not_for' => 'Questions about prices.']],
            'list' => [['Payments.', 'Refunds.']],
            'nested JSON values' => [['examples' => ['Refund me', 2, 1.5, true, null, []]]],
        ];
    }

    /**
     * Tests that invalid descriptions are rejected.
     *
     * @dataProvider provideInvalidDescriptions
     *
     * @param mixed $description The invalid description.
     * @return void
     */
    public function testRejectsInvalidDescriptions($description): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The description must be a non-empty string, or a non-empty array of strings, numbers, booleans, '
            . 'null, and arrays.'
        );

        DescriptionUtil::validate($description, 'The description');
    }

    /**
     * Provides invalid descriptions.
     *
     * @return array<string, array{mixed}>
     */
    public function provideInvalidDescriptions(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'whitespace' => ['  '],
            'number' => [3],
            'empty array' => [[]],
            'object in array' => [['what' => new \stdClass()]],
            'infinite number in array' => [['weight' => INF]],
        ];
    }
}

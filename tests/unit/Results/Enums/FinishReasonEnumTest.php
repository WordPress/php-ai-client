<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tests\unit\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Results\Enums\FinishReasonEnum
 */
class FinishReasonEnumTest extends TestCase
{
    use EnumTestTrait;

    protected function getEnumClass(): string
    {
        return FinishReasonEnum::class;
    }

    protected function getExpectedValues(): array
    {
        return [
            'STOP' => 'stop',
            'LENGTH' => 'length',
            'CONTENT_FILTER' => 'content_filter',
            'TOOL_CALLS' => 'tool_calls',
            'ERROR' => 'error',
        ];
    }

    public function testSpecificEnumMethods(): void
    {
        $stop = FinishReasonEnum::stop();
        $this->assertTrue($stop->isStop());
        $this->assertFalse($stop->isLength());

        $contentFilter = FinishReasonEnum::contentFilter();
        $this->assertTrue($contentFilter->isContentFilter());
        $this->assertFalse($contentFilter->isToolCalls());

        $error = FinishReasonEnum::error();
        $this->assertTrue($error->isError());
        $this->assertFalse($error->isStop());
    }
}

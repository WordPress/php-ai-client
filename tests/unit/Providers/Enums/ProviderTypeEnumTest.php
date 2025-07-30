<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Enums;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Tests\traits\EnumTestTrait;

/**
 * @covers \WordPress\AiClient\Providers\Enums\ProviderTypeEnum
 */
class ProviderTypeEnumTest extends TestCase
{
    use EnumTestTrait;

    /**
     * Gets the enum class to test.
     *
     * @return string
     */
    protected function getEnumClass(): string
    {
        return ProviderTypeEnum::class;
    }

    /**
     * Gets the expected enum values.
     *
     * @return array
     */
    protected function getExpectedValues(): array
    {
        return [
            'CLOUD' => 'cloud',
            'SERVER' => 'server',
            'CLIENT' => 'client',
        ];
    }

    /**
     * Tests the specific enum methods.
     *
     * @return void
     */
    public function testSpecificEnumMethods(): void
    {
        $cloud = ProviderTypeEnum::cloud();
        $this->assertTrue($cloud->isCloud());
        $this->assertFalse($cloud->isServer());
        $this->assertFalse($cloud->isClient());

        $server = ProviderTypeEnum::server();
        $this->assertFalse($server->isCloud());
        $this->assertTrue($server->isServer());
        $this->assertFalse($server->isClient());

        $client = ProviderTypeEnum::client();
        $this->assertFalse($client->isCloud());
        $this->assertFalse($client->isServer());
        $this->assertTrue($client->isClient());
    }
}

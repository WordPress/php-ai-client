<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\DTO\ProviderData;

/**
 * @covers \WordPress\AiClient\Messages\DTO\ProviderData
 */
class ProviderDataTest extends TestCase
{
    /**
     * Tests provider data access and array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $data = [
            'type' => 'tool_search_call',
            'id' => 'search_123',
            'arguments' => ['query' => 'calendar'],
        ];
        $providerData = new ProviderData('openai', $data);

        $array = $providerData->toArray();
        $restored = ProviderData::fromArray($array);

        $this->assertEquals('openai', $restored->getProviderId());
        $this->assertEquals($data, $restored->getData());
        $this->assertEquals($array, $restored->toArray());
    }

    /**
     * Tests the JSON schema describes provider-scoped opaque data.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = ProviderData::getJsonSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertEquals(
            [ProviderData::KEY_PROVIDER_ID, ProviderData::KEY_DATA],
            $schema['required']
        );
        $this->assertTrue($schema['properties'][ProviderData::KEY_DATA]['additionalProperties']);
        $this->assertFalse($schema['additionalProperties']);
    }
}

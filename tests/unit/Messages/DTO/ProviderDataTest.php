<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Messages\DTO\ProviderData;

/**
 * @covers \WordPress\AiClient\Messages\DTO\ProviderData
 */
class ProviderDataTest extends TestCase
{
    /**
     * Tests provider-native fields survive array and JSON transformations.
     *
     * @dataProvider nativeDataProvider
     * @param string $providerId The originating provider identifier.
     * @param array<string, mixed> $data The provider-native payload.
     * @return void
     */
    public function testRoundTrip(string $providerId, array $data): void
    {
        $providerData = new ProviderData($providerId, $data);
        $array = ['providerId' => $providerId, 'data' => $data];

        $this->assertSame($providerId, $providerData->getProviderId());
        $this->assertSame($data, $providerData->getData());
        $this->assertSame($array, $providerData->toArray());
        $this->assertSame($array, ProviderData::fromArray($array)->toArray());

        $decoded = json_decode(json_encode($providerData, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($array, ProviderData::fromArray($decoded)->toArray());
    }

    /**
     * Provides native items without requiring provider implementations.
     *
     * @return array<string, array{string, array<string, mixed>}> The payloads.
     */
    public function nativeDataProvider(): array
    {
        return [
            'tool discovery' => ['openai', [
                'type' => 'tool_search_output',
                'id' => 'search_123',
                'tools' => [['type' => 'function', 'name' => 'calendar', 'defer_loading' => false]],
                'unknown_field' => null,
            ]],
            'server tool call' => ['google', [
                'thoughtSignature' => 'signature_fixture',
                'toolCall' => ['toolType' => 'GOOGLE_SEARCH_WEB', 'args' => ['queries' => ['WordPress']]],
            ]],
            'server tool response' => ['google', [
                'toolResponse' => [
                    'toolType' => 'GOOGLE_SEARCH_WEB',
                    'response' => ['search_suggestions' => '<div>Search suggestions</div>'],
                ],
            ]],
            'empty data' => ['custom-provider', []],
        ];
    }

    /**
     * Tests missing required fields use the SDK exception contract.
     *
     * @dataProvider missingFieldsProvider
     * @param array<string, mixed> $array The incomplete data.
     * @return void
     */
    public function testMissingFields(array $array): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing required keys:');

        ProviderData::fromArray($array);
    }

    /**
     * Provides arrays with missing required fields.
     *
     * @return array<string, array{array<string, mixed>}> The incomplete arrays.
     */
    public function missingFieldsProvider(): array
    {
        return [
            'provider identifier' => [['data' => []]],
            'data' => [['providerId' => 'openai']],
            'both' => [[]],
        ];
    }

    /**
     * Tests returned arrays and clones do not share mutable array data.
     *
     * @return void
     */
    public function testArrayIndependence(): void
    {
        $data = ['nested' => ['value' => 'original']];
        $original = new ProviderData('custom-provider', $data);
        $cloned = clone $original;
        $data['nested']['value'] = 'input changed';
        $retrieved = $cloned->getData();
        $retrieved['nested']['value'] = 'getter changed';
        $array = $original->toArray();
        $array['data']['nested']['value'] = 'array changed';

        $this->assertNotSame($original, $cloned);
        $this->assertSame(['nested' => ['value' => 'original']], $original->getData());
        $this->assertSame($original->getData(), $cloned->getData());
    }

    /**
     * Tests the schema describes provider-scoped opaque object data.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = ProviderData::getJsonSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertSame([ProviderData::KEY_PROVIDER_ID, ProviderData::KEY_DATA], $schema['required']);
        $this->assertSame('string', $schema['properties'][ProviderData::KEY_PROVIDER_ID]['type']);
        $this->assertSame('object', $schema['properties'][ProviderData::KEY_DATA]['type']);
        $this->assertTrue($schema['properties'][ProviderData::KEY_DATA]['additionalProperties']);
        $this->assertFalse($schema['additionalProperties']);
        $this->assertSame('{"providerId":"custom-provider","data":{}}', json_encode(
            new ProviderData('custom-provider', [])
        ));
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\DTO;

use JsonSerializable;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Contracts\WithArrayTransformationInterface;
use WordPress\AiClient\Common\Contracts\WithJsonSchemaInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;

/**
 * @covers \WordPress\AiClient\Providers\DTO\ProviderMetadata
 */
class ProviderMetadataTest extends TestCase
{
    /**
     * Tests constructor and getter methods with defaults.
     *
     * @return void
     */
    public function testConstructorAndGetters(): void
    {
        $metadata = new ProviderMetadata('openai', 'OpenAI');

        $this->assertEquals('openai', $metadata->getId());
        $this->assertEquals('OpenAI', $metadata->getName());
        $this->assertNull($metadata->getDescription());
        $this->assertTrue($metadata->getType()->isCloud());
        $this->assertNull($metadata->getCredentialsUrl());
        $this->assertNull($metadata->getAuthenticationMethod());
    }

    /**
     * Tests constructor with all args.
     *
     * @return void
     */
    public function testConstructorWithAllArgs(): void
    {
        $metadata = new ProviderMetadata('openai', 'OpenAI', [
            'description' => 'OpenAI is a leading AI research lab.',
            'type' => ProviderTypeEnum::server(),
            'credentialsUrl' => 'https://platform.openai.com/account/api-keys',
        ]);

        $this->assertEquals('openai', $metadata->getId());
        $this->assertEquals('OpenAI', $metadata->getName());
        $this->assertEquals('OpenAI is a leading AI research lab.', $metadata->getDescription());
        $this->assertTrue($metadata->getType()->isServer());
        $this->assertEquals('https://platform.openai.com/account/api-keys', $metadata->getCredentialsUrl());
    }

    /**
     * Tests backwards-compatible constructor with positional arguments.
     *
     * @return void
     */
    public function testConstructorBackwardsCompatibility(): void
    {
        $type = ProviderTypeEnum::server();
        $metadata = new ProviderMetadata('openai', 'OpenAI', $type, 'https://example.com/keys');

        $this->assertEquals('openai', $metadata->getId());
        $this->assertEquals('OpenAI', $metadata->getName());
        $this->assertSame($type, $metadata->getType());
        $this->assertEquals('https://example.com/keys', $metadata->getCredentialsUrl());
    }

    /**
     * Tests different provider types.
     *
     * @return void
     */
    public function testDifferentProviderTypes(): void
    {
        // Test cloud provider (default)
        $cloudProvider = new ProviderMetadata('google', 'Google AI');
        $this->assertTrue($cloudProvider->getType()->isCloud());
        $this->assertFalse($cloudProvider->getType()->isServer());
        $this->assertFalse($cloudProvider->getType()->isClient());

        // Test server provider
        $serverProvider = new ProviderMetadata('llama', 'LLaMA', ['type' => ProviderTypeEnum::server()]);
        $this->assertFalse($serverProvider->getType()->isCloud());
        $this->assertTrue($serverProvider->getType()->isServer());
        $this->assertFalse($serverProvider->getType()->isClient());

        // Test client provider
        $clientProvider = new ProviderMetadata('browser-ai', 'Browser AI', ['type' => ProviderTypeEnum::client()]);
        $this->assertFalse($clientProvider->getType()->isCloud());
        $this->assertFalse($clientProvider->getType()->isServer());
        $this->assertTrue($clientProvider->getType()->isClient());
    }

    /**
     * Tests JSON schema generation.
     *
     * @return void
     */
    public function testGetJsonSchema(): void
    {
        $schema = ProviderMetadata::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);

        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey(ProviderMetadata::KEY_ID, $schema['properties']);
        $this->assertArrayHasKey(ProviderMetadata::KEY_NAME, $schema['properties']);
        $this->assertArrayHasKey(ProviderMetadata::KEY_DESCRIPTION, $schema['properties']);
        $this->assertArrayHasKey(ProviderMetadata::KEY_TYPE, $schema['properties']);
        $this->assertArrayHasKey(ProviderMetadata::KEY_CREDENTIALS_URL, $schema['properties']);

        // Check property types
        $this->assertEquals('string', $schema['properties'][ProviderMetadata::KEY_ID]['type']);
        $this->assertEquals('string', $schema['properties'][ProviderMetadata::KEY_NAME]['type']);
        $this->assertEquals('string', $schema['properties'][ProviderMetadata::KEY_DESCRIPTION]['type']);
        $this->assertEquals('string', $schema['properties'][ProviderMetadata::KEY_TYPE]['type']);
        $this->assertEquals('string', $schema['properties'][ProviderMetadata::KEY_CREDENTIALS_URL]['type']);

        // Check enum values for type
        $this->assertArrayHasKey('enum', $schema['properties'][ProviderMetadata::KEY_TYPE]);
        $this->assertEquals(ProviderTypeEnum::getValues(), $schema['properties'][ProviderMetadata::KEY_TYPE]['enum']);

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertEquals(
            [ProviderMetadata::KEY_ID, ProviderMetadata::KEY_NAME],
            $schema['required']
        );
    }

    /**
     * Tests array conversion.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $metadata = new ProviderMetadata('anthropic', 'Anthropic');
        $array = $metadata->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('anthropic', $array[ProviderMetadata::KEY_ID]);
        $this->assertEquals('Anthropic', $array[ProviderMetadata::KEY_NAME]);
        $this->assertNull($array[ProviderMetadata::KEY_DESCRIPTION]);
        $this->assertEquals('cloud', $array[ProviderMetadata::KEY_TYPE]);
        $this->assertNull($array[ProviderMetadata::KEY_CREDENTIALS_URL]);
        $this->assertNull($array[ProviderMetadata::KEY_AUTHENTICATION_METHOD]);
        $this->assertCount(6, $array);
    }

    /**
     * Tests creating from array.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $data = [
            ProviderMetadata::KEY_ID => 'custom-provider',
            ProviderMetadata::KEY_NAME => 'Custom Provider',
            ProviderMetadata::KEY_TYPE => 'server',
            ProviderMetadata::KEY_CREDENTIALS_URL => 'https://example.com/credentials',
        ];

        $metadata = ProviderMetadata::fromArray($data);

        $this->assertInstanceOf(ProviderMetadata::class, $metadata);
        $this->assertEquals('custom-provider', $metadata->getId());
        $this->assertEquals('Custom Provider', $metadata->getName());
        $this->assertTrue($metadata->getType()->isServer());
        $this->assertEquals('https://example.com/credentials', $metadata->getCredentialsUrl());
    }

    /**
     * Tests round-trip array transformation.
     *
     * @return void
     */
    public function testArrayRoundTrip(): void
    {
        $original = new ProviderMetadata('test-provider', 'Test Provider', ['type' => ProviderTypeEnum::client()]);
        $array = $original->toArray();
        $restored = ProviderMetadata::fromArray($array);

        $this->assertEquals($original->getId(), $restored->getId());
        $this->assertEquals($original->getName(), $restored->getName());
        $this->assertEquals($original->getType()->value, $restored->getType()->value);
    }

    /**
     * Tests JSON serialization.
     *
     * @return void
     */
    public function testJsonSerialize(): void
    {
        $metadata = new ProviderMetadata('json-provider', 'JSON Provider');
        $json = json_encode($metadata);
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertEquals('json-provider', $decoded[ProviderMetadata::KEY_ID]);
        $this->assertEquals('JSON Provider', $decoded[ProviderMetadata::KEY_NAME]);
        $this->assertEquals('cloud', $decoded[ProviderMetadata::KEY_TYPE]);
    }

    /**
     * Tests with special characters in names.
     *
     * @return void
     */
    public function testSpecialCharactersInNames(): void
    {
        $metadata = new ProviderMetadata(
            'special-chars',
            'Provider with "quotes" & special <chars>'
        );

        $array = $metadata->toArray();
        $this->assertEquals('Provider with "quotes" & special <chars>', $array[ProviderMetadata::KEY_NAME]);

        $restored = ProviderMetadata::fromArray($array);
        $this->assertEquals($metadata->getName(), $restored->getName());
    }

    /**
     * Tests with empty strings.
     *
     * @return void
     */
    public function testEmptyStrings(): void
    {
        $metadata = new ProviderMetadata('', '');

        $this->assertEquals('', $metadata->getId());
        $this->assertEquals('', $metadata->getName());

        $array = $metadata->toArray();
        $this->assertEquals('', $array[ProviderMetadata::KEY_ID]);
        $this->assertEquals('', $array[ProviderMetadata::KEY_NAME]);
    }

    /**
     * Tests implements correct interfaces.
     *
     * @return void
     */
    public function testImplementsCorrectInterfaces(): void
    {
        $metadata = new ProviderMetadata('test', 'Test');

        $this->assertInstanceOf(
            WithArrayTransformationInterface::class,
            $metadata
        );
        $this->assertInstanceOf(
            WithJsonSchemaInterface::class,
            $metadata
        );
        $this->assertInstanceOf(
            JsonSerializable::class,
            $metadata
        );
    }
}

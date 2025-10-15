<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\DTO;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;

/**
 * @covers \WordPress\AiClient\Providers\Http\DTO\RequestOptions
 */
class RequestOptionsTest extends TestCase
{
    /**
     * Tests that all options are nullable by default.
     *
     * @return void
     */
    public function testConstructorDefaultsToNullValues(): void
    {
        $options = new RequestOptions();

        $this->assertNull($options->getTimeout());
        $this->assertNull($options->getConnectTimeout());
        $this->assertNull($options->allowsRedirects());
        $this->assertNull($options->getMaxRedirects());
        $this->assertSame([], $options->toArray());
    }

    /**
     * Tests immutable helpers modify the cloned instance only.
     *
     * @return void
     */
    public function testWithTimeoutReturnsUpdatedClone(): void
    {
        $options = new RequestOptions();
        $updated = $options->withTimeout(5.0);

        $this->assertNotSame($options, $updated);
        $this->assertNull($options->getTimeout());
        $this->assertSame(5.0, $updated->getTimeout());
    }

    /**
     * Tests enabling redirects with a limit.
     *
     * @return void
     */
    public function testWithRedirectsSetsFlagsAndLimit(): void
    {
        $options = new RequestOptions();
        $updated = $options->withRedirects(3);

        $this->assertTrue($updated->allowsRedirects());
        $this->assertSame(3, $updated->getMaxRedirects());
    }

    /**
     * Tests disabling redirects clears the maximum.
     *
     * @return void
     */
    public function testWithoutRedirectsClearsRedirectLimit(): void
    {
        $options = (new RequestOptions())->withRedirects(4);
        $disabled = $options->withoutRedirects();

        $this->assertFalse($disabled->allowsRedirects());
        $this->assertNull($disabled->getMaxRedirects());
    }

    /**
     * Tests validation when attempting to set a redirect limit while redirects are not enabled.
     *
     * @return void
     */
    public function testWithMaxRedirectsThrowsWhenRedirectsDisabled(): void
    {
        $options = new RequestOptions();

        $this->expectException(InvalidArgumentException::class);
        $options->withMaxRedirects(2);
    }

    /**
     * Tests that the JSON schema reflects nullable redirect flag.
     *
     * @return void
     */
    public function testGetJsonSchemaDefinesNullableRedirectFlag(): void
    {
        $schema = RequestOptions::getJsonSchema();

        $this->assertSame(['boolean', 'null'], $schema['properties'][RequestOptions::KEY_ALLOW_REDIRECTS]['type']);
        $this->assertSame(['integer', 'null'], $schema['properties'][RequestOptions::KEY_MAX_REDIRECTS]['type']);
    }
}

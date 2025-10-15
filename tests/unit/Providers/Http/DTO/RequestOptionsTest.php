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
    public function testDefaultsToNullValues(): void
    {
        $options = new RequestOptions();

        $this->assertNull($options->getTimeout());
        $this->assertNull($options->getConnectTimeout());
        $this->assertNull($options->allowsRedirects());
        $this->assertNull($options->getMaxRedirects());
        $this->assertSame([], $options->toArray());
    }

    /**
     * Tests mutable setters modify the same instance.
     *
     * @return void
     */
    public function testSetTimeoutModifiesInstance(): void
    {
        $options = new RequestOptions();
        $options->setTimeout(5.0);

        $this->assertSame(5.0, $options->getTimeout());
    }

    /**
     * Tests enabling redirects with a limit.
     *
     * @return void
     */
    public function testSetAllowRedirectsEnablesRedirects(): void
    {
        $options = new RequestOptions();
        $options->setAllowRedirects(true);
        $options->setMaxRedirects(3);

        $this->assertTrue($options->allowsRedirects());
        $this->assertSame(3, $options->getMaxRedirects());
    }

    /**
     * Tests disabling redirects clears the maximum.
     *
     * @return void
     */
    public function testSetAllowRedirectsFalseClearsRedirectLimit(): void
    {
        $options = new RequestOptions();
        $options->setAllowRedirects(true);
        $options->setMaxRedirects(4);
        $options->setAllowRedirects(false);

        $this->assertFalse($options->allowsRedirects());
        $this->assertNull($options->getMaxRedirects());
    }

    /**
     * Tests validation when attempting to set a redirect limit while redirects are not enabled.
     *
     * @return void
     */
    public function testSetMaxRedirectsThrowsWhenRedirectsDisabled(): void
    {
        $options = new RequestOptions();

        $this->expectException(InvalidArgumentException::class);
        $options->setMaxRedirects(2);
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

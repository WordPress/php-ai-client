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
    public function testSetMaxRedirectsEnablesRedirects(): void
    {
        $options = new RequestOptions();
        $options->setMaxRedirects(3);

        $this->assertTrue($options->allowsRedirects());
        $this->assertSame(3, $options->getMaxRedirects());
    }

    /**
     * Tests disabling redirects by setting maxRedirects to 0.
     *
     * @return void
     */
    public function testSetMaxRedirectsToZeroDisablesRedirects(): void
    {
        $options = new RequestOptions();
        $options->setMaxRedirects(0);

        $this->assertFalse($options->allowsRedirects());
        $this->assertSame(0, $options->getMaxRedirects());
    }

    /**
     * Tests validation when attempting to set a negative redirect limit.
     *
     * @return void
     */
    public function testSetMaxRedirectsThrowsWhenNegative(): void
    {
        $options = new RequestOptions();

        $this->expectException(InvalidArgumentException::class);
        $options->setMaxRedirects(-1);
    }

    /**
     * Tests that the JSON schema reflects nullable maxRedirects.
     *
     * @return void
     */
    public function testGetJsonSchemaDefinesNullableMaxRedirects(): void
    {
        $schema = RequestOptions::getJsonSchema();

        $this->assertSame(['integer', 'null'], $schema['properties'][RequestOptions::KEY_MAX_REDIRECTS]['type']);
    }
}

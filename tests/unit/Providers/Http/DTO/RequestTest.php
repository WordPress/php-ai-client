<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\DTO;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;

/**
 * @covers \WordPress\AiClient\Providers\Http\DTO\Request
 */
class RequestTest extends TestCase
{
    /**
     * Tests that request options default to null and are omitted from serialization.
     *
     * @return void
     */
    public function testOptionsDefaultToNull(): void
    {
        $request = new Request(HttpMethodEnum::get(), 'https://example.com');

        $this->assertNull($request->getOptions());

        $array = $request->toArray();
        $this->assertArrayNotHasKey(Request::KEY_OPTIONS, $array);
    }

    /**
     * Tests the withOptions helper stores the provided options immutably.
     *
     * @return void
     */
    public function testWithOptionsStoresProvidedOptions(): void
    {
        $request = new Request(HttpMethodEnum::post(), 'https://example.com');
        $options = (new RequestOptions())->withTimeout(1.5);

        $updated = $request->withOptions($options);

        $this->assertNotSame($request, $updated);
        $this->assertSame($options, $updated->getOptions());
        $this->assertNull($request->getOptions());
    }

    /**
     * Tests that convenience setters lazily instantiate request options.
     *
     * @return void
     */
    public function testSettersInstantiateRequestOptions(): void
    {
        $request = new Request(HttpMethodEnum::post(), 'https://example.com');
        $this->assertNull($request->getOptions());

        $request->setTimeout(2.0);
        $request->setConnectTimeout(1.0);
        $request->setAllowRedirects(true);
        $request->setMaxRedirects(5);

        $options = $request->getOptions();
        $this->assertInstanceOf(RequestOptions::class, $options);
        $this->assertSame(2.0, $options->getTimeout());
        $this->assertSame(1.0, $options->getConnectTimeout());
        $this->assertTrue($options->allowsRedirects());
        $this->assertSame(5, $options->getMaxRedirects());

        $array = $request->toArray();
        $this->assertArrayHasKey(Request::KEY_OPTIONS, $array);
        $this->assertArrayHasKey(RequestOptions::KEY_TIMEOUT, $array[Request::KEY_OPTIONS]);
    }

    /**
     * Tests that disabling redirects clears the limit serialized in toArray.
     *
     * @return void
     */
    public function testDisablingRedirectsClearsLimit(): void
    {
        $request = new Request(HttpMethodEnum::post(), 'https://example.com');
        $request->setAllowRedirects(true);
        $request->setMaxRedirects(3);
        $request->setAllowRedirects(false);

        $options = $request->getOptions();
        $this->assertFalse($options->allowsRedirects());
        $this->assertNull($options->getMaxRedirects());
    }
}

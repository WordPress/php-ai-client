<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\Http\DTO;

use GuzzleHttp\Psr7\Request as Psr7Request;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Exception\InvalidArgumentException as AiInvalidArgumentException;
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
        $options = new RequestOptions();
        $options->setTimeout(1.5);

        $updated = $request->withOptions($options);

        $this->assertNotSame($request, $updated);
        $this->assertSame($options, $updated->getOptions());
        $this->assertNull($request->getOptions());
    }

    /**
     * Tests that GET requests with array data append data as query parameters.
     *
     * @return void
     */
    public function testGetUriAppendsQueryParametersForGetRequest(): void
    {
        $request = new Request(
            HttpMethodEnum::get(),
            'https://example.com/search',
            [],
            ['q' => 'php', 'page' => '2']
        );

        $this->assertSame('https://example.com/search?q=php&page=2', $request->getUri());
        $this->assertNull($request->getBody());
        $this->assertSame(['q' => 'php', 'page' => '2'], $request->getData());
    }

    /**
     * Tests JSON body generation when Content-Type is application/json.
     *
     * @return void
     */
    public function testGetBodyEncodesJsonData(): void
    {
        $request = new Request(
            HttpMethodEnum::post(),
            'https://example.com/resources',
            ['Content-Type' => 'application/json'],
            ['title' => 'Test', 'published' => true]
        );

        $this->assertSame('{"title":"Test","published":true}', $request->getBody());
        $this->assertSame(['title' => 'Test', 'published' => true], $request->getData());
    }

    /**
     * Tests form body generation when Content-Type is application/x-www-form-urlencoded.
     *
     * @return void
     */
    public function testGetBodyEncodesFormData(): void
    {
        $request = new Request(
            HttpMethodEnum::post(),
            'https://example.com/resources',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            ['name' => 'Example', 'value' => '123']
        );

        $this->assertSame('name=Example&value=123', $request->getBody());
    }

    /**
     * Tests string body pass-through when provided directly.
     *
     * @return void
     */
    public function testGetBodyReturnsExplicitString(): void
    {
        $request = new Request(
            HttpMethodEnum::post(),
            'https://example.com/raw',
            ['Content-Type' => 'text/plain'],
            'raw-body'
        );

        $this->assertSame('raw-body', $request->getBody());
    }

    /**
     * Tests header access methods are case-insensitive.
     *
     * @return void
     */
    public function testHeaderAccessIsCaseInsensitive(): void
    {
        $request = new Request(
            HttpMethodEnum::get(),
            'https://example.com',
            ['X-Test' => ['A', 'B']]
        );

        $this->assertTrue($request->hasHeader('x-test'));
        $this->assertSame(['A', 'B'], $request->getHeader('X-TEST'));
        $this->assertSame('A, B', $request->getHeaderAsString('x-test'));
    }

    /**
     * Tests withHeader returns cloned instance with updated header.
     *
     * @return void
     */
    public function testWithHeaderReturnsNewInstance(): void
    {
        $request = new Request(HttpMethodEnum::get(), 'https://example.com');
        $updated = $request->withHeader('X-New', 'value');

        $this->assertNotSame($request, $updated);
        $this->assertFalse($request->hasHeader('X-New'));
        $this->assertSame('value', $updated->getHeaderAsString('X-New'));
    }

    /**
     * Tests withData toggles between body and data fields.
     *
     * @return void
     */
    public function testWithDataReplacesBodyAndData(): void
    {
        $request = new Request(HttpMethodEnum::post(), 'https://example.com', [], 'initial-body');
        $requestWithArray = $request->withData(['foo' => 'bar']);

        $this->assertNotSame($request, $requestWithArray);
        $this->assertSame(['foo' => 'bar'], $requestWithArray->getData());
        $this->assertSame('foo=bar', $requestWithArray->getBody());

        $requestWithString = $requestWithArray->withData('string-body');
        $this->assertSame('string-body', $requestWithString->getBody());
        $this->assertNull($requestWithString->getData());
    }

    /**
     * Tests toArray includes headers, body, and options when present.
     *
     * @return void
     */
    public function testToArrayIncludesBodyAndOptions(): void
    {
        $options = new RequestOptions();
        $options->setTimeout(1.0);
        $options->setAllowRedirects(true);
        $options->setMaxRedirects(2);

        $request = new Request(
            HttpMethodEnum::post(),
            'https://example.com',
            ['Content-Type' => 'application/json'],
            ['key' => 'value'],
            $options
        );

        $array = $request->toArray();

        $this->assertSame(HttpMethodEnum::post()->value, $array[Request::KEY_METHOD]);
        $this->assertSame('https://example.com', $array[Request::KEY_URI]);
        $this->assertSame(['application/json'], $array[Request::KEY_HEADERS]['Content-Type']);
        $this->assertSame('{"key":"value"}', $array[Request::KEY_BODY]);
        $this->assertSame(
            ['timeout' => 1.0, 'allowRedirects' => true, 'maxRedirects' => 2],
            $array[Request::KEY_OPTIONS]
        );
    }

    /**
     * Tests fromArray creates a request instance including options when supplied.
     *
     * @return void
     */
    public function testFromArrayRestoresRequestAndOptions(): void
    {
        $array = [
            Request::KEY_METHOD => HttpMethodEnum::post()->value,
            Request::KEY_URI => 'https://example.com',
            Request::KEY_HEADERS => ['Accept' => ['application/json']],
            Request::KEY_BODY => 'payload',
            Request::KEY_OPTIONS => [
                RequestOptions::KEY_TIMEOUT => 4.0,
                RequestOptions::KEY_ALLOW_REDIRECTS => true,
                RequestOptions::KEY_MAX_REDIRECTS => 1,
            ],
        ];

        $request = Request::fromArray($array);

        $this->assertSame('payload', $request->getBody());
        $options = $request->getOptions();
        $this->assertInstanceOf(RequestOptions::class, $options);
        $this->assertSame(4.0, $options->getTimeout());
        $this->assertTrue($options->allowsRedirects());
        $this->assertSame(1, $options->getMaxRedirects());
    }

    /**
     * Tests fromArray works without options.
     *
     * @return void
     */
    public function testFromArrayWithoutOptionsLeavesOptionsNull(): void
    {
        $array = [
            Request::KEY_METHOD => HttpMethodEnum::get()->value,
            Request::KEY_URI => 'https://example.com',
            Request::KEY_HEADERS => [],
        ];

        $request = Request::fromArray($array);

        $this->assertNull($request->getOptions());
    }

    /**
     * Tests fromPsrRequest converts PSR-7 request into DTO.
     *
     * @return void
     */
    public function testFromPsrRequest(): void
    {
        $psrRequest = (new Psr7Request('POST', 'https://example.com', ['Content-Type' => 'text/plain'], 'body'))
            ->withAddedHeader('X-Test', 'value');

        $request = Request::fromPsrRequest($psrRequest);

        $this->assertSame(HttpMethodEnum::post()->value, $request->getMethod()->value);
        $this->assertSame('https://example.com', $request->getUri());
        $this->assertSame('body', $request->getBody());
        $this->assertSame(['value'], $request->getHeader('X-Test'));
    }

    /**
     * Ensures constructor throws when URI is empty.
     *
     * @return void
     */
    public function testConstructorThrowsWhenUriIsEmpty(): void
    {
        $this->expectException(AiInvalidArgumentException::class);
        new Request(HttpMethodEnum::get(), '');
    }
}

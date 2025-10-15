<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\Providers\Http;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Http\Mock\Client as MockClient;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\HttpTransporter;
use WordPress\AiClient\Tests\mocks\GuzzleLikeClient;

/**
 * Tests for HttpTransporter class.
 *
 * @covers \WordPress\AiClient\Providers\Http\HttpTransporter
 */
class HttpTransporterTest extends TestCase
{
    /**
     * @var MockClient Mock HTTP client.
     */
    private MockClient $mockClient;

    /**
     * @var HttpFactory PSR-17 factory.
     */
    private HttpFactory $httpFactory;

    /**
     * @var HttpTransporter The transporter under test.
     */
    private HttpTransporter $transporter;

    /**
     * Sets up the test fixture.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockClient();
        $this->httpFactory = new HttpFactory();
        $this->transporter = new HttpTransporter(
            $this->mockClient,
            $this->httpFactory,
            $this->httpFactory
        );
    }

    /**
     * Tests sending a simple GET request.
     *
     * @covers ::send
     * @covers ::convertToPsr7Request
     * @covers ::convertFromPsr7Response
     *
     * @return void
     */
    public function testSendGetRequest(): void
    {
        // Arrange
        $request = new Request(HttpMethodEnum::GET(), 'https://api.example.com/data');
        $mockResponse = new Psr7Response(200, ['Content-Type' => 'application/json'], '{"success":true}');
        $this->mockClient->addResponse($mockResponse);

        // Act
        $response = $this->transporter->send($request);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"success":true}', $response->getBody());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertEquals('application/json', $response->getHeaderAsString('Content-Type'));
        // Test case-insensitive header lookup
        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertEquals('application/json', $response->getHeaderAsString('CONTENT-TYPE'));
    }

    /**
     * Tests sending a POST request with body.
     *
     * @covers ::send
     * @covers ::convertToPsr7Request
     * @covers ::convertFromPsr7Response
     *
     * @return void
     */
    public function testSendPostRequestWithBody(): void
    {
        // Arrange
        $headers = ['Content-Type' => 'application/json'];
        $body = '{"name":"test"}';
        $request = new Request(HttpMethodEnum::POST(), 'https://api.example.com/create', $headers, $body);

        $mockResponse = new Psr7Response(201, ['Location' => '/resource/123'], '{"id":123}');
        $this->mockClient->addResponse($mockResponse);

        // Act
        $response = $this->transporter->send($request);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('{"id":123}', $response->getBody());
        $this->assertEquals(['/resource/123'], $response->getHeader('Location'));

        // Verify the request was sent correctly
        $sentRequests = $this->mockClient->getRequests();
        $this->assertCount(1, $sentRequests);

        $sentRequest = $sentRequests[0];
        $this->assertEquals('POST', $sentRequest->getMethod());
        $this->assertEquals('https://api.example.com/create', (string) $sentRequest->getUri());
        $this->assertEquals($body, (string) $sentRequest->getBody());
    }

    /**
     * Tests handling headers with multiple values.
     *
     * @covers ::send
     * @covers ::convertToPsr7Request
     * @covers ::convertFromPsr7Response
     *
     * @return void
     */
    public function testMultipleHeaderValues(): void
    {
        // Arrange
        $headers = [
            'Accept' => ['application/json', 'application/xml'],
            'X-Custom' => 'single-value'
        ];
        $request = new Request(HttpMethodEnum::GET(), 'https://api.example.com', $headers);

        $mockResponse = new Psr7Response(200, ['Set-Cookie' => ['cookie1=value1', 'cookie2=value2']]);
        $this->mockClient->addResponse($mockResponse);

        // Act
        $response = $this->transporter->send($request);

        // Assert
        $setCookie = $response->getHeader('Set-Cookie');
        $this->assertIsArray($setCookie);
        $this->assertCount(2, $setCookie);
        $this->assertContains('cookie1=value1', $setCookie);
        $this->assertContains('cookie2=value2', $setCookie);

        // Verify request headers
        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertEquals(['application/json', 'application/xml'], $sentRequest->getHeader('Accept'));
        $this->assertEquals(['single-value'], $sentRequest->getHeader('X-Custom'));
    }

    /**
     * Tests sending a GET request with array data as query parameters.
     *
     * @covers ::send
     * @covers ::convertToPsr7Request
     *
     * @return void
     */
    public function testSendGetRequestWithArrayData(): void
    {
        // Arrange
        $data = ['search' => 'test', 'limit' => '10'];
        $request = new Request(HttpMethodEnum::GET(), 'https://api.example.com/search', [], $data);

        $mockResponse = new Psr7Response(200, [], '[]');
        $this->mockClient->addResponse($mockResponse);

        // Act
        $response = $this->transporter->send($request);

        // Assert
        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertEquals('https://api.example.com/search?search=test&limit=10', (string) $sentRequest->getUri());
        $this->assertEmpty((string) $sentRequest->getBody());
    }

    /**
     * Tests sending a POST request with array data as JSON.
     *
     * @covers ::send
     * @covers ::convertToPsr7Request
     *
     * @return void
     */
    public function testSendPostRequestWithArrayDataAsJson(): void
    {
        // Arrange
        $headers = ['Content-Type' => 'application/json'];
        $data = ['name' => 'test', 'value' => 123];
        $request = new Request(HttpMethodEnum::POST(), 'https://api.example.com/create', $headers, $data);

        $mockResponse = new Psr7Response(201, [], '{"id":1}');
        $this->mockClient->addResponse($mockResponse);

        // Act
        $response = $this->transporter->send($request);

        // Assert
        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertEquals('{"name":"test","value":123}', (string) $sentRequest->getBody());
    }

    /**
     * Tests sending a POST request with array data as form-encoded.
     *
     * @covers ::send
     * @covers ::convertToPsr7Request
     *
     * @return void
     */
    public function testSendPostRequestWithArrayDataAsForm(): void
    {
        // Arrange
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $data = ['name' => 'test', 'value' => '123'];
        $request = new Request(HttpMethodEnum::POST(), 'https://api.example.com/create', $headers, $data);

        $mockResponse = new Psr7Response(201, [], '{"id":1}');
        $this->mockClient->addResponse($mockResponse);

        // Act
        $response = $this->transporter->send($request);

        // Assert
        $sentRequest = $this->mockClient->getRequests()[0];
        $this->assertEquals('name=test&value=123', (string) $sentRequest->getBody());
    }

    /**
     * Tests that Guzzle-like clients receive request options through the send method.
     *
     * @covers ::send
     * @covers ::buildGuzzleOptions
     * @covers ::isGuzzleClient
     *
     * @return void
     */
    public function testSendUsesGuzzleClientOptions(): void
    {
        $response = new Psr7Response(204);
        $guzzleClient = new GuzzleLikeClient($response);
        $transporter = new HttpTransporter(
            $guzzleClient,
            $this->httpFactory,
            $this->httpFactory
        );

        $options = new RequestOptions();
        $options->setTimeout(5.0);
        $options->setConnectTimeout(1.0);
        $options->setMaxRedirects(3);

        $request = new Request(
            HttpMethodEnum::GET(),
            'https://api.example.com/guzzle-test',
            [],
            null,
            $options
        );

        $result = $transporter->send($request);

        $this->assertEquals(204, $result->getStatusCode());
        $this->assertFalse($guzzleClient->wasSendRequestCalled());

        $lastOptions = $guzzleClient->getLastOptions();
        $this->assertIsArray($lastOptions);
        $this->assertSame(5.0, $lastOptions['timeout']);
        $this->assertSame(1.0, $lastOptions['connect_timeout']);
        $this->assertSame(['max' => 3], $lastOptions['allow_redirects']);
    }

    /**
     * Tests case-insensitive header access in Request.
     *
     * @return void
     */
    public function testRequestCaseInsensitiveHeaders(): void
    {
        // Arrange
        $headers = [
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'value',
        ];
        $request = new Request(HttpMethodEnum::GET(), 'https://api.example.com', $headers);

        // Assert - getHeader
        $this->assertEquals(['application/json'], $request->getHeader('Content-Type'));
        $this->assertEquals(['application/json'], $request->getHeader('content-type'));
        $this->assertEquals(['application/json'], $request->getHeader('CONTENT-TYPE'));

        // Assert - getHeaderAsString
        $this->assertEquals('application/json', $request->getHeaderAsString('Content-Type'));
        $this->assertEquals('application/json', $request->getHeaderAsString('content-type'));

        // Assert - hasHeader
        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertTrue($request->hasHeader('content-type'));
        $this->assertTrue($request->hasHeader('X-CUSTOM-HEADER'));
        $this->assertFalse($request->hasHeader('Non-Existent'));
    }

    /**
     * Tests using discovery when no dependencies provided.
     *
     * @covers ::__construct
     *
     * @return void
     */
    public function testConstructorWithDiscovery(): void
    {
        // This test verifies that the constructor can use discovery
        // In a real scenario, discovery would find installed clients
        $transporter = new HttpTransporter();

        // The transporter should be created successfully
        $this->assertInstanceOf(HttpTransporter::class, $transporter);
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\Providers\Http;

use Http\Mock\Client as MockClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\HttpTransporter;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\HttpFactory;

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
        $request = new Request('GET', 'https://api.example.com/data');
        $mockResponse = new Psr7Response(200, ['Content-Type' => 'application/json'], '{"success":true}');
        $this->mockClient->addResponse($mockResponse);

        // Act
        $response = $this->transporter->send($request);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"success":true}', $response->getBody());
        $this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        // Test case-insensitive header lookup
        $this->assertEquals(['application/json'], $response->getHeader('content-type'));
        $this->assertEquals('application/json', $response->getHeaderLine('CONTENT-TYPE'));
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
        $request = new Request('POST', 'https://api.example.com/create', $headers, $body);

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
        $request = new Request('GET', 'https://api.example.com', $headers);

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

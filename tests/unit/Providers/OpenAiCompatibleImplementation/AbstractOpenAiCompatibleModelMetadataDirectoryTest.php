<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\OpenAiCompatibleImplementation;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Tests\mocks\MockCache;

/**
 * @covers \WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory
 */
class AbstractOpenAiCompatibleModelMetadataDirectoryTest extends TestCase
{
    /**
     * @var HttpTransporterInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockHttpTransporter;

    /**
     * @var RequestAuthenticationInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockRequestAuthentication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHttpTransporter = $this->createMock(HttpTransporterInterface::class);
        $this->mockRequestAuthentication = $this->createMock(RequestAuthenticationInterface::class);
    }

    protected function tearDown(): void
    {
        // Reset static cache state after each test.
        AiClient::setCache(null);

        parent::tearDown();
    }

    /**
     * Tests sendListModelsRequest() method on success.
     *
     * @return void
     */
    public function testSendListModelsRequestSuccess(): void
    {
        $response = new Response(200, [], '{"data": [{"id": "model-a"}, {"id": "model-b"}]}');

        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0); // Return the request as is.

        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $directory = new MockOpenAiCompatibleModelMetadataDirectory(
            $this->mockHttpTransporter,
            $this->mockRequestAuthentication,
            function (string $modelId) {
                return $this->createModelMetadataStub($modelId);
            }
        );

        $modelsMetadata = $directory->listModelMetadata(); // Calls sendListModelsRequest internally.

        $this->assertCount(2, $modelsMetadata);
        $this->assertEquals('model-a', $modelsMetadata[0]->getId());
        $this->assertEquals('model-b', $modelsMetadata[1]->getId());
    }

    /**
     * Creates a ModelMetadata stub with the given ID.
     *
     * @param string $modelId
     * @return ModelMetadata&\PHPUnit\Framework\MockObject\Stub
     */
    public function createModelMetadataStub(string $modelId)
    {
        $modelMetadata = $this->createStub(ModelMetadata::class);
        $modelMetadata->method('getId')->willReturn($modelId);
        return $modelMetadata;
    }

    /**
     * Tests sendListModelsRequest() method on failure.
     *
     * @return void
     */
    public function testSendListModelsRequestFailure(): void
    {
        $response = new Response(400, [], '{"error": "Invalid parameter provided."}');

        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $directory = new MockOpenAiCompatibleModelMetadataDirectory(
            $this->mockHttpTransporter,
            $this->mockRequestAuthentication,
            function (string $modelId) {
                return $this->createModelMetadataStub($modelId);
            }
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Bad Request (400) - Invalid parameter provided.');

        $directory->listModelMetadata();
    }

    /**
     * Tests that cache is used when available and populated.
     *
     * @return void
     */
    public function testSendListModelsRequestUsesCachedData(): void
    {
        $cache = new MockCache();

        // Seed the cache with model data.
        $cacheKey = 'ai_client_' . AiClient::VERSION . '_'
            . md5(MockOpenAiCompatibleModelMetadataDirectory::class) . '_models';
        $cachedData = [
            'cached-model-a' => [
                'id' => 'cached-model-a',
                'name' => 'Cached Model A',
                'supportedCapabilities' => ['text_generation'],
                'supportedOptions' => [],
            ],
            'cached-model-b' => [
                'id' => 'cached-model-b',
                'name' => 'Cached Model B',
                'supportedCapabilities' => ['text_generation'],
                'supportedOptions' => [],
            ],
        ];
        $cache->seed($cacheKey, $cachedData);

        AiClient::setCache($cache);

        // HTTP transporter should NOT be called when cache is available.
        $this->mockHttpTransporter
            ->expects($this->never())
            ->method('send');

        $directory = new MockOpenAiCompatibleModelMetadataDirectory(
            $this->mockHttpTransporter,
            $this->mockRequestAuthentication,
            null,
            [],
            true
        );

        $modelsMetadata = $directory->listModelMetadata();

        $this->assertCount(2, $modelsMetadata);
        $this->assertEquals('cached-model-a', $modelsMetadata[0]->getId());
        $this->assertEquals('Cached Model A', $modelsMetadata[0]->getName());
        $this->assertEquals('cached-model-b', $modelsMetadata[1]->getId());
        $this->assertEquals('Cached Model B', $modelsMetadata[1]->getName());
    }

    /**
     * Tests that API response is cached when cache is available but empty.
     *
     * @return void
     */
    public function testSendListModelsRequestCachesApiResponse(): void
    {
        $cache = new MockCache();
        AiClient::setCache($cache);

        $response = new Response(200, [], '{"data": [{"id": "api-model-a"}, {"id": "api-model-b"}]}');

        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $directory = new MockOpenAiCompatibleModelMetadataDirectory(
            $this->mockHttpTransporter,
            $this->mockRequestAuthentication,
            null,
            [],
            true
        );

        $modelsMetadata = $directory->listModelMetadata();

        $this->assertCount(2, $modelsMetadata);
        $this->assertEquals('api-model-a', $modelsMetadata[0]->getId());
        $this->assertEquals('api-model-b', $modelsMetadata[1]->getId());

        // Verify cache was written.
        $setOperations = $cache->getOperationsOfType('set');
        $this->assertCount(1, $setOperations);

        $cacheKey = 'ai_client_' . AiClient::VERSION . '_'
            . md5(MockOpenAiCompatibleModelMetadataDirectory::class) . '_models';
        $this->assertEquals($cacheKey, $setOperations[0]['key']);

        // Verify cached data structure.
        $cachedData = $cache->peek($cacheKey);
        $this->assertIsArray($cachedData);
        $this->assertArrayHasKey('api-model-a', $cachedData);
        $this->assertArrayHasKey('api-model-b', $cachedData);
        $this->assertEquals('api-model-a', $cachedData['api-model-a']['id']);
    }

    /**
     * Tests that API is called when no cache is configured.
     *
     * @return void
     */
    public function testSendListModelsRequestWorksWithoutCache(): void
    {
        // Ensure no cache is set.
        AiClient::setCache(null);

        $response = new Response(200, [], '{"data": [{"id": "model-x"}]}');

        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $directory = new MockOpenAiCompatibleModelMetadataDirectory(
            $this->mockHttpTransporter,
            $this->mockRequestAuthentication,
            null,
            [],
            true
        );

        $modelsMetadata = $directory->listModelMetadata();

        $this->assertCount(1, $modelsMetadata);
        $this->assertEquals('model-x', $modelsMetadata[0]->getId());
    }

    /**
     * Tests that cache keys are unique per child class.
     *
     * @return void
     */
    public function testCacheKeysAreUniquePerChildClass(): void
    {
        $cache = new MockCache();
        AiClient::setCache($cache);

        // Seed cache for the first directory class.
        $cacheKey1 = 'ai_client_' . AiClient::VERSION . '_'
            . md5(MockOpenAiCompatibleModelMetadataDirectory::class) . '_models';
        $cachedData1 = [
            'first-provider-model' => [
                'id' => 'first-provider-model',
                'name' => 'First Provider Model',
                'supportedCapabilities' => ['text_generation'],
                'supportedOptions' => [],
            ],
        ];
        $cache->seed($cacheKey1, $cachedData1);

        // Seed cache for the second directory class with different data.
        $cacheKey2 = 'ai_client_' . AiClient::VERSION . '_'
            . md5(AnotherMockOpenAiCompatibleModelMetadataDirectory::class) . '_models';
        $cachedData2 = [
            'second-provider-model' => [
                'id' => 'second-provider-model',
                'name' => 'Second Provider Model',
                'supportedCapabilities' => ['text_generation'],
                'supportedOptions' => [],
            ],
        ];
        $cache->seed($cacheKey2, $cachedData2);

        // Verify keys are different.
        $this->assertNotEquals($cacheKey1, $cacheKey2);

        // HTTP transporter should NOT be called for either directory.
        $this->mockHttpTransporter
            ->expects($this->never())
            ->method('send');

        $directory1 = new MockOpenAiCompatibleModelMetadataDirectory(
            $this->mockHttpTransporter,
            $this->mockRequestAuthentication,
            null,
            [],
            true
        );

        $directory2 = new AnotherMockOpenAiCompatibleModelMetadataDirectory(
            $this->mockHttpTransporter,
            $this->mockRequestAuthentication,
            null,
            [],
            true
        );

        $models1 = $directory1->listModelMetadata();
        $models2 = $directory2->listModelMetadata();

        // Each directory should get its own cached data.
        $this->assertCount(1, $models1);
        $this->assertEquals('first-provider-model', $models1[0]->getId());

        $this->assertCount(1, $models2);
        $this->assertEquals('second-provider-model', $models2[0]->getId());
    }

    /**
     * Tests that cache is bypassed when cached data is not an array.
     *
     * @return void
     */
    public function testCacheBypassedWhenCachedDataIsInvalid(): void
    {
        $cache = new MockCache();
        AiClient::setCache($cache);

        // Seed cache with invalid data (not an array).
        $cacheKey = 'ai_client_' . AiClient::VERSION . '_'
            . md5(MockOpenAiCompatibleModelMetadataDirectory::class) . '_models';
        $cache->seed($cacheKey, 'invalid-string-data');

        $response = new Response(200, [], '{"data": [{"id": "fresh-model"}]}');

        $this->mockRequestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        // API should be called because cached data is invalid.
        $this->mockHttpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $directory = new MockOpenAiCompatibleModelMetadataDirectory(
            $this->mockHttpTransporter,
            $this->mockRequestAuthentication,
            null,
            [],
            true
        );

        $modelsMetadata = $directory->listModelMetadata();

        $this->assertCount(1, $modelsMetadata);
        $this->assertEquals('fresh-model', $modelsMetadata[0]->getId());
    }
}

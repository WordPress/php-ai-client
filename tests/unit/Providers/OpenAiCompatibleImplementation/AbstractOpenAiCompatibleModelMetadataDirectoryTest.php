<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\OpenAiCompatibleImplementation;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * @covers \WordPress\AiClient\Providers\ApiBasedImplementation\AbstractOpenAiCompatibleModelMetadataDirectory
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
        $response = new Response(400, [], '{"error": "Bad Request"}');

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

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage('Bad status code: 400. Bad Request');

        $directory->listModelMetadata();
    }
}

<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\ApiBasedImplementation;

use Exception;
use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Common\Contracts\CachesDataInterface;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WordPress\AiClient\Providers\Http\Exception\NetworkException;
use WordPress\AiClient\Providers\Http\Exception\ServerException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * @covers \WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability
 */
class ListModelsApiBasedProviderAvailabilityTest extends TestCase
{
    /**
     * @var ModelMetadataDirectoryInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $modelMetadataDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelMetadataDirectory = $this->createMock(ModelMetadataDirectoryInterface::class);
    }

    /**
     * Tests isConfigured() method when listing models succeeds.
     *
     * @return void
     */
    public function testIsConfiguredReturnsTrueOnSuccess(): void
    {
        $this->modelMetadataDirectory
            ->expects($this->once())
            ->method('listModelMetadata')
            ->willReturn([]);

        $availability = new ListModelsApiBasedProviderAvailability($this->modelMetadataDirectory);

        $this->assertTrue($availability->isConfigured());
    }

    /**
     * Tests isConfigured() method when listing models throws an exception.
     *
     * @return void
     */
    public function testIsConfiguredReturnsFalseOnException(): void
    {
        $this->modelMetadataDirectory
            ->expects($this->once())
            ->method('listModelMetadata')
            ->willThrowException(new Exception('API error'));

        $availability = new ListModelsApiBasedProviderAvailability($this->modelMetadataDirectory);

        $this->assertFalse($availability->isConfigured());
    }

    /**
     * Tests verifyCredentials() method when listing models succeeds.
     *
     * @return void
     */
    public function testVerifyCredentialsReturnsTrueOnSuccess(): void
    {
        $this->modelMetadataDirectory
            ->expects($this->once())
            ->method('listModelMetadata')
            ->willReturn([]);

        $availability = new ListModelsApiBasedProviderAvailability($this->modelMetadataDirectory);

        $this->assertTrue($availability->verifyCredentials());
    }

    /**
     * Tests verifyCredentials() method when the provider rejects the credentials.
     *
     * @dataProvider rejectedCredentialsStatusCodeProvider
     *
     * @param int $statusCode The HTTP status code of the response.
     * @return void
     */
    public function testVerifyCredentialsReturnsFalseWhenCredentialsAreRejected(int $statusCode): void
    {
        $this->modelMetadataDirectory
            ->expects($this->once())
            ->method('listModelMetadata')
            ->willThrowException(ClientException::fromClientErrorResponse(new Response($statusCode, [])));

        $availability = new ListModelsApiBasedProviderAvailability($this->modelMetadataDirectory);

        $this->assertFalse($availability->verifyCredentials());
    }

    /**
     * Provides status codes of responses that reject the credentials.
     *
     * @return array<string, array{int}>
     */
    public function rejectedCredentialsStatusCodeProvider(): array
    {
        return [
            '400 Bad Request' => [400],
            '401 Unauthorized' => [401],
            '403 Forbidden' => [403],
        ];
    }

    /**
     * Tests verifyCredentials() method when the credentials cannot be verified.
     *
     * @dataProvider unverifiableCredentialsExceptionProvider
     *
     * @param Exception $exception The exception thrown when listing models.
     * @return void
     */
    public function testVerifyCredentialsThrowsWhenCredentialsCannotBeVerified(Exception $exception): void
    {
        $this->modelMetadataDirectory
            ->expects($this->once())
            ->method('listModelMetadata')
            ->willThrowException($exception);

        $availability = new ListModelsApiBasedProviderAvailability($this->modelMetadataDirectory);

        $this->expectExceptionObject($exception);

        $availability->verifyCredentials();
    }

    /**
     * Provides exceptions that do not reflect on the credentials.
     *
     * @return array<string, array{Exception}>
     */
    public function unverifiableCredentialsExceptionProvider(): array
    {
        return [
            'network error' => [new NetworkException('Connection timed out.')],
            '408 Request Timeout' => [ClientException::fromClientErrorResponse(new Response(408, []))],
            '429 Too Many Requests' => [ClientException::fromClientErrorResponse(new Response(429, []))],
            '500 Internal Server Error' => [ServerException::fromServerErrorResponse(new Response(500, []))],
            '503 Service Unavailable' => [ServerException::fromServerErrorResponse(new Response(503, []))],
        ];
    }

    /**
     * Tests that verifyCredentials() does not rely on a cached model list, while isConfigured() does.
     *
     * @return void
     */
    public function testVerifyCredentialsInvalidatesCachedModelsFirst(): void
    {
        $modelMetadataDirectory = new class implements ModelMetadataDirectoryInterface, CachesDataInterface {
            /**
             * @var list<string> The methods called on this directory, in order.
             */
            public array $calls = [];

            public function listModelMetadata(): array
            {
                $this->calls[] = 'listModelMetadata';
                return [];
            }

            public function hasModelMetadata(string $modelId): bool
            {
                return false;
            }

            public function getModelMetadata(string $modelId): ModelMetadata
            {
                throw new InvalidArgumentException('No models available.');
            }

            public function invalidateCaches(): void
            {
                $this->calls[] = 'invalidateCaches';
            }
        };

        $availability = new ListModelsApiBasedProviderAvailability($modelMetadataDirectory);

        $availability->isConfigured();
        $this->assertSame(['listModelMetadata'], $modelMetadataDirectory->calls);

        $modelMetadataDirectory->calls = [];

        $availability->verifyCredentials();
        $this->assertSame(['invalidateCaches', 'listModelMetadata'], $modelMetadataDirectory->calls);
    }
}

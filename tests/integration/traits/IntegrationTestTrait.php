<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\integration\traits;

/**
 * Trait providing shared functionality for integration tests.
 *
 * This trait provides utility methods for integration tests that make
 * real API calls to AI providers.
 */
trait IntegrationTestTrait
{
    /**
     * Skips the test if the specified environment variable is not set.
     *
     * @param string $envVar The name of the environment variable to check.
     */
    protected function requireApiKey(string $envVar): void
    {
        // Check both $_ENV (populated by symfony/dotenv) and getenv() (shell environment)
        $value = $_ENV[$envVar] ?? getenv($envVar);
        if ($value === false || $value === '' || $value === null) {
            $this->markTestSkipped("Skipping: {$envVar} environment variable is not set.");
        }
    }
}

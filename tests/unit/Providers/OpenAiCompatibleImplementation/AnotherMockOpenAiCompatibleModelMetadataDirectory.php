<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Providers\OpenAiCompatibleImplementation;

/**
 * Second mock class for testing that cache keys are unique per child class.
 *
 * This class extends the main mock but represents a different "provider" to verify
 * that the cache key generation properly differentiates between child classes.
 */
class AnotherMockOpenAiCompatibleModelMetadataDirectory extends MockOpenAiCompatibleModelMetadataDirectory
{
    // Inherits all functionality from parent.
    // The class name difference ensures a different cache key.
}

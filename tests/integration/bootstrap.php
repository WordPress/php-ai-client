<?php

/**
 * Bootstrap file for integration tests.
 *
 * Loads environment variables from .env file before running tests.
 * Registers AI provider packages for testing.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use WordPress\AiClient\AiClient;
use WordPress\AnthropicAiProvider\Provider\AnthropicProvider;
use WordPress\GoogleAiProvider\Provider\GoogleProvider;
use WordPress\OpenAiAiProvider\Provider\OpenAiProvider;

$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    $dotenv = new Dotenv();
    // Enable putenv() so getenv() works (used by ProviderRegistry for API keys)
    $dotenv->usePutenv(true);
    $dotenv->load($envFile);
}

// Register provider packages for integration tests.
$registry = AiClient::defaultRegistry();
$registry->registerProvider(AnthropicProvider::class);
$registry->registerProvider(GoogleProvider::class);
$registry->registerProvider(OpenAiProvider::class);

<?php

/**
 * Bootstrap file for integration tests.
 *
 * Loads environment variables from .env file before running tests.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    $dotenv = new Dotenv();
    // Enable putenv() so getenv() works (used by ProviderRegistry for API keys)
    $dotenv->usePutenv(true);
    $dotenv->load($envFile);
}

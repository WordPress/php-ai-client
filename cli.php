<?php
/**
 * CLI script for interacting with the AI client.
 *
 * This script allows users to send prompts to the AI and receive responses.
 * It supports named arguments for provider and model selection.
 *
 * Usage:
 *   GOOGLE_API_KEY=123456 php cli.php 'Your prompt here' --providerId=google --modelId=gemini-2.5-flash
 *   OPENAI_API_KEY=123456 php cli.php 'Your prompt here' --providerId=openai
 *   GOOGLE_API_KEY=123456 OPENAI_API_KEY=123456 php cli.php 'Your prompt here'
 */

declare(strict_types=1);

use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Messages\Util\MessageUtil;
use WordPress\AiClient\ProviderImplementations\Anthropic\AnthropicProvider;
use WordPress\AiClient\ProviderImplementations\Google\GoogleProvider;
use WordPress\AiClient\ProviderImplementations\OpenAi\OpenAiProvider;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Http\HttpTransporterFactory;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\DTO\RequiredOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Prints the output to stdout.
 *
 * @param string $output The output to print.
 */
function printOutput(string $output): void
{
    echo $output . PHP_EOL;
}

/**
 * Logs an informational message to stderr.
 *
 * @param string $message The message to log.
 */
function logInfo(string $message): void
{
    fwrite(STDERR, '[INFO] ' . $message . PHP_EOL);
}

/**
 * Logs a warning message to stderr.
 *
 * @param string $message The message to log.
 */
function logWarning(string $message): void
{
    fwrite(STDERR, '[WARNING] ' . $message . PHP_EOL);
}

/**
 * Logs an error message to stderr and terminates the script.
 *
 * @param string $message The message to log.
 * @param int    $exit_code The exit code to use.
 */
function logError(string $message, int $exit_code = 1): void
{
    fwrite(STDERR, '[ERROR] ' . $message . PHP_EOL);
    exit($exit_code);
}

// --- Argument parsing ---

$positional_args = [];
$named_args      = [];

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    if (str_starts_with($arg, '--')) {
        $parts = explode('=', substr($arg, 2), 2);
        $key   = $parts[0];
        $value = $parts[1] ?? true;
        if (empty($key)) {
            logWarning("Ignoring invalid named argument: {$arg}");
            continue;
        }
        $named_args[$key] = $value;
    } else {
        $positional_args[] = $arg;
    }
}

// --- Input validation ---

if (empty($positional_args[0])) {
    logError('Missing required positional argument "prompt input".');
}

// Prompt input. Allow complex input as a JSON string.
$promptInput = $positional_args[0];
if (strpos($promptInput, '{') === 0 || strpos($promptInput, '[') === 0) {
    $decodedInput = json_decode($promptInput, true);
    if ($decodedInput) {
        $promptInput = $decodedInput;
    }
}

// Provider ID, model ID, and output format.
$providerId = $named_args['providerId'] ?? null;
$modelId = $named_args['modelId'] ?? null;
$outputFormat = $named_args['outputFormat'] ?? 'message-text';

// Any model configuration options.
$schema = ModelConfig::getJsonSchema()['properties'];
$model_config_data = [];
foreach ($named_args as $key => $value) {
    if (!isset($schema[$key])) {
        continue;
    }

    $property_schema = $schema[$key];
    $type = $property_schema['type'] ?? null;

    $processed_value = $value;
    if ($type === 'array' || $type === 'object') {
        $decoded = json_decode((string) $value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logWarning("Invalid JSON for argument --{$key}: " . json_last_error_msg());
            continue;
        }
        $processed_value = $decoded;
    } elseif ($type === 'integer') {
        $processed_value = (int) $value;
    } elseif ($type === 'number') {
        $processed_value = (float) $value;
    } elseif ($type === 'boolean') {
        $processed_value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (null === $processed_value) {
            logWarning("Invalid boolean for argument --{$key}: {$value}");
            continue;
        }
    }

    $model_config_data[$key] = $processed_value;
}

// --- SDK setup ---

// This will eventually be obsolete, as the AiClient class will handle it.
$providerRegistry = new ProviderRegistry();
$providerRegistry->setHttpTransporter(HttpTransporterFactory::createTransporter());
$providerRegistry->registerProvider(AnthropicProvider::class);
$providerRegistry->registerProvider(GoogleProvider::class);
$providerRegistry->registerProvider(OpenAiProvider::class);

// --- Main logic ---

try {
    $modelConfig = ModelConfig::fromArray($model_config_data);

    $promptBuilder = new PromptBuilder($providerRegistry, $promptInput, $modelConfig);
    if ($providerId && $modelId) {
        $providerClassName = $providerRegistry->getProviderClassName($providerId);
        $promptBuilder = $promptBuilder->usingModel($providerClassName::model($modelId));
    } elseif ($providerId) {
        $promptBuilder = $promptBuilder->usingProvider($providerId);
    }
} catch (InvalidArgumentException $e) {
    logError('Invalid arguments while trying to set up prompt builder: ' . $e->getMessage());
} catch (ResponseException $e) {
    logError('Request failed while trying to set up prompt builder: ' . $e->getMessage());
}

// TODO: Reinstate this once the generative AI result includes model and provider metadata.
//logInfo("Using provider ID: \"{$modelInstance->providerMetadata()->getId()}\"");
//logInfo("Using model ID: \"{$modelInstance->metadata()->getId()}\"");

try {
    $result = $promptBuilder->generateTextResult();
} catch (InvalidArgumentException $e) {
    logError('Invalid arguments while trying to generate text result: ' . $e->getMessage());
} catch (ResponseException $e) {
    logError('Request failed while trying to generate text result: ' . $e->getMessage());
}

switch ($outputFormat) {
    case 'result-json':
        $output = json_encode($result, JSON_PRETTY_PRINT);
        break;
    case 'candidates-json':
        $output = json_encode($result->getCandidates(), JSON_PRETTY_PRINT);
        break;
    case 'message-text':
    default:
        $output = $result->toText();
}

printOutput($output);

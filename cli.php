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

$promptInput = $positional_args[0];

$providerId = $named_args['providerId'] ?? null;
$modelId = $named_args['modelId'] ?? null;
$outputFormat = $named_args['outputFormat'] ?? 'message-text';

// --- SDK setup ---

// This will eventually be obsolete, as the AiClient class will handle it.
$providerRegistry = new ProviderRegistry();
$providerRegistry->setHttpTransporter(HttpTransporterFactory::createTransporter());
$providerRegistry->registerProvider(AnthropicProvider::class);
$providerRegistry->registerProvider(GoogleProvider::class);
$providerRegistry->registerProvider(OpenAiProvider::class);

// --- Main logic ---

// Allow complex input to be passed as a JSON string.
if (strpos($promptInput, '{') === 0 || strpos($promptInput, '[') === 0) {
    $decodedInput = json_decode($promptInput, true);
    if ($decodedInput) {
        $promptInput = $decodedInput;
    }
}

$messages = MessageUtil::parseMessagesFromInput($promptInput);

$modelConfig = new ModelConfig();
$modelConfig->setTemperature(0.1);

$modelRequirements = new ModelRequirements(
    [
        CapabilityEnum::textGeneration(),
    ],
    [
        new RequiredOption(ModelConfig::KEY_TEMPERATURE, 0.1),
    ],
);

try {
    if (!$providerId && !$modelId) {
        $providerModelsMetadata = $providerRegistry->findModelsMetadataForSupport($modelRequirements);
        $providerId = $providerModelsMetadata[0]->getProvider()->getId();
        $modelId = $providerModelsMetadata[0]->getModels()[0]->getId();
    } elseif (!$modelId) {
        $modelsMetadata = $providerRegistry->findProviderModelsMetadataForSupport($providerId, $modelRequirements);
        $modelId = $modelsMetadata[0]->getId();
    }
    $modelInstance = $providerRegistry->getProviderModel($providerId, $modelId);
} catch (InvalidArgumentException $e) {
    logError('Invalid arguments while trying to set up model instance: ' . $e->getMessage());
} catch (ResponseException $e) {
    logError('Request failed while trying to set up model instance: ' . $e->getMessage());
}

logInfo("Using provider ID: \"{$modelInstance->providerMetadata()->getId()}\"");
logInfo("Using model ID: \"{$modelInstance->metadata()->getId()}\"");

if (!($modelInstance instanceof TextGenerationModelInterface)) {
    logError('The model class ' . get_class($modelInstance) . ' does not support text generation.');
}

$modelInstance->setConfig($modelConfig);

try {
    $result = $modelInstance->generateTextResult($messages);
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

# PHP AI Client

[_Part of the **AI Building Blocks for WordPress** initiative_](https://make.wordpress.org/ai/2025/07/17/ai-building-blocks)

A provider agnostic PHP AI client SDK to communicate with any generative AI models of various capabilities using a uniform API.

## General information

This project is a PHP SDK, which can be installed as a Composer package. In WordPress, it could be bundled in plugins. It is however not a plugin itself.

While this project is stewarded by [WordPress AI Team](https://make.wordpress.org/ai/) members and contributors, it is technically WordPress agnostic. The gap the project addresses is relevant for not only the WordPress ecosystem, but the overall PHP ecosystem, so any PHP project could benefit from it. There is also no technical reason to scope it to WordPress, as communicating with AI models and their providers is independent of WordPress's built-in APIs and paradigms.

## Installation

```
composer require wordpress/php-ai-client
```

## Code examples

### Text generation using a specific model

```php
use WordPress\AiClient\AiClient;

$text = AiClient::prompt('Write a 2-verse poem about PHP.')
    ->usingModel(Google::model('gemini-2.5-flash'))
    ->generateText();
```

### Text generation using any compatible model from a specific provider

```php
use WordPress\AiClient\AiClient;

$text = AiClient::prompt('Write a 2-verse poem about PHP.')
    ->usingProvider('openai')
    ->generateText();
```

### Text generation using any compatible model

```php
use WordPress\AiClient\AiClient;

$text = AiClient::prompt('Write a 2-verse poem about PHP.')
    ->generateText();
```

### Text generation with additional parameters

```php
use WordPress\AiClient\AiClient;

$text = AiClient::prompt('Write a 2-verse poem about PHP.')
    ->usingSystemInstruction('You are a famous poet from the 17th century.')
    ->usingTemperature(0.8)
    ->generateText();
```

### Text generation with multiple candidates using any compatible model

```php
use WordPress\AiClient\AiClient;

$texts = AiClient::prompt('Write a 2-verse poem about PHP.')
    ->generateTexts(4);
```

### Text generation using max tokens

```php
use WordPress\AiClient\AiClient;

$text = AiClient::prompt('Write a 80-verse poem with long stanzas about PHP.')
    ->usingSystemInstruction('You are a famous poet from the 17th century.')
    ->usingTemperature(0.8)
    ->usingMaxTokens(8000);
    ->generateText();
```

### Image generation using any compatible model

```php
use WordPress\AiClient\AiClient;

$imageFile = AiClient::prompt('Generate an illustration of the PHP elephant in the Caribbean sea.')
    ->generateImage();
```

See the [`PromptBuilder` class](https://github.com/WordPress/php-ai-client/blob/trunk/src/Builders/PromptBuilder.php) and its public methods for all the ways you can configure the prompt.

**More documentation is coming soon.**

## Event Dispatching

The AI Client supports PSR-14 event dispatching for prompt lifecycle events. This allows you to hook into the generation process for logging, monitoring, or other integrations.

### Available Events

- `BeforeGenerateResultEvent` - Dispatched before a prompt is sent to the model
- `AfterGenerateResultEvent` - Dispatched after a result is received from the model

**Important:** Event listeners should not return a value, as they will be ignored. In order to modify data that is passed with the event object, you need to rely on setters on the event object. Any event data for which there are no setters on the event object is meant to be immutable or, in other words, read-only for the event listener.

### Connecting Your Event Dispatcher

To enable event dispatching, pass any PSR-14 compatible `EventDispatcherInterface` to the client:

```php
use WordPress\AiClient\AiClient;

// Set your PSR-14 event dispatcher
AiClient::setEventDispatcher($yourEventDispatcher);

// Events will now be dispatched during generation
$text = AiClient::prompt('Hello, world!')
    ->generateText();
```

### Example: Logging Events

```php
use WordPress\AiClient\Events\BeforeGenerateResultEvent;
use WordPress\AiClient\Events\AfterGenerateResultEvent;

// In your event listener/subscriber
class AiEventListener
{
    public function onBeforeGenerate(BeforeGenerateResultEvent $event): void
    {
        $model = $event->getModel();
        $messages = $event->getMessages();
        $capability = $event->getCapability();

        // Log, monitor, or perform other actions
    }

    public function onAfterGenerate(AfterGenerateResultEvent $event): void
    {
        $result = $event->getResult();

        // Log the result, track usage, etc.
    }
}
```

## Further reading

For more information on the requirements and guiding principles, please review:

* [Glossary](./docs/GLOSSARY.md)
* [Requirements](./docs/REQUIREMENTS.md)
* [Architecture](./docs/ARCHITECTURE.md)
* [Prepublish Checklist](./docs/PREPUBLISH-CHECKLIST.md)

See the [contributing documentation](./CONTRIBUTING.md) for more information on how to get involved.

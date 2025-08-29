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

### Image generation using any compatible model

```php
use WordPress\AiClient\AiClient;

$imageFile = AiClient::prompt('Generate an illustration of the PHP elephant in the Caribbean sea.')
    ->generateImage();
```

See the [`PromptBuilder` class](https://github.com/WordPress/php-ai-client/blob/trunk/src/Builders/PromptBuilder.php) and its public methods for all the ways you can configure the prompt.

**More documentation is coming soon.**

## Further reading

For more information on the requirements and guiding principles, please review:

* [Glossary](./docs/GLOSSARY.md)
* [Requirements](./docs/REQUIREMENTS.md)
* [Architecture](./docs/ARCHITECTURE.md)

See the [contributing documentation](./CONTRIBUTING.md) for more information on how to get involved.

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

$text = AiClient::prompt('Write an 80-verse poem with long stanzas about PHP.')
    ->usingSystemInstruction('You are a famous poet from the 17th century.')
    ->usingTemperature(0.8)
    ->usingMaxTokens(8000)
    ->generateText();
```

### Image generation using any compatible model

```php
use WordPress\AiClient\AiClient;

$imageFile = AiClient::prompt('Generate an illustration of the PHP elephant in the Caribbean sea.')
    ->generateImage();
```

### Embedding generation using a specific model

Unlike the other capabilities, embedding generation always requires you to name the model. Embedding vectors are only comparable to other vectors produced by the same model, so a stored set of embeddings is permanently tied to the model that created it. If the library picked a model for you, that choice could change between requests — for example when the registered providers change — silently making new vectors incomparable to the ones you already stored. Omitting the model therefore raises an error rather than falling back to a default.

```php
use WordPress\AiClient\AiClient;

$embedding = AiClient::input('PHP powers a large part of the web.')
    ->usingProviderModel('google', 'gemini-embedding-001')
    ->generateEmbedding();

$values = $embedding->getValues();
```

You can also pass a model instance, which is useful when you already have one or want to reference the provider class directly:

```php
use WordPress\AiClient\AiClient;
use WordPress\GoogleAiProvider\Provider\GoogleProvider;

$embedding = AiClient::input('PHP powers a large part of the web.')
    ->usingModel(GoogleProvider::model('gemini-embedding-001'))
    ->generateEmbedding();
```

### Batch embedding generation

```php
use WordPress\AiClient\AiClient;

$embeddings = AiClient::input([
        'PHP powers a large part of the web.',
        'WordPress makes publishing accessible.',
    ])
    ->usingProviderModel('openai', 'text-embedding-3-small')
    ->generateEmbeddings();
```

### Embedding generation with dimensions

```php
use WordPress\AiClient\AiClient;

$embedding = AiClient::input('PHP powers a large part of the web.')
    ->usingProviderModel('openai', 'text-embedding-3-small')
    ->usingDimensions(512)
    ->generateEmbedding();
```

Embedding inputs are independent [`MessagePart`](https://github.com/WordPress/php-ai-client/blob/trunk/src/Messages/DTO/MessagePart.php) values, not a conversation. `input()` accepts one input or a list of inputs. Variadic `withInput()` accepts one or more arguments, and lists can be passed using PHP's spread syntax (`withInput(...$inputs)`). Each input may be a string, `MessagePart`, `File`, or message-part array shape.

The model you name is verified before any request is sent: it must support embedding generation, accept the input modalities of your inputs, and support every configuration option you set. If it does not, an `InvalidArgumentException` explains which capability or option is unsupported. To check without triggering an exception, call `isSupported()`, which returns `false` for any model that cannot fulfill the request — including one whose provider is not registered or configured, or a model ID the provider does not offer. Only omitting the model entirely still throws, since that is a programming error rather than an unsupported model.

### Discovering available embedding models

Since no model is chosen for you, you may need to find out which embedding models the configured providers offer:

```php
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

$requirements = new ModelRequirements([CapabilityEnum::embeddingGeneration()], []);

foreach (AiClient::defaultRegistry()->findModelsMetadataForSupport($requirements) as $providerModels) {
    $providerId = $providerModels->getProvider()->getId();

    foreach ($providerModels->getModels() as $modelMetadata) {
        echo $providerId . ' / ' . $modelMetadata->getId() . "\n";
    }
}
```

### Decisions using any compatible model

Decision models answer typed questions about the state you give them rather than generating content. There are three types of question:

* A `binary` question asks whether a statement is true, optionally describing what counts as true and what counts as false, and is answered with the probability that it is true.
* A `choice` question lists options, each a value with an optional description of when it applies, and is answered with the value of the chosen option.
* A `score` question lists the levels of a scale from lowest to highest, and is answered with a position on that scale, from `0` for the lowest level to one less than the number of levels. The position may fall between two levels.

Each description, of an option, a level, or what counts as true or false, is a sentence, or an array such as `['what' => 'Payments and refunds.', 'not_for' => 'Questions about prices.']` when structure makes it clearer. An option without a description is interpreted by its value alone.

Answers to `choice` and `score` questions may also carry the model's confidence and a probability for each option or level, depending on what the model reports. Every answer is checked against its question, so an answer of the wrong type, an option the question does not have, or a position outside the scale raises a `RuntimeException`.

A model may refuse to answer a question while still answering the others. Check `isRefusal()` before reading the value of an answer, since reading the value of a refusal raises a `RuntimeException`.

As with content generation, a suitable model is discovered if you do not name one. Decisions require a registered provider with at least one model that supports them. If there is none, `decideResult()` throws an `InvalidArgumentException`, and `isSupported()` returns `false`.

**Name the model when your code compares answers to thresholds.** Probabilities and confidences are calibrated per model: a probability of 0.8 can be reliable on one model and not on another, and each provider computes confidence in its own way. A check such as `$probability > 0.8` therefore changes meaning if a different model is chosen, for example when the registered providers change. Pick thresholds using labelled examples from your application, and name the model they were picked for with `usingModel()`, ideally by a versioned ID rather than an alias such as `latest`, since an alias moves to a new model on release. Providers also differ in their limits, such as how many questions, options, or levels a request may have, so a request that one model accepts may be rejected by another.

```php
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Decisions\DTO\DecisionOption;
use WordPress\AiClient\Decisions\DTO\DecisionQuestion;
use WordPress\AiClient\Decisions\Enums\DecisionQuestionTypeEnum;

$result = AiClient::decide(['comment' => $commentText])
    ->withQuestion(
        'spam',
        new DecisionQuestion(
            DecisionQuestionTypeEnum::binary(),
            'Is this comment spam?',
            [
                'true' => 'The comment advertises something unrelated to the post.',
                'false' => 'The comment responds to the post, even if briefly.',
            ]
        )
    )
    ->withQuestion(
        'route',
        new DecisionQuestion(
            DecisionQuestionTypeEnum::choice(),
            'How should a moderator handle it?',
            [
                new DecisionOption('approve', 'The comment is fine to publish.'),
                new DecisionOption('hold', 'The comment needs a closer look.'),
                'trash',
            ]
        )
    )
    ->withQuestion(
        'tone',
        new DecisionQuestion(
            DecisionQuestionTypeEnum::score(),
            'How civil is the comment?',
            ['Hostile.', 'Neutral.', 'Friendly.']
        )
    )
    ->decideResult();

$spamProbability = $result->getAnswer('spam')->getProbability(); // e.g. 0.03

$route = $result->getAnswer('route');
if (!$route->isRefusal()) {
    $choice = $route->getChoice(); // e.g. 'approve'
    $confidence = $route->getConfidence(); // e.g. 0.91, or null if not reported
}

$tone = $result->getAnswer('tone')->getScore(); // e.g. 1.7, between 'Neutral.' and 'Friendly.'
```

The state can be a string, a file, or an array of values that may contain files, such as images. A model is only chosen if it accepts the types of file in the state:

```php
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Decisions\DTO\DecisionQuestion;
use WordPress\AiClient\Decisions\Enums\DecisionQuestionTypeEnum;

$result = AiClient::decide([
        'caption' => $caption,
        'photo' => File::fromLocalPath('/path/to/photo.jpg'),
    ])
    ->withQuestion(
        'matches',
        new DecisionQuestion(DecisionQuestionTypeEnum::binary(), 'Does the caption describe the photo?')
    )
    ->decideResult();
```

See the [`PromptBuilder` class](https://github.com/WordPress/php-ai-client/blob/trunk/src/Builders/PromptBuilder.php), the [`EmbeddingBuilder` class](https://github.com/WordPress/php-ai-client/blob/trunk/src/Builders/EmbeddingBuilder.php), and the [`DecisionBuilder` class](https://github.com/WordPress/php-ai-client/blob/trunk/src/Builders/DecisionBuilder.php) and their public methods for all the ways you can configure generation.

**More documentation is coming soon.**

## Event Dispatching

The AI Client supports PSR-14 event dispatching for prompt lifecycle events. This allows you to hook into the generation process for logging, monitoring, or other integrations.

### Available Events

- `BeforeGenerateResultEvent` - Dispatched before a prompt is sent to the model
- `AfterGenerateResultEvent` - Dispatched after a result is received from the model
- `BeforeGenerateEmbeddingEvent` - Dispatched before embedding inputs are sent to the model
- `AfterGenerateEmbeddingEvent` - Dispatched after an embedding result is received from the model
- `BeforeDecideEvent` - Dispatched before the decision request, with its state and questions, is sent to the model
- `AfterDecideEvent` - Dispatched after the model's answers have been received and checked against the questions

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

### Maintainers

Maintainers for this repo are [Felix Arntz (@felixarntz)](https://github.com/felixarntz), [Jason Adams (@JasonTheAdams)](https://github.com/JasonTheAdams), and [Jeffrey Paul (@jeffpaul)](https://github.com/jeffpaul); all can be reached using the [@WordPress/php-ai-client-maintainers](https://github.com/orgs/WordPress/teams/php-ai-client-maintainers) team.

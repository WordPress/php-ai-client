# Architecture

This document outlines the architecture for the PHP AI Client. It is critical that it meets all [requirements](./REQUIREMENTS.md).

## High-level API design

The architecture at a high level is heavily inspired by the [Vercel AI SDK](https://github.com/vercel/ai), which is widely used in the NodeJS ecosystem and one of the very few comprehensive AI client SDKs available.

The main additional aspect that the Vercel AI SDK does not cater for easily is for a developer to use AI in a way that the choice of provider remains with the user. To clarify with an example: Instead of "Generate text with Google's model `gemini-2.5-flash`", go with "Generate text using any provider model that supports text generation and multimodal input". In other words, there needs to be a mechanism that allows finding any configured model that supports the given set of required AI capabilities and options.

For the implementer facing API surface, two alternative APIs are available:

* A fluent API is used as the primary means of using the AI client SDK, for easy-to-read code by chaining declarative methods.
* A traditional method based API inspired by the Vercel AI SDK, which is more aligned with traditional WordPress patterns such as passing an array of arguments.

### Code examples

The following examples indicate how this SDK could eventually be used.

#### Generate text using any suitable model from any provider (most basic example)

##### Fluent API
```php
$text = AiClient::prompt('Write a 2-verse poem about PHP.')
    ->generateText();
```

##### Traditional API
```php
$text = AiClient::generateTextResult(
    'Write a 2-verse poem about PHP.'
)->toText();
```

#### Generate text using a Google model

##### Fluent API
```php
$text = AiClient::prompt('Write a 2-verse poem about PHP.')
    ->usingModel(Google::model('gemini-2.5-flash'))
    ->generateText();
```

##### Traditional API
```php
$text = AiClient::generateTextResult(
    'Write a 2-verse poem about PHP.',
    Google::model('gemini-2.5-flash')
)->toText();
```

#### Generate multiple text candidates using an Anthropic model

##### Fluent API
```php
$texts = AiClient::prompt('Write a 2-verse poem about PHP.')
    ->usingModel(Anthropic::model('claude-3.7-sonnet'))
    ->generateTexts(4);
```

##### Traditional API
```php
$texts = AiClient::generateTextResult(
    'Write a 2-verse poem about PHP.',
    Anthropic::model(
        'claude-3.7-sonnet',
        [AiOption::CANDIDATE_COUNT => 4]
    )
)->toTexts();
```

#### Generate an image using any suitable OpenAI model

##### Fluent API
```php
$imageFile = AiClient::prompt('Generate an illustration of the PHP elephant in the Carribean sea.')
    ->usingProvider('openai')
    ->generateImage();
```

##### Traditional API
```php
$modelsMetadata = AiClient::defaultRegistry()->findProviderModelsMetadataForSupport(
    'openai',
    new AiModelRequirements([AiCapability::IMAGE_GENERATION])
);
$imageFile = AiClient::generateImageResult(
    'Generate an illustration of the PHP elephant in the Carribean sea.',
    AiClient::defaultRegistry()->getProviderModel(
        'openai',
        $modelsMetadata[0]->getId()
    )
)->toImageFile();
```

#### Generate an image using any suitable model from any provider

##### Fluent API
```php
$imageFile = AiClient::prompt('Generate an illustration of the PHP elephant in the Carribean sea.')
    ->generateImage();
```

##### Traditional API
```php
$providerModelsMetadata = AiClient::defaultRegistry()->findModelsMetadataForSupport(
    new AiModelRequirements([AiCapability::IMAGE_GENERATION])
);
$imageFile = AiClient::generateImageResult(
    'Generate an illustration of the PHP elephant in the Carribean sea.',
    AiClient::defaultRegistry()->getProviderModel(
        $providerModelsMetadata[0]->getProvider()->getId(),
        $providerModelsMetadata[0]->getModels()[0]->getId()
    )
)->toImageFile();
```

#### Generate text using any suitable model from any provider

_Note: This does effectively the exact same as [the first code example](#generate-text-using-any-suitable-model-from-any-provider-most-basic-example), but more verbosely. In other words, if you omit the model parameter, the SDK will do this internally._

##### Fluent API
```php
$providerModelsMetadata = AiClient::defaultRegistry()->findModelsMetadataForSupport(
    new AiModelRequirements([AiCapability::TEXT_GENERATION])
);

$text = AiClient::prompt('Write a 2-verse poem about PHP.')
    ->withModel(
        AiClient::defaultRegistry()->getProviderModel(
            $providerModelsMetadata[0]->getProvider()->getId(),
            $providerModelsMetadata[0]->getModels()[0]->getId()
        )
    )
    ->generateText();
```

##### Traditional API
```php
$providerModelsMetadata = AiClient::defaultRegistry()->findModelsMetadataForSupport(
    new AiModelRequirements([AiCapability::TEXT_GENERATION])
);
$text = AiClient::generateTextResult(
    'Write a 2-verse poem about PHP.',
    AiClient::defaultRegistry()->getProviderModel(
        $providerModelsMetadata[0]->getProvider()->getId(),
        $providerModelsMetadata[0]->getModels()[0]->getId()
    )
)->toText();
```

#### Generate text with an image as additional input using any suitable model from any provider

_Note: Since this omits the model parameter, the SDK will automatically determine which models are suitable and use any of them, similar to [the first code example](#generate-text-using-any-suitable-model-from-any-provider-most-basic-example). Since it knows the input includes an image, it can internally infer that the model needs to not only support `AiCapability::TEXT_GENERATION`, but also `AiOption::INPUT_MODALITIES => ['text', 'image']`._

##### Fluent API
```php
$text = AiClient::prompt('Generate alternative text for this image.')
    ->withInlineImage($base64Blob, 'image/png')
    ->generateText();
```

##### Traditional API
```php
$text = AiClient::generateTextResult(
    [
        [
            'text' => 'Generate alternative text for this image.',
        ],
        [
            'mimeType'   => 'image/png',
            'base64Data' => '...', // Base64-encoded data blob.
        ],
    ]
)->toText();
```

#### Generate text with chat history using any suitable model from any provider

_Note: Similarly to the previous example, even without specifying the model here, the SDK will be able to infer required model capabilities because it can detect that multiple chat messages are passed. Therefore it will internally only consider models that support `AiCapability::TEXT_GENERATION` as well as `AiCapability::CHAT_HISTORY`._

##### Fluent API
```php
$text = AiClient::prompt()
    ->withHistory(
        new UserMessage('Do you spell it WordPress or Wordpress?'),
        new AgentMessage('The correct spelling is WordPress.'),
    )
    ->withText('Can you repeat that please?')
    ->generateText();
```

##### Traditional API
```php
$text = AiClient::generateTextResult(
    [
        [
            'role'  => MessageRole::USER,
            'parts' => ['text' => 'Do you spell it WordPress or Wordpress?'],
        ],
        [
            'role'  => MessageRole::MODEL,
            'parts' => ['text' => 'The correct spelling is WordPress.'],
        ],
        [
            'role'  => MessageRole::USER,
            'parts' => ['text' => 'Can you repeat that please?'],
        ],
    ]
)->toText();
```

#### Generate text with JSON output using any suitable model from any provider

_Note: Unlike the previous two examples, to require JSON output it is necessary to go the verbose route, since it is impossible for the SDK to detect whether you require JSON output purely from the prompt input. Therefore this code example contains the logic to manually search for suitable models and then use one of them for the task._

##### Fluent API
```php
$text = AiClient::prompt('Transform the following CSV content into a JSON array of row data.')
    ->asJsonResponse()
    ->usingOutputSchema([
        'type'  => 'array',
        'items' => [
            'type'       => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
                'age'  => [
                    'type' => 'integer',
                ],
            ],
        ],
    ])
    ->generateText();
```

##### Traditional API
```php
$providerModelsMetadata = AiClient::defaultRegistry()->findModelsMetadataForSupport(
    new AiModelRequirements(
        [AiCapability::TEXT_GENERATION],
        [
            // Make sure the model supports JSON output as well as following a given schema.
            AiOption::OUTPUT_MIME_TYPE => 'application/json',
            AiOption::OUTPUT_SCHEMA    => true,
        ]
    )
);
$jsonString = AiClient::generateTextResult(
    'Transform the following CSV content into a JSON array of row data.',
    AiClient::defaultRegistry()->getProviderModel(
        $providerModelsMetadata[0]->getProvider()->getId(),
        $providerModelsMetadata[0]->getModels()[0]->getId(),
        [
            AiOption::OUTPUT_MIME_TYPE => 'application/json',
            AiOption::OUTPUT_SCHEMA    => [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                        ],
                        'age'  => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ],
        ]
    )
)->toText();
```

## Class diagrams

This section shows comprehensive class diagrams for the proposed architecture. For explanation on specific terms, see the [glossary](./GLOSSARY.md).

**Note:** The class diagrams are not meant to be entirely comprehensive in terms of which AI features and capabilities are or will be supported. For now, they simply use "text generation", "image generation", "text to speech", "speech generation", and "embedding generation" for illustrative purposes. Other features like "music generation" or "video generation" etc. would work similarly.

**Note:** The class diagrams are also not meant to be comprehensive in terms of any specific configuration keys or parameters which are or will be supported. For now, the relevant definitions don't include any specific parameter names or constants.

### Overview: Fluent API for AI implementers

This is a subset of the overall class diagram, purely focused on the fluent API for AI implementers.

```mermaid
---
config:
  class:
    hideEmptyMembersBox: true
---
classDiagram
direction LR
    namespace Ai {
        class AiClient {
            +prompt(string|Message|null $text = null) PromptBuilder$
            +message(?string $text) MessageBuilder$
        }

        class PromptBuilder {
            +withText(string $text) self
            +withInlineImage(string $base64Blob, string $mimeType)
            +withLocalImage(string $path, string $mimeType)
            +withRemoteImage(string $uri, string $mimeType)
            +withImageFile(File $file) self
            +withAudioFile(File $file) self
            +withVideoFile(File $file) self
            +withFunctionResponse(FunctionResponse $functionResponse) self
            +withMessageParts(...MessagePart $part) self
            +withHistory(...Message $messages) self
            +usingModel(AiModel $model) self
            +usingSystemInstruction(string|MessagePart[]|Message $systemInstruction) self
            +usingMaxTokens(int $maxTokens) self
            +usingTemperature(float $temperature) self
            +usingTopP(float $topP) self
            +usingTopK(int $topK) self
            +usingStopSequences(...string $stopSequences) self
            +usingCandidateCount(int $candidateCount) self
            +usingOutputMime(string $mimeType) self
            +usingOutputSchema(array< string, mixed > $schema) self
            +usingOutputModalities(...AiModality $modalities) self
            +asJsonResponse(?array< string, mixed > $schema) self
            +generateResult() GenerativeAiResult
            +generateOperation() GenerativeAiOperation
            +generateTextResult() GenerativeAiResult
            +streamGenerateTextResult() Generator< GenerativeAiResult >
            +generateImageResult() GenerativeAiResult
            +convertTextToSpeechResult() GenerativeAiResult
            +generateSpeechResult() GenerativeAiResult
            +generateEmbeddingsResult() EmbeddingResult
            +generateTextOperation() GenerativeAiOperation
            +generateImageOperation() GenerativeAiOperation
            +convertTextToSpeechOperation() GenerativeAiOperation
            +generateSpeechOperation() GenerativeAiOperation
            +generateEmbeddingsOperation() EmbeddingOperation
            +generateText() string
            +generateTexts(?int $candidateCount) string[]
            +streamGenerateText() Generator< string >
            +generateImage() File
            +generateImages(?int $candidateCount) File[]
            +convertTextToSpeech() File
            +convertTextToSpeeches(?int $candidateCount) File[]
            +generateSpeech() File
            +generateSpeeches(?int $candidateCount) File[]
            +generateEmbeddings() Embedding[]
            +getModelRequirements() AiModelRequirements
            +isSupported() bool
        }

        class MessageBuilder {
            +usingRole(MessageRole $role) self
            +withText(string $text) self
            +withImageFile(File $file) self
            +withAudioFile(File $file) self
            +withVideoFile(File $file) self
            +withFunctionCall(FunctionCall $functionCall) self
            +withFunctionResponse(FunctionResponse $functionResponse) self
            +withMessageParts(...MessagePart $part) self
            +get() Message
        }
    }

    AiClient .. PromptBuilder : creates
    AiClient .. MessageBuilder : creates
```

### Overview: Traditional method call API for AI implementers

This is a subset of the overall class diagram, purely focused on the traditional method call API for AI implementers.

```mermaid
---
config:
  class:
    hideEmptyMembersBox: true
---
classDiagram
direction LR
    namespace Ai {
        class AiClient {
            +generateResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiResult$
            +generateOperation(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiOperation$
            +generateTextResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiResult$
            +streamGenerateTextResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) Generator< GenerativeAiResult >$
            +generateImageResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiResult$
            +convertTextToSpeechResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiResult$
            +generateSpeechResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiResult$
            +generateEmbeddingsResult(string[]|Message[] $input, AiModel $model) EmbeddingResult$
            +generateTextOperation(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiOperation$
            +generateImageOperation(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiOperation$
            +convertTextToSpeechOperation(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiOperation$
            +generateSpeechOperation(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiOperation$
            +generateEmbeddingsOperation(string[]|Message[] $input, AiModel $model) EmbeddingOperation$
        }
    }
```

### Overview: API for AI extenders

This is a subset of the overall class diagram, purely focused on the API for AI extenders.

```mermaid
---
config:
  class:
    hideEmptyMembersBox: true
---
classDiagram
direction LR
    namespace Ai {
        class AiClient {
            +defaultRegistry() AiProviderRegistry$
            +isConfigured(AiProviderAvailability $availability) bool$
        }
    }
    namespace Ai.Providers {
        class AiProviderRegistry {
            +registerProvider(string $className) void
            +hasProvider(string $idOrClassName) bool
            +getProviderClassName(string $id) string
            +isProviderConfigured(string $idOrClassName) bool
            +getProviderModel(string $idOrClassName, string $modelId, AiModelConfig|array< string, mixed > $modelConfig) AiModel
            +findProviderModelsMetadataForSupport(string $idOrClassName, AiModelRequirements $modelRequirements) AiModelMetadata[]
            +findModelsMetadataForSupport(AiModelRequirements $modelRequirements) AiProviderModelMetadata[]
        }
    }

    AiClient "1" o-- "1..*" AiProviderRegistry
```

### Details: Class diagram for AI implementers

```mermaid
---
config:
  class:
    hideEmptyMembersBox: true
---
classDiagram
direction LR
    namespace Ai {
        class AiClient {
            +prompt(string|Message|null $text = null) PromptBuilder$
            +message(?string $text) MessageBuilder$
            +defaultRegistry() AiProviderRegistry$
            +isConfigured(AiProviderAvailability $availability) bool$
            +generateResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiResult$
            +generateOperation(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiOperation$
            +generateTextResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiResult$
            +streamGenerateTextResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) Generator< GenerativeAiResult >$
            +generateImageResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiResult$
            +convertTextToSpeechResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiResult$
            +generateSpeechResult(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiResult$
            +generateEmbeddingsResult(string[]|Message[] $input, AiModel $model) EmbeddingResult$
            +generateTextOperation(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiOperation$
            +generateImageOperation(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiOperation$
            +convertTextToSpeechOperation(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiOperation$
            +generateSpeechOperation(string|MessagePart|MessagePart[]|Message|Message[] $prompt, AiModel $model) GenerativeAiOperation$
            +generateEmbeddingsOperation(string[]|Message[] $input, AiModel $model) EmbeddingOperation$
        }

        class PromptBuilder {
            +withText(string $text) self
            +withInlineImage(string $base64Blob, string $mimeType)
            +withLocalImage(string $path, string $mimeType)
            +withRemoteImage(string $uri, string $mimeType)
            +withImageFile(File $file) self
            +withAudioFile(File $file) self
            +withVideoFile(File $file) self
            +withFunctionResponse(FunctionResponse $functionResponse) self
            +withMessageParts(...MessagePart $part) self
            +withHistory(...Message $messages) self
            +usingModel(AiModel $model) self
            +usingSystemInstruction(string|MessagePart[]|Message $systemInstruction) self
            +usingMaxTokens(int $maxTokens) self
            +usingTemperature(float $temperature) self
            +usingTopP(float $topP) self
            +usingTopK(int $topK) self
            +usingStopSequences(...string $stopSequences) self
            +usingCandidateCount(int $candidateCount) self
            +usingOutputMime(string $mimeType) self
            +usingOutputSchema(array< string, mixed > $schema) self
            +usingOutputModalities(...AiModality $modalities) self
            +asJsonResponse(?array< string, mixed > $schema) self
            +generateResult() GenerativeAiResult
            +generateOperation() GenerativeAiOperation
            +generateTextResult() GenerativeAiResult
            +streamGenerateTextResult() Generator< GenerativeAiResult >
            +generateImageResult() GenerativeAiResult
            +convertTextToSpeechResult() GenerativeAiResult
            +generateSpeechResult() GenerativeAiResult
            +generateEmbeddingsResult() EmbeddingResult
            +generateTextOperation() GenerativeAiOperation
            +generateImageOperation() GenerativeAiOperation
            +convertTextToSpeechOperation() GenerativeAiOperation
            +generateSpeechOperation() GenerativeAiOperation
            +generateEmbeddingsOperation() EmbeddingOperation
            +generateText() string
            +generateTexts(?int $candidateCount) string[]
            +streamGenerateText() Generator< string >
            +generateImage() File
            +generateImages(?int $candidateCount) File[]
            +convertTextToSpeech() File
            +convertTextToSpeeches(?int $candidateCount) File[]
            +generateSpeech() File
            +generateSpeeches(?int $candidateCount) File[]
            +generateEmbeddings() Embedding[]
            +getModelRequirements() AiModelRequirements
            +isSupported() bool
        }

        class MessageBuilder {
            +usingRole(MessageRole $role) self
            +withText(string $text) self
            +withImageFile(File $file) self
            +withAudioFile(File $file) self
            +withVideoFile(File $file) self
            +withFunctionCall(FunctionCall $functionCall) self
            +withFunctionResponse(FunctionResponse $functionResponse) self
            +withMessageParts(...MessagePart $part) self
            +get() Message
        }
    }
    namespace Ai.Types {
        class Message {
            +getRole() MessageRole
            +getParts() MessagePart[]
            +getJsonSchema() array< string, mixed >$
        }
        class MessagePart {
            +getType() MessagePartType
            +getText() string?
            +getInlineFile() InlineFile?
            +getRemoteFile() RemoteFile?
            +getFunctionCall() FunctionCall?
            +getFunctionResponse() FunctionResponse?
            +getJsonSchema() array< string, mixed >$
        }
        class File {
        }
        class InlineFile {
            +getMimeType() string
            +getBase64Data() string
            +getJsonSchema() array< string, mixed >$
        }
        class RemoteFile {
            +getMimeType() string
            +getUrl() string
            +getJsonSchema() array< string, mixed >$
        }
        class LocalFile {
            +getMimeType() string
            +getPath() string
            +getJsonSchema() array< string, mixed >$
        }
        class FunctionCall {
            +getId() string
            +getName() string
            +getArgs() array< string, mixed >
            +getJsonSchema() array< string, mixed >$
        }
        class FunctionResponse {
            +getId() string
            +getName() string
            +getResponse() mixed
            +getJsonSchema() array< string, mixed >$
        }
        class Embedding {
            +getVector() float[]
            +getDimension() int
        }
        class Operation {
            +getId() string
            +getState() OperationState
            +getJsonSchema() array< string, mixed >$
        }
        class GenerativeAiOperation {
            +getId() string
            +getState() OperationState
            +getResult() GenerativeAiResult
            +getJsonSchema() array< string, mixed >$
        }
        class EmbeddingOperation {
            +getId() string
            +getState() OperationState
            +getResult() EmbeddingResult
            +getJsonSchema() array< string, mixed >$
        }
        class Result {
            +getId() string
            +getTokenUsage() TokenUsage
            +getProviderMetadata() array< string, mixed >
            +getJsonSchema() array< string, mixed >$
        }
        class GenerativeAiResult {
            +getId() string
            +getCandidates() Candidate[]
            +getTokenUsage() TokenUsage
            +getProviderMetadata() array< string, mixed >
            +getJsonSchema() array< string, mixed >$
            %% The following utility methods transform the result candidates into a specific shape.
            +toText() string
            +toImageFile() File
            +toAudioFile() File
            +toVideoFile() File
            +toMessage() Message
            +toTexts() string[]
            +toImageFiles() File[]
            +toAudioFiles() File[]
            +toVideoFiles() File[]
            +toMessages() Message[]
        }
        class EmbeddingResult {
            +getId() string
            +getEmbeddings() Embedding[]
            +getTokenUsage() TokenUsage
            +getProviderMetadata() array< string, mixed >
            +getJsonSchema() array< string, mixed >$
        }
        class Candidate {
            +getMessage() Message
            +getFinishReason() FinishReason
            +getTokenCount() int
            +getJsonSchema() array< string, mixed >$
        }
        class TokenUsage {
            +getPromptTokens() int
            +getCompletionTokens() int
            +getTotalTokens() int
            +getJsonSchema() array< string, mixed >$
        }
    }
    namespace Ai.Types.Enums {
        class MessageRole {
            USER
            MODEL
            SYSTEM
        }
        class MessagePartType {
            TEXT
            INLINE_FILE
            REMOTE_FILE
            FUNCTION_CALL
            FUNCTION_RESPONSE
        }
        class FinishReason {
            STOP
            LENGTH
            CONTENT_FILTER
            TOOL_CALLS
            ERROR
        }
        class OperationState {
            STARTING
            PROCESSING
            SUCCEEDED
            FAILED
            CANCELED
        }
        class AiModality {
            TEXT
            DOCUMENT
            IMAGE
            AUDIO
            VIDEO
        }
    }
    namespace Ai.Util {
        class MessageUtil {
            +toText(Message $message) string$
            +toImageFile(Message $message) File$
            +toAudioFile(Message $message) File$
            +toVideoFile(Message $message) File$
        }
        class CandidatesUtil {
            +toTexts(Candidate[] $candidates) string[]$
            +toImageFiles(Candidate[] $candidates) File[]$
            +toAudioFiles(Candidate[] $candidates) File[]$
            +toVideoFiles(Candidate[] $candidates) File[]$
            +toFirstText(Candidate[] $candidates) string$
            +toFirstImageFile(Candidate[] $candidates) File$
            +toFirstAudioFile(Candidate[] $candidates) File$
            +toFirstVideoFile(Candidate[] $candidates) File$
        }
        class RequirementsUtil {
            +inferRequirements(Message[] $messages, AiModelConfig $modelConfig) AiModelRequirements$
        }
    }

    <<interface>> File
    <<interface>> Operation
    <<interface>> Result
    <<Enumeration>> MessageRole
    <<Enumeration>> MessagePartType
    <<Enumeration>> FinishReason
    <<Enumeration>> OperationState
    <<Enumeration>> AiModality

    AiClient .. Message : receives
    AiClient .. MessagePart : receives
    AiClient .. PromptBuilder : creates
    AiClient .. MessageBuilder : creates
    AiClient .. GenerativeAiResult : creates
    AiClient .. EmbeddingResult : creates
    AiClient .. GenerativeAiOperation : creates
    AiClient .. EmbeddingOperation : creates
    PromptBuilder .. GenerativeAiResult : creates
    PromptBuilder .. EmbeddingResult : creates
    PromptBuilder .. GenerativeAiOperation : creates
    PromptBuilder .. EmbeddingOperation : creates
    MessageBuilder .. Message : creates
    Message "1" *-- "1..*" MessagePart
    MessagePart "1" o-- "0..1" InlineFile
    MessagePart "1" o-- "0..1" RemoteFile
    MessagePart "1" o-- "0..1" FunctionCall
    MessagePart "1" o-- "0..1" FunctionResponse
    GenerativeAiOperation "1" o-- "0..1" GenerativeAiResult
    EmbeddingOperation "1" o-- "0..1" EmbeddingResult
    GenerativeAiResult "1" o-- "1..*" Candidate
    GenerativeAiResult "1" o-- "1" TokenUsage
    EmbeddingResult "1" o-- "1..*" Embedding
    EmbeddingResult "1" o-- "1" TokenUsage
    Candidate "1" o-- "1" Message
    Message ..> MessageRole
    MessagePart ..> MessagePartType
    Operation ..> OperationState
    GenerativeAiOperation ..> OperationState
    Candidate ..> FinishReason
    File <|-- InlineFile
    File <|-- RemoteFile
    File <|-- LocalFile
    Operation <|-- GenerativeAiOperation
    Operation <|-- EmbeddingOperation
    Result <|-- GenerativeAiResult
    Result <|-- EmbeddingResult
```

### Details: Class diagram for AI extenders

```mermaid
---
config:
  class:
    hideEmptyMembersBox: true
---
classDiagram
direction LR
    namespace Ai.Providers {
        class AiProviderRegistry {
            +registerProvider(string $className) void
            +hasProvider(string $idOrClassName) bool
            +getProviderClassName(string $id) string
            +isProviderConfigured(string $idOrClassName) bool
            +getProviderModel(string $idOrClassName, string $modelId, AiModelConfig|array< string, mixed > $modelConfig) AiModel
            +findProviderModelsMetadataForSupport(string $idOrClassName, AiModelRequirements $modelRequirements) AiModelMetadata[]
            +findModelsMetadataForSupport(AiModelRequirements $modelRequirements) AiProviderModelMetadata[]
        }
    }
    namespace Ai.Providers.Contracts {
        class AiProvider {
            +metadata() AiProviderMetadata$
            +model(string $modelId, AiModelConfig|array< string, mixed > $modelConfig) AiModel$
            +availability() AiProviderAvailability$
            +modelMetadataDirectory() AiModelMetadataDirectory$
        }
        class AiModel {
            +metadata() AiModelMetadata
            +setConfig(AiModelConfig $config) void
            +getConfig() AiModelConfig
        }
        class AiProviderAvailability {
            +isConfigured() bool
        }
        class AiModelMetadataDirectory {
            +listModelMetadata() AiModelMetadata[]
            +hasModelMetadata(string $modelId) bool
            +getModelMetadata(string $modelId) AiModelMetadata
        }
        class WithGenerativeAiOperations {
            +getOperation(string $operationId) GenerativeAiOperation
        }
        class WithEmbeddingOperations {
            +getOperation(string $operationId) EmbeddingOperation
        }
        class AiTextGenerationModel {
            +generateTextResult(Message[] $prompt) GenerativeAiResult
            +streamGenerateTextResult(Message[] $prompt) Generator< GenerativeAiResult >
        }
        class AiImageGenerationModel {
            +generateImageResult(Message[] $prompt) GenerativeAiResult
        }
        class AiTextToSpeechConversionModel {
            +convertTextToSpeechResult(Message[] $prompt) GenerativeAiResult
        }
        class AiSpeechGenerationModel {
            +generateSpeechResult(Message[] $prompt) GenerativeAiResult
        }
        class AiEmbeddingGenerationModel {
            +generateEmbeddingsResult(Message[] $input) EmbeddingResult
        }
        class AiTextGenerationOperationModel {
            +generateTextOperation(Message[] $prompt) GenerativeAiOperation
        }
        class AiImageGenerationOperationModel {
            +generateImageOperation(Message[] $prompt) GenerativeAiOperation
        }
        class AiTextToSpeechConversionOperationModel {
            +convertTextToSpeechOperation(Message[] $prompt) GenerativeAiOperation
        }
        class AiSpeechGenerationOperationModel {
            +generateSpeechOperation(Message[] $prompt) GenerativeAiOperation
        }
        class AiEmbeddingGenerationOperationModel {
            +generateEmbeddingsOperation(Message[] $input) EmbeddingOperation
        }
        class WithHttpClient {
            +setHttpClient(HttpClient $client) void
            +getHttpClient() HttpClient
        }
        class HttpClient {
            +send(RequestInterface $request, array< string, mixed > $options) ResponseInterface
            +request(string $method, string $uri, array< string, mixed > $options) ResponseInterface
        }
        class WithAuthentication {
            +setAuthentication(Authentication $authentication) void
            +getAuthentication() Authentication
        }
        class Authentication {
            +authenticate(RequestInterface $request) void
            +getJsonSchema() array< string, mixed >$
        }
    }
    namespace Ai.Providers.Types {
        class AiProviderMetadata {
            +getId() string
            +getName() string
            +getType() AiProviderType
            +getJsonSchema() array< string, mixed >$
        }
        class AiModelMetadata {
            +getId() string
            +getName() string
            +getSupportedCapabilities() AiCapability[]
            +getSupportedOptions() AiSupportedOption[]
            +getJsonSchema() array< string, mixed >$
        }
        class AiProviderModelsMetadata {
            +getProvider() AiProviderMetadata
            +getModels() AiModelMetadata[]
            +getJsonSchema() array< string, mixed >$
        }
        class AiModelRequirements {
            getRequiredCapabilities() AiCapability[]
            getRequiredOptions() AiRequiredOption[]
        }
        class AiModelConfig {
            +setOutputModalities(AiModality[] $modalities) void
            +getOutputModalities() AiModality[]
            +setSystemInstruction(string|MessagePart|MessagePart[]|Message $systemInstruction) void
            +getSystemInstruction() Message?
            +setCandidateCount(int $candidateCount) void
            +getCandidateCount() int
            +setMaxTokens(int $maxTokens) void
            +getMaxTokens() int
            +setTemperature(float $temperature) void
            +getTemperature() float
            +setTopK(int $topK) void
            +getTopK() int
            +setTopP(float $topP) void
            +getTopP() float
            +setOutputMimeType(string $outputMimeType) void
            +getOutputMimeType() string
            +setOutputSchema(array< string, mixed > $outputSchema) void
            +getOutputSchema() array< string, mixed >
            +setCustomOption(string $key, mixed $value) void
            +getCustomOption(string $key) mixed
            +getCustomOptions() array< string, mixed >
            +setTools(Tool[] $tools) void
            +getTools() Tool[]
            +getJsonSchema() array< string, mixed >$
        }
        class Tool {
            +getType() ToolType
            +getFunctionDeclarations() FunctionDeclaration[]?
            +getWebSearch() WebSearch?
            +getJsonSchema() array< string, mixed >$
        }
        class FunctionDeclaration {
            +getName() string
            +getDescription() string
            +getParameters() mixed
            +getJsonSchema() array< string, mixed >$
        }
        class WebSearch {
            +getAllowedDomains() string[]
            +getDisallowedDomains() string[]
            +getJsonSchema() array< string, mixed >$
        }
        class AiSupportedOption {
            +getName() string
            +isSupportedValue(mixed $value) bool
            +getSupportedValues() mixed[]
            +getJsonSchema() array< string, mixed >$
        }
        class AiRequiredOption {
            +getName() string
            +getValue() mixed
            +getJsonSchema() array< string, mixed >$
        }
    }
    namespace Ai.Providers.Enums {
        class AiProviderType {
            CLOUD
            SERVER
            CLIENT
        }
        class ToolType {
            FUNCTION_DECLARATIONS
            WEB_SEARCH
        }
        class AiCapability {
            TEXT_GENERATION
            IMAGE_GENERATION
            TEXT_TO_SPEECH
            SPEECH_GENERATION
            MUSIC_GENERATION
            VIDEO_GENERATION
            EMBEDDING_GENERATION
            CHAT_HISTORY
        }
        class AiOption {
            INPUT_MODALITIES
            OUTPUT_MODALITIES
            SYSTEM_INSTRUCTION
            CANDIDATE_COUNT
            MAX_TOKENS
            TEMPERATURE
            TOP_K
            TOP_P
            OUTPUT_MIME_TYPE
            OUTPUT_SCHEMA
        }
    }
    namespace Ai.Providers.Util {
        class AiCapabilitiesUtil {
            +getSupportedCapabilities(AiModel|string $modelClass) AiCapability[]$
            +getSupportedOptions(AiModel|string $modelClass) AiSupportedOption[]$
        }
    }

    <<interface>> AiProvider
    <<interface>> AiModel
    <<interface>> AiProviderAvailability
    <<interface>> AiModelMetadataDirectory
    <<interface>> WithGenerativeAiOperations
    <<interface>> WithEmbeddingOperations
    <<interface>> AiTextGenerationModel
    <<interface>> AiImageGenerationModel
    <<interface>> AiTextToSpeechConversionModel
    <<interface>> AiSpeechGenerationModel
    <<interface>> AiEmbeddingGenerationModel
    <<interface>> AiTextGenerationOperationModel
    <<interface>> AiImageGenerationOperationModel
    <<interface>> AiTextToSpeechConversionOperationModel
    <<interface>> AiSpeechGenerationOperationModel
    <<interface>> AiEmbeddingGenerationOperationModel
    <<interface>> WithHttpClient
    <<interface>> HttpClient
    <<interface>> WithAuthentication
    <<interface>> Authentication
    <<Enumeration>> AiCapability
    <<Enumeration>> AiOption
    <<Enumeration>> AiProviderType

    AiProvider .. AiModel : creates
    AiProvider "1" *-- "1" AiProviderMetadata
    AiProvider "1" *-- "1" AiProviderAvailability
    AiProvider "1" *-- "1" AiModelMetadataDirectory
    AiModel "1" *-- "1" AiModelMetadata
    AiModel "1" *-- "1" AiModelConfig
    AiProviderModelsMetadata "1" o-- "1" AiProviderMetadata
    AiProviderModelsMetadata "1" o-- "1..*" AiModelMetadata
    AiProviderRegistry "1" o-- "0..*" AiProvider
    AiProviderRegistry "1" o-- "0..*" AiProviderMetadata
    AiModelMetadataDirectory "1" o-- "1..*" AiModelMetadata
    AiModelMetadata "1" o-- "1..*" AiCapability
    AiModelMetadata "1" o-- "0..*" AiSupportedOption
    AiModelRequirements "1" o-- "1..*" AiCapability
    AiModelRequirements "1" o-- "0..*" AiRequiredOption
    AiModelConfig "1" o-- "0..*" Tool
    Tool "1" o-- "0..*" FunctionDeclaration
    Tool "1" o-- "0..1" WebSearch
    AiProviderMetadata ..> AiProviderType
    AiModelMetadata ..> AiCapability
    AiModelMetadata ..> AiSupportedOption
    AiModel <|-- AiTextGenerationModel
    AiModel <|-- AiImageGenerationModel
    AiModel <|-- AiTextToSpeechConversionModel
    AiModel <|-- AiSpeechGenerationModel
    AiModel <|-- AiEmbeddingGenerationModel
    AiModel <|-- AiTextGenerationOperationModel
    AiModel <|-- AiImageGenerationOperationModel
    AiModel <|-- AiTextToSpeechConversionOperationModel
    AiModel <|-- AiSpeechGenerationOperationModel
    AiModel <|-- AiEmbeddingGenerationOperationModel
```

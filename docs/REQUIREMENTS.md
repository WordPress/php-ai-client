# Requirements

This document outlines the functional requirements for the PHP AI Client, as well as the bigger picture for how it can eventually be used. The concrete technical architecture is defined and outlined in a separate document, based on these requirements.

## Target Audiences

There are two primary developer audiences this client is intended for. This is important to understand as it significantly influences the thinking and complexity around the APIs introduced in this library.

### Extenders

Extenders are the folks that will be adding providers, models, and otherwise extending the functionality of the client itself. These are highly technical people who likely have a stronger understanding of how models and model APIs work. Given their capabilities, these APIs will be more technical and formal in nature, using things such as interfaces, traits, and so forth, relying on a knowledge of inheritance and composition.

### Implementers

Implementers are the folks that will be utilizing the client to take advantage of AI features. These developers know their own codebase well, but their technical and model knowledge varies. It is important not to rely on this knowledge for them to get significant value from the client. The APIs for these people will be simpler, straightforward, readable, and composable, so they can interact with the model with only what they need to know in mind.

## Objective

Enable calling any generative AI implementation using a uniform API in various programming languages.

### Context

* While the initial rationale for building a provider agnostic AI client abstraction for the WordPress AI Team was naturally the lack of such an abstraction being available for WordPress, further research showed that this gap also exists in other PHP CMSs, and even the overall PHP ecosystem.
* In other words, the background for starting this project is the lack of such an SDK in the PHP ecosystem. Since UI on the web today heavily relies on JavaScript (in addition to whichever server-side language), the SDK's API needs to be centered in PHP, but accessible via JavaScript as well (e.g. through a REST API).
* The PHP AI Client (this project) will only provide the foundational PHP layer, in a platform agnostic way. Additional future packages or plugins will be implemented to cover CMS specific aspects and the JavaScript layer.
* While a few noteworthy projects with a related purpose exist in various programming languages, they are not in PHP, or their API is not provider agnostic, or their API lacks flexibility for emerging modalities and features.
* Ideally, the APIs in this project can eventually be translated from PHP to client-side JavaScript. As such, the project should follow paradigms that can be expressed in both programming languages.

## Architecture requirements

This section lists the key requirements that this project must meet. For explanation on specific terms, see the [glossary](./GLOSSARY.md).

* MUST support any kinds of AI implementation, i.e. cloud-based AI, server-side AI, client-side AI.
* MUST define clear data structures for AI inputs and outputs, precise enough to be reusable in other programming languages (e.g. an eventual client-side JavaScript implementation of the API).
* MUST support arbitrary combinations of input and output modalities, regardless of which combinations or singular modalities generative AI models support today. Such as (non comprehensive list):
  * Text
  * Image
  * Audio
  * Video
* MUST support additional non-generative features such as (non comprehensive list):
  * Classification
  * Text to Speech
  * Embedding
* MUST support response streaming for arbitrary output modalities.
* MUST allow for long-running operations that may take several minutes, _if_ relevant for the selected provider.
* MUST define standard ways for interacting with optional provider capabilities, such as managing chat history or specifying multimodal inputs/outputs, _if_ the selected provider supports them.
* MUST support diverse common model parameters, such as temperature, top P, or image aspect ratio, with uniform names and behavior across providers, _if_ the selected provider supports them.
* MUST define a modular component model that allows for the addition of new providers, models, and features without modifying core functionality.
* MUST define an API for external packages to register and implement AI model providers.
* MUST be decoupled from any AI provider's implementation details (e.g. not all providers require HTTP requests or API authentication).
* MUST allow provider and model discovery based on specific inputs, outputs, and configuration options supported.
* MUST define data types and interfaces that have direct equivalents in all supported languages (e.g. no multiple inheritance for classes).
* MUST define separate APIs for SDK usage and provider registration, so that iterations or breaking changes in one don't automatically affect the other.

### Best practices

* SHOULD use concepts and paradigms so that they can be applied in other AI infrastructure projects, either in combination or separate from the PHP AI Client (e.g. MCP, real-time AI abstraction, prompt generation).
* SHOULD provide middleware that can be used to "polyfill" certain functionality when a provider does not support it (e.g. message history, downloading files from URLs).
* SHOULD allow for arbitrary request and response parameters for specific providers or models to be passed through even when not formally supported, to cater for provider specific features or to allow for newly added features to be used before official support is added to the SDK.

### Out of scope

* MUST NOT include any common AI features beyond the actual AI client (e.g. no MCP, no agents).
* MUST NOT include a real-time / live AI abstraction as it requires different infrastructure.

## Credit

This project is heavily based on researching existing AI providers and existing AI client SDKs, and it takes significant learnings from these into account for the specification. All of these products helped inform the aforementioned requirements.

Below is a list of products that were reviewed. The list is non comprehensive, based on a best-effort approach to include all key resources reviewed. It is not an endorsement of any of these products.

### Cloud-based AI providers

_(in alphabetical order)_

* [Anthropic API](https://platform.claude.com/docs/en/api/)
* [fal API](https://fal.ai/docs/model-endpoints)
* [Google Generative Language API](https://ai.google.dev/api/all-methods)
* [Google Vertex AI API](https://docs.cloud.google.com/vertex-ai/docs/reference/rest)
* [Nvidia LLM API](https://docs.api.nvidia.com/nim/reference/llm-apis)
* [OpenAI API](https://developers.openai.com/api/reference/overview)
* [Perplexity API](https://docs.perplexity.ai/api-reference/)
* [Replicate API](https://replicate.com/docs/reference/http)
* [X AI API](https://docs.x.ai/developers/rest-api-reference/inference/chat)

### AI client SDKs

_(in alphabetical order)_

* [AI Services WordPress plugin](https://github.com/felixarntz/ai-services)
* [Drupal AI](https://git.drupalcode.org/project/ai)
* [Firebase Genkit](https://github.com/genkit-ai/genkit)
* [Google Gen AI SDK for TypeScript and JavaScript](https://github.com/googleapis/js-genai)
* [LangChain.js](https://github.com/langchain-ai/langchainjs)
* [LLPhant](https://github.com/LLPhant/LLPhant)
* [OpenAI PHP](https://github.com/openai-php/client)
* [Prism (Laravel)](https://github.com/prism-php/prism)
* [Vercel AI SDK](https://github.com/vercel/ai)

### AI specifications

_(in alphabetical order)_

* [A2A protocol](https://github.com/a2aproject/A2A)
* [MCP](https://github.com/modelcontextprotocol/modelcontextprotocol)
* [OpenAI model spec](https://cdn.openai.com/spec/model-spec-2024-05-08.html)

### Client-side AI

_(in alphabetical order)_

* [Chrome built-in AI Prompt API](https://github.com/webmachinelearning/prompt-api)
* [transformers.js](https://github.com/huggingface/transformers.js/)

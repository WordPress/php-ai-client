# Glossary

This glossary defines common terms relevant for the PHP AI Client and related projects.

* **Agent**: An autonomous system that can perceive its environment, make decisions, and take actions to achieve specific goals, often leveraging AI models.
* **Candidate Count**: The number of different response options an LLM generates internally.
* **Capability**: A specific skill, function, or type of task that an AI model can perform, e.g. text generation or image generation.
* **Extender API**: The API used by developers that want to enable the use of additional _Providers_ or _Models_.
* **Generative AI**: Overaching term describing AI models that generate content as requested in a prompt.
* **Implementer API**: The API used by people that want to _implement_ AI features in their own software/products.
* **MCP**: The "Model Context Protocol", a proposed standard for connecting AI assistants to the systems where data lives.
* **Message**: A single message, either a user prompt, a model response, or a system prompt—optionally containing of multiple message parts.
* **Message part**: A part of a message, such as a piece of text, a URL, a file, or a function call.
* **Modality**: The type or format of input provided to, or output received from, an AI model. Examples include text, image, audio, and video.
* **Model**: A specific AI model that supports arbitrary AI features and modalities. Examples include content generation, classification, embedding.
* **Option**: AI configuration and output options for a model, e.g. temperature or output schema.
* **Prompt**: The input that a generative AI model uses to generate content—often text, but it can also be of other modalities.
* **Provider**: An entity (company, organization, or platform) that offers access to one or more AI models or services via an API (e.g., Anthropic, Google, OpenAI, a locally hosted service).
* **Provider Registry**: Manages the available AI _Providers_ and _Models_. Provides a mechanism to find models that match criteria for a given AI interaction.

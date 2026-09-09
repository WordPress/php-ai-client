# Provider-native conversation data

`ProviderData` lets a provider retain a native conversation item as an ordered
`MessagePart` without adding that provider's wire format to the SDK. It is opaque
to the core client. Array/JSON serialization and cloning preserve it alongside
ordinary text and function-call parts.

Providers must check the originating provider ID and validate the payload before
replaying it. A provider ID is an ownership label, not authentication: persisted
messages and tool execution still need application-level authorization. Providers
that do not recognize this part type must not interpret it as text or execute it
as an application function call.

Applications should preserve all returned message parts when saving history.
Converting a result to text loses non-text conversation state.

## Why this small core extension?

The motivating consumer is hosted tool search in the OpenAI provider. The provider
can choose an automatic tool-count threshold, emit `defer_loading` and
`tool_search`, and consume policy overrides through the existing
`ModelConfig::setCustomOption()` API. None of that needs a core tool-search option,
a `FunctionDeclaration` flag, or a `PromptBuilder::usingToolSearch()` method.

Request parameters alone do not preserve the search call and loaded-tool output
across stateless, serialized message history. Result-level provider metadata is
not retained by message conversion; thought signatures describe reasoning, not
tool discovery. `ProviderData` addresses only that conversation-state gap.

Using `previous_response_id` through custom options is a zero-core-change
alternative when the application deliberately uses OpenAI-managed conversation
state. It requires the application to retain response IDs and send only new input,
and is not a substitute for portable, stateless message history.

## Research (2026-09-09)

- [OpenAI tool search](https://developers.openai.com/api/docs/guides/tools-tool-search)
  supports hosted discovery on GPT-5.4 and later compatible Responses models.
  Deferred flat functions retain their names/descriptions in context and defer
  mainly parameter schemas. Namespaces can yield larger savings, but should not
  be invented automatically without meaningful grouping metadata.
- [Vercel AI SDK v7 OpenAI provider](https://ai-sdk.dev/providers/ai-sdk-providers/openai)
  uses an explicit provider tool plus per-function `providerOptions.openai.deferLoading`.
  It preserves provider-specific replay metadata. It does not document an automatic
  tool-count activation threshold.
- [Vercel Anthropic provider](https://ai-sdk.dev/providers/ai-sdk-providers/anthropic)
  likewise exposes provider-defined search tools and deferred-loading options.

An automatic threshold such as more than 10 functions is a provider experiment,
not a cross-provider capability guarantee or a demonstrated performance win.
Measure task success, total tokens, and end-to-end latency against eager loading
before promoting that policy or further tool-search APIs into core.

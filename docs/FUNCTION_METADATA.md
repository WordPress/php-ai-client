# Function declaration metadata

`FunctionDeclaration` accepts an optional fourth constructor argument containing
generic metadata (annotations):

```php
$function = new FunctionDeclaration(
    'get_weather',
    'Gets the weather',
    null,
    ['deferredLoading' => true]
);

$metadata = $function->getMetadata();
```

Metadata is an `array<string, mixed>` whose values should be JSON-serializable.
The SDK preserves it during declaration and model-configuration serialization
without interpreting annotation names or values. Empty metadata is omitted from
serialized declarations, preserving the existing shape for callers that do not
use this argument. Missing metadata is restored as an empty array.

Providers and other consumers define which annotations they recognize, their value
types, and their interaction with request-level custom options. Unknown annotations
can be ignored. Provider-specific annotations should use a namespaced key or nested
provider-specific map to avoid collisions. Metadata must not be blindly merged into
a provider request or the function's JSON parameter schema.

For example, a provider can interpret a `deferredLoading` annotation together with
`ModelConfig::setCustomOptions(['deferredLoading' => true])`. This example does not
establish a core deferred-loading capability, guarantee provider support, or add a
model-selection requirement. Automatic tool-count thresholds remain provider policy.
Annotations are not authorization; applications must still validate tool execution.

This extension concerns outgoing function definitions only. It adds no message
types or native response replay mechanism. An OpenAI provider experiment can use
existing `previous_response_id` custom options and send only new input for
server-managed continuation; stateless replay of discovery items remains separate
work requiring further evidence.

The [Vercel AI SDK OpenAI provider](https://ai-sdk.dev/providers/ai-sdk-providers/openai)
uses per-tool `providerOptions` for comparable provider-owned configuration. Its
documented deferred-loading API is explicit, rather than count-triggered. Generic
metadata provides an extension point for exploring such features without adding
feature-specific properties or fluent builder methods to this SDK.

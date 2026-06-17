# AIProviders

Configure AI provider connections and default model settings used by Matomo AI features.

Built-in providers: Claude, OpenAI, Gemini, and a generic custom provider for OpenAI-compatible endpoints.

## Configuration

Settings are managed from **Administration > System > AI Providers**.

The plugin stores the default provider, default capability level, and provider connection settings as Matomo system settings (not shown on the generic plugin settings page). API keys are only returned to the administration UI as masked state, not as secret values.

On a managed environment, the default provider is forced and locked via the `[AIProviders] defaultProvider` config setting. There is nothing left to configure, so the AI Providers settings page and its admin menu entry are hidden entirely.

### Custom provider and local LLM servers

The generic custom provider talks to any **OpenAI-compatible** Chat Completions API. This includes hosted OpenAI-compatible services as well as local LLM servers such as Ollama, LM Studio, llama.cpp (`llama-server`), vLLM and LocalAI, which all expose the same `/v1/chat/completions` wire format.

Two things matter when configuring it:

- **API base URL.** Enter the URL up to and including the OpenAI-compatible API root — for most servers that is the `/v1` path. Matomo appends `/chat/completions` itself, so do not include it. Examples:
  - Ollama: `http://localhost:11434/v1`
  - LM Studio: `http://localhost:1234/v1`
  - vLLM / llama.cpp: `http://localhost:8000/v1`

  Note that the request leaves the Matomo server, not the browser. If Matomo runs in a container (e.g. Docker/ddev), `localhost` refers to the container — use the host gateway such as `host.docker.internal` instead.

- **API key.** Optional for the custom provider. Many local servers run without authentication, so the key may be left blank; it is only sent (as `Authorization: Bearer …`) when provided.

- **Model.** The "test connection" action probes `GET {base}/models` (rather than spending generation tokens) and populates the model picker from the result; the refresh button re-runs it. Pick the model to use, or type a name when the server does not expose a listing. The custom provider has **no built-in default model** — the model sent to the server is the per-request model (`AIRequest::withModel()`) if set, otherwise the saved configuration model. If neither is set, completions fail with a clear "no model configured" error rather than silently calling a wrong model.

### Config-file credentials

Provider connection settings can also be supplied from the `[AIProviders]` config section or environment variables instead of the administration UI. Per field, a config-file/environment value wins over the database value:

```ini
[AIProviders]
openaiApiKey = "..."       ; or env MATOMO_AIPROVIDERS_OPENAI_API_KEY
openaiEndpointUrl = "..."  ; or env MATOMO_AIPROVIDERS_OPENAI_ENDPOINT_URL
```

The config key is the provider ID verbatim plus the field suffix; the environment variable upper-cases the ID and replaces `-` with `_`. Credentials supplied this way never appear in the UI as secret values and cannot be edited or removed there.

### Restricted providers and the provider selection allowlist

A managed environment can demote providers to *restricted* (non-selectable) in the `AIProviders.filterAIProviders` event via `AIProvidersList::setSelectable()`. Restricted providers are hidden from every admin surface and can never become the default, but stay registered for completions.

Plugins listed on the centrally managed allowlist may target a specific provider and model per request even though the default provider is forced — needed by features whose purpose is to query specific AI engines:

```ini
[AIProviders]
defaultProvider = "openai"
providerSelectionAllowlist[] = "ExamplePlugin"
```

In a managed environment this config is locked and centrally managed, so neither the allowlist nor the providers it unlocks can be influenced by users. Allowlisted callers get no silent fallback: requesting an unknown or unconfigured provider fails with a clear error rather than answering from the wrong engine.

## Usage from other plugins

Do not call this plugin's `API.php` from other plugins. Its public API methods exist only for the administration UI and only expose masked configuration values — adding prompt/completion methods there would expose them over Matomo's HTTP API to anyone with a valid token.

Use the PHP service instead:

```php
use Piwik\Container\StaticContainer;
use Piwik\Plugins\AIProviders\AIRequest;
use Piwik\Plugins\AIProviders\AIProviderService;

$service = StaticContainer::get(AIProviderService::class);
$response = $service->complete(new AIRequest('why is the sky blue, answer in 7 words', 'YourPlugin'));
$text = $response->getText();
```

Build the request with `AIRequest`: the first argument is the user prompt, the second is your plugin name (used for accountability). Optional settings such as a system prompt, model, max tokens, or temperature are set with the immutable `with*()` methods. The service resolves the provider (honouring a provider forced by a managed environment), calls it, and returns an `AIProviderResponse` with the completion text and, when the provider reports it, token usage.

Credentials are resolved and used server-side by the service and are never returned to callers. Built-in providers default to low-latency models, which can be overridden per request with `AIRequest::withModel()` on self-managed instances. When a managed environment forces a provider from configuration, the service also ignores caller-provided provider and model overrides — unless the calling plugin is on the `providerSelectionAllowlist` (see above), in which case its requested provider and model are honoured.

**Hard rule:** the values passed to `withProviderId()` and `withModel()` must come from plugin code constants or server-side configuration, never from request input. Forwarding request parameters would let any HTTP client pick the provider and model the server calls — on managed hosting that circumvents the centrally managed provider and its cost controls. If users may choose between engines in a UI, the request parameter must be a key into a server-side map defined by your plugin, never the provider/model string itself.

`AIProviderResponse` exposes the provider, model, completion text, token usage, raw decoded provider response, reasoning level used, web-search usage, and execution time. Reasoning controls, web search, and thinking budgets are currently contract stubs only: callers may set them on `AIRequest`, but the built-in providers do not send provider-specific reasoning or web-search options yet and report `none` / `false` in the response.

### JSON mode

For structured output, call `withJsonResponse()` and read the decoded object with `getJsonData()`:

```php
$response = $service->complete(
    (new AIRequest($prompt, 'Goals'))->withJsonResponse()
);
$data = $response->getJsonData(); // array, or null if the model did not return valid JSON
```

Each provider asks for JSON the best way it can — `response_format` (OpenAI-compatible), `responseMimeType` (Gemini) — and AIProviders always adds a system instruction to return a single JSON object, so it works for providers without a native option (for example Claude) too. The model can still occasionally return invalid JSON, so always handle a `null` from `getJsonData()`.

## Conversations (multi-turn, tool calling)

`complete()` is prompt-in/text-out. When your plugin maintains a back-and-forth conversation and dispatches tool calls itself (as AskMatomo does), use `AIConversationRequest` with `AIProviderService::converse()` instead. The service resolves the provider exactly like `complete()` (forced provider, allowlisted caller selection, then the configured default) and returns one assistant turn per call.

```php
use Piwik\Container\StaticContainer;
use Piwik\Plugins\AIProviders\AIConversationRequest;
use Piwik\Plugins\AIProviders\AIConversationResponse;
use Piwik\Plugins\AIProviders\AIProviderService;

$service = StaticContainer::get(AIProviderService::class);

$messages = [
    ['role' => 'user', 'content' => [['type' => 'text', 'text' => 'How many visits yesterday?']]],
];

$response = $service->converse(
    (new AIConversationRequest($messages, 'YourPlugin'))
        ->withSystemPrompt($systemPrompt)
        ->withTools($toolCatalog) // optional; MCP-aligned shape, see below
        ->withMaxTokens(2048)
);

if ($response->getStopReason() === AIConversationResponse::STOP_TOOL_USE) {
    // Run the requested tool_use blocks, append this turn and the tool
    // results to $messages, and call converse() again.
}
```

Messages, tools, and the assistant's content all use one **provider-agnostic canonical shape** documented in [`CanonicalMessage`](CanonicalMessage.php). Each provider translates that shape to and from its own wire format inside `converse()`, so swapping the configured provider never changes how your plugin builds or stores a conversation. Running tools and appending their results to the history for the next call is the caller's responsibility — the service performs a single round-trip per call.

`AIConversationResponse` exposes the assistant content blocks (`getContent()`), the stop reason mapped onto the `STOP_*` constants (`getStopReason()`), a text convenience (`getText()`), token usage, and the raw decoded provider response. Treat an unknown stop reason like `STOP_END_TURN`. Unlike `complete()`, an empty text turn is valid here: a turn may consist solely of `tool_use` blocks.

Not every provider supports conversations. Gate conversational features on availability rather than calling `converse()` blindly:

```php
if (!$service->canConverse()) {
    // Hide or disable the feature.
}
// When the reason matters for the UI:
$availability = $service->getConversationAvailability(); // status + resolved provider
```

The same **hard rule** as for `AIRequest` applies: the values passed to `withProviderId()` and `withModel()` must come from plugin code constants or server-side configuration, never from request input.

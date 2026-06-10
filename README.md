# AIProviders

Configure AI provider connections and default model settings used by Matomo AI features.

Built-in providers: Claude, OpenAI, Gemini, and a generic custom provider for OpenAI-compatible endpoints.

## Configuration

Settings are managed from **Administration > System > AI Providers**.

The plugin stores the default provider, default capability level, and provider connection settings as Matomo system settings (not shown on the generic plugin settings page). API keys are only returned to the administration UI as masked state, not as secret values.

On a managed environment (for example Matomo Cloud), the default provider is forced and locked via the `[AIProviders] defaultProvider` config setting. There is nothing left to configure, so the AI Providers settings page and its admin menu entry are hidden entirely.

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

Credentials are resolved and used server-side by the service and are never returned to callers. Built-in providers default to low-latency models, which can be overridden per request with `AIRequest::withModel()`.

### JSON mode

For structured output, call `withJsonResponse()` and read the decoded object with `getJsonData()`:

```php
$response = $service->complete(
    (new AIRequest($prompt, 'Goals'))->withJsonResponse()
);
$data = $response->getJsonData(); // array, or null if the model did not return valid JSON
```

Each provider asks for JSON the best way it can — `response_format` (OpenAI-compatible), `responseMimeType` (Gemini) — and AIProviders always adds a system instruction to return a single JSON object, so it works for providers without a native option (Claude, Bedrock) too. The model can still occasionally return invalid JSON, so always handle a `null` from `getJsonData()`.

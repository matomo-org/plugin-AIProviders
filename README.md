# AIProviders

Configure AI provider connections and default model settings used by Matomo AI features.

Built-in providers: Claude, OpenAI, Gemini, and a generic custom provider for OpenAI-compatible endpoints.

## Usage from other plugins

Do not call this plugin's `API.php` from other plugins. Its public API methods exist only for the administration UI and only expose masked configuration values — adding prompt/completion methods there would expose them over Matomo's HTTP API to anyone with a valid token.

Use the PHP service instead:

```php
use Piwik\Container\StaticContainer;
use Piwik\Plugins\AIProviders\AIProviderService;

$service = StaticContainer::get(AIProviderService::class);
$provider = $service->getDefaultProvider();
$configuration = $service->getDefaultProviderConfiguration();
$capabilityLevel = $service->getDefaultCapabilityLevel();
```

`$configuration` may include secrets and must not be logged, returned from API methods, or rendered in browser output.

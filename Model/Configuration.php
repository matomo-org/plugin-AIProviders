<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders\Model;

use InvalidArgumentException;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Settings\FieldConfig;
use Piwik\Settings\Plugin\SystemSetting;
use Piwik\Plugins\AIProviders\AIProvidersList;
use Piwik\Plugins\AIProviders\Provider\AIProvider;

/**
 * Stores and resolves the AI provider configuration.
 *
 * Provider connection settings are merged per field in this order:
 *
 * 1. Namespaced DI values supplied by the instance owner:
 *
 *        AIProviders.openaiApiKey
 *
 * 2. The `[AIProviders]` section of `config.ini.php`, or environment variables:
 *
 *        [AIProviders]
 *        openaiApiKey = "..."       ; or env MATOMO_AIPROVIDERS_OPENAI_API_KEY
 *        openaiEndpointUrl = "..."  ; or env MATOMO_AIPROVIDERS_OPENAI_ENDPOINT_URL
 *
 * 3. The database, written from the administration UI.
 *
 * The DI/config-file sources are how a managed environment supplies credentials.
 */
class Configuration
{
    public const SETTING_DEFAULT_PROVIDER = 'defaultProvider';
    public const SETTING_DEFAULT_CAPABILITY_LEVEL = 'defaultCapabilityLevel';
    public const SETTING_PROVIDER_CREDENTIALS = 'providerCredentials';

    /**
     * Key in the `[AIProviders]` config section listing the plugins that may
     * target a specific provider per request even when a managed environment
     * forces the default provider. See {@link isPluginAllowedToSelectProvider()}.
     */
    public const CONFIG_PROVIDER_SELECTION_ALLOWLIST = 'providerSelectionAllowlist';

    public const CAPABILITY_INSTANT = 'instant';
    public const CAPABILITY_THINKING = 'thinking';

    private const PLUGIN_NAME = 'AIProviders';
    private const DEFAULT_PROVIDER_ID = 'openai';

    /**
     * Stored default provider ID. Persisted in the plugin settings storage, but
     * not shown on the generic plugin settings page (the settings are not
     * registered in a settings container). The value can be overridden and
     * locked from `config.ini.php`, which is how managed environments force
     * a provider:
     *
     *     [AIProviders]
     *     defaultProvider = "openai"
     *
     * @var SystemSetting
     */
    private $defaultProvider;

    /**
     * @var SystemSetting
     */
    private $defaultCapabilityLevel;

    /**
     * Per-provider connection settings keyed by provider ID, for example
     * `['openai' => ['apiKey' => '...', 'endpointUrl' => '']]`.
     *
     * @var SystemSetting
     */
    private $providerCredentials;

    public function __construct()
    {
        $this->defaultProvider = new SystemSetting(
            self::SETTING_DEFAULT_PROVIDER,
            '',
            FieldConfig::TYPE_STRING,
            self::PLUGIN_NAME
        );
        $this->defaultCapabilityLevel = new SystemSetting(
            self::SETTING_DEFAULT_CAPABILITY_LEVEL,
            self::CAPABILITY_INSTANT,
            FieldConfig::TYPE_STRING,
            self::PLUGIN_NAME
        );
        $this->providerCredentials = new SystemSetting(
            self::SETTING_PROVIDER_CREDENTIALS,
            [],
            FieldConfig::TYPE_ARRAY,
            self::PLUGIN_NAME
        );
    }

    /**
     * Returns masked AI provider settings for the administration UI.
     *
     * Only selectable providers are included, so providers a managed
     * environment registered as restricted (usable by allowlisted plugins
     * only) are never revealed to admin surfaces.
     *
     * @return array{
     *     defaultProviderId: string,
     *     defaultCapabilityLevel: string,
     *     canEditProviderConfiguration: bool,
     *     canEditCapabilityLevel: bool,
     *     capabilityLevels: array<string, array{label: string, description: string}>,
     *     providers: array<int, array<string, mixed>>
     * } Provider metadata and masked configuration values.
     */
    public function getSettings(AIProvidersList $providers): array
    {
        return [
            'defaultProviderId' => $this->getDefaultProviderId($providers),
            'defaultCapabilityLevel' => $this->getDefaultCapabilityLevel(),
            'canEditProviderConfiguration' => $this->canEditProviderConfiguration(),
            'canEditCapabilityLevel' => $this->canEditCapabilityLevel(),
            'capabilityLevels' => $this->getCapabilityLevels(),
            'providers' => array_map(function (AIProvider $provider): array {
                $providerConfiguration = $this->getProviderConfiguration($provider->getId());

                return array_merge($provider->toArray(), [
                    'configuration' => [
                        'hasApiKey' => !empty($providerConfiguration['apiKey']),
                        'endpointUrl' => $providerConfiguration['endpointUrl'],
                        'model' => $providerConfiguration['model'],
                        'isUsable' => $provider->isConfigured($providerConfiguration),
                    ],
                ]);
            }, $providers->getSelectableProviders()),
        ];
    }

    /**
     * Saves the default provider, capability level, and provider connection settings.
     *
     * Both are optional so the form can be saved before any provider is
     * connected: an empty default provider clears it (or falls back to the
     * first usable one), and an empty capability level keeps the stored value.
     *
     * In a managed environment the provider and its credentials
     * are forced from configuration, so nothing is persisted here.
     */
    public function saveSettings(
        AIProvidersList $providers,
        string $defaultProviderId,
        string $defaultCapabilityLevel,
        #[\SensitiveParameter]
        string $providerConfigurationsJson
    ): void {
        if ($this->canEditProviderConfiguration()) {
            $submittedProviderConfigurations = $this->decodeProviderConfigurations($providerConfigurationsJson);
            $this->saveProviderConfigurations($providers, $submittedProviderConfigurations);
            $this->saveDefaultProviderId($providers, $defaultProviderId);
        }

        if ($this->canEditCapabilityLevel() && trim($defaultCapabilityLevel) !== '') {
            $this->saveDefaultCapabilityLevel($defaultCapabilityLevel);
        }
    }

    /**
     * Returns the effective server-side provider configuration including the API key.
     *
     * The returned array contains secrets. It is internal to the AIProviders
     * plugin and its providers and must never be returned from API methods,
     * logged, or exposed to other plugins. Other plugins run completions via
     * {@link \Piwik\Plugins\AIProviders\AIProviderService::complete()} and
     * never see credentials.
     *
     * @internal
     * @return array{apiKey: string, endpointUrl: string, model: string}
     */
    public function getProviderConfiguration(string $providerId): array
    {
        $storedConfiguration = $this->getProviderConfigurations()[$providerId] ?? [
            'apiKey' => '',
            'endpointUrl' => '',
            'model' => '',
        ];
        $configFileConfiguration = $this->getConfigFileProviderConfiguration($providerId);

        return [
            'apiKey' => $configFileConfiguration['apiKey'] !== ''
                ? $configFileConfiguration['apiKey']
                : $storedConfiguration['apiKey'],
            'endpointUrl' => $configFileConfiguration['endpointUrl'] !== ''
                ? $configFileConfiguration['endpointUrl']
                : $storedConfiguration['endpointUrl'],
            'model' => $configFileConfiguration['model'] !== ''
                ? $configFileConfiguration['model']
                : $storedConfiguration['model'],
        ];
    }

    /**
     * Returns a validated server-side provider configuration, optionally using
     * unsaved values from the admin UI for connection testing.
     *
     * @param array<string, mixed> $submittedProviderConfiguration
     * @return array<string, string>
     */
    public function getProviderConfigurationForUse(
        AIProvider $provider,
        #[\SensitiveParameter]
        array $submittedProviderConfiguration = []
    ): array {
        $providerId = $provider->getId();
        // Base the merge on the effective configuration so a connection test
        // exercises what complete() would actually use, including config-file
        // credentials.
        $existingConfiguration = $this->getProviderConfiguration($providerId);
        $submittedProviderConfiguration = array_merge(
            $existingConfiguration,
            $submittedProviderConfiguration
        );

        return [
            'apiKey' => $this->getSubmittedApiKey(
                $submittedProviderConfiguration,
                [$providerId => $existingConfiguration],
                $providerId
            ),
            'endpointUrl' => $this->getSubmittedEndpointUrl($submittedProviderConfiguration, $provider),
            'model' => $this->getSubmittedModel($submittedProviderConfiguration, $provider),
        ];
    }

    /**
     * Removes the database-stored connection settings for a provider.
     *
     * Credentials supplied via the config file or environment (see the class
     * docblock) are not touched: they are not stored in the database and can
     * only be removed where they were defined.
     */
    public function removeProviderConfiguration(string $providerId): void
    {
        $providerConfigurations = $this->getProviderConfigurations();
        unset($providerConfigurations[$providerId]);

        $this->providerCredentials->setValue($providerConfigurations);
        $this->providerCredentials->save();
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public function getCapabilityLevels(): array
    {
        return [
            self::CAPABILITY_INSTANT => [
                'label' => 'AIProviders_InstantCapability',
                'description' => 'AIProviders_InstantCapabilityDescription',
            ],
            self::CAPABILITY_THINKING => [
                'label' => 'AIProviders_ThinkingCapability',
                'description' => 'AIProviders_ThinkingCapabilityDescription',
            ],
        ];
    }

    /**
     * Returns whether provider connections can be edited in the UI.
     *
     * Provider configuration is locked whenever the default provider is forced
     * from configuration (a managed environment).
     */
    public function canEditProviderConfiguration(): bool
    {
        return !$this->isManaged() && $this->defaultProvider->isWritableByCurrentUser();
    }

    public function canEditCapabilityLevel(): bool
    {
        return !$this->isManaged() && $this->defaultCapabilityLevel->isWritableByCurrentUser();
    }

    /**
     * Returns the forced provider ID when running in a managed environment, or
     * null when the default provider may be chosen freely.
     *
     * Trusted callers use this to honour the centrally managed provider even
     * when they request a specific one.
     */
    public function getForcedProviderId(): ?string
    {
        if (!$this->isManaged()) {
            return null;
        }

        $providerId = $this->defaultProvider->getValue();

        return is_string($providerId) && $providerId !== '' ? $providerId : null;
    }

    /**
     * Returns the configured default provider ID, falling back to the first
     * configured provider. Only selectable providers are considered, so a
     * restricted provider can never become the default.
     */
    public function getDefaultProviderId(AIProvidersList $providers): string
    {
        $providerId = $this->defaultProvider->getValue();

        /**
         * When forced from configuration, use the
         * configured provider as-is so misconfiguration surfaces a clear error
         * from the provider rather than silently falling back.
         */
        if ($this->isManaged() && is_string($providerId) && $providerId !== '' && $providers->hasProvider($providerId)) {
            return $providerId;
        }

        if (
            is_string($providerId)
            && $providers->isSelectable($providerId)
            && $this->isProviderUsable($providers->getProvider($providerId))
        ) {
            return $providerId;
        }

        if (
            $providers->isSelectable(self::DEFAULT_PROVIDER_ID)
            && $this->isProviderUsable($providers->getProvider(self::DEFAULT_PROVIDER_ID))
        ) {
            return self::DEFAULT_PROVIDER_ID;
        }

        return $this->getFirstConfiguredProviderId($providers);
    }

    private function saveDefaultProviderId(AIProvidersList $providers, string $providerId): void
    {
        $providerId = trim($providerId);

        if ($providerId === '') {
            $providerId = $this->getFirstConfiguredProviderId($providers);

            if ($providerId === '') {
                $this->defaultProvider->setValue('');
                $this->defaultProvider->save();
                return;
            }
        }

        // Restricted providers are reported as unknown on purpose: admin
        // surfaces must not reveal that they exist.
        if (!$providers->hasProvider($providerId) || !$providers->isSelectable($providerId)) {
            throw new InvalidArgumentException(sprintf('Unknown AI provider "%s".', $providerId));
        }

        if (!$this->isProviderUsable($providers->getProvider($providerId))) {
            throw new InvalidArgumentException(sprintf('AI provider "%s" is not configured.', $providerId));
        }

        $this->defaultProvider->setValue($providerId);
        $this->defaultProvider->save();
    }

    /**
     * Returns the configured default model capability level.
     */
    public function getDefaultCapabilityLevel(): string
    {
        $capabilityLevel = $this->defaultCapabilityLevel->getValue();

        if (!is_string($capabilityLevel) || !array_key_exists($capabilityLevel, $this->getCapabilityLevels())) {
            return self::CAPABILITY_INSTANT;
        }

        return $capabilityLevel;
    }

    private function saveDefaultCapabilityLevel(string $capabilityLevel): void
    {
        $capabilityLevel = trim($capabilityLevel);

        if (!array_key_exists($capabilityLevel, $this->getCapabilityLevels())) {
            throw new InvalidArgumentException(sprintf('Unknown AI model capability level "%s".', $capabilityLevel));
        }

        $this->defaultCapabilityLevel->setValue($capabilityLevel);
        $this->defaultCapabilityLevel->save();
    }

    /**
     * Returns whether the plugin runs in a managed environment, that is, the
     * default provider is forced (and locked) from configuration.
     */
    public function isManaged(): bool
    {
        $config = Config::getInstance()->AIProviders;
        $providerId = is_array($config) ? ($config[self::SETTING_DEFAULT_PROVIDER] ?? null) : null;

        return is_string($providerId) && trim($providerId) !== '';
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getProviderConfigurations(): array
    {
        $rawValue = $this->providerCredentials->getValue();

        if (!is_array($rawValue)) {
            return [];
        }

        $providerConfigurations = [];
        foreach ($rawValue as $providerId => $providerConfiguration) {
            if (!is_string($providerId) || !is_array($providerConfiguration)) {
                continue;
            }

            $providerConfigurations[$providerId] = [
                'apiKey' => isset($providerConfiguration['apiKey']) && is_string($providerConfiguration['apiKey'])
                    ? $providerConfiguration['apiKey']
                    : '',
                'endpointUrl' => isset($providerConfiguration['endpointUrl']) && is_string($providerConfiguration['endpointUrl'])
                    ? $providerConfiguration['endpointUrl']
                    : '',
                'model' => isset($providerConfiguration['model']) && is_string($providerConfiguration['model'])
                    ? $providerConfiguration['model']
                    : '',
            ];
        }

        return $providerConfigurations;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function decodeProviderConfigurations(
        #[\SensitiveParameter]
        string $providerConfigurationsJson
    ): array {
        $decoded = json_decode($providerConfigurationsJson, true);

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Provider configurations must be a JSON object.');
        }

        return $decoded;
    }

    /**
     * Persists the submitted provider connection settings to the database.
     *
     * Only selectable providers are accepted, so restricted providers cannot
     * be configured through the administration flow. The API key fallback
     * reads the stored (database) value on purpose: config-file credentials
     * must never be copied into the database.
     *
     * @param array<string, mixed> $submittedProviderConfigurations
     */
    private function saveProviderConfigurations(
        AIProvidersList $providers,
        #[\SensitiveParameter]
        array $submittedProviderConfigurations
    ): void {
        $existingProviderConfigurations = $this->getProviderConfigurations();
        $providerConfigurations = [];

        foreach ($providers->getSelectableProviders() as $provider) {
            $providerId = $provider->getId();
            $submittedProviderConfiguration = $submittedProviderConfigurations[$providerId] ?? [];

            if (!is_array($submittedProviderConfiguration)) {
                throw new InvalidArgumentException(sprintf('Invalid configuration for AI provider "%s".', $providerId));
            }

            $apiKey = $this->getSubmittedApiKey($submittedProviderConfiguration, $existingProviderConfigurations, $providerId);
            $endpointUrl = $this->getSubmittedEndpointUrl($submittedProviderConfiguration, $provider);
            $model = $this->getSubmittedModel($submittedProviderConfiguration, $provider);

            if ($apiKey === '' && $endpointUrl === '') {
                continue;
            }

            $providerConfigurations[$providerId] = [
                'apiKey' => $apiKey,
                'endpointUrl' => $endpointUrl,
                'model' => $model,
            ];
        }

        $this->providerCredentials->setValue($providerConfigurations);
        $this->providerCredentials->save();
    }

    /**
     * @param array<string, mixed> $submittedProviderConfiguration
     * @param array<string, array<string, string>> $existingProviderConfigurations
     */
    private function getSubmittedApiKey(
        #[\SensitiveParameter]
        array $submittedProviderConfiguration,
        #[\SensitiveParameter]
        array $existingProviderConfigurations,
        string $providerId
    ): string {
        if (
            isset($submittedProviderConfiguration['apiKey'])
            && is_string($submittedProviderConfiguration['apiKey'])
            && trim($submittedProviderConfiguration['apiKey']) !== ''
        ) {
            return trim($submittedProviderConfiguration['apiKey']);
        }

        return $existingProviderConfigurations[$providerId]['apiKey'] ?? '';
    }

    /**
     * @param array<string, mixed> $submittedProviderConfiguration
     */
    private function getSubmittedEndpointUrl(array $submittedProviderConfiguration, AIProvider $provider): string
    {
        if (!$provider->supportsCustomEndpoint()) {
            return '';
        }

        $endpointUrl = isset($submittedProviderConfiguration['endpointUrl'])
            && is_string($submittedProviderConfiguration['endpointUrl'])
            ? trim($submittedProviderConfiguration['endpointUrl'])
            : '';

        if ($endpointUrl === '') {
            return '';
        }

        // Expand provider shorthand (e.g. a bare AWS region) before validating.
        $endpointUrl = $provider->normalizeEndpointUrl($endpointUrl);

        $parsedUrl = parse_url($endpointUrl);
        $scheme = is_array($parsedUrl) ? ($parsedUrl['scheme'] ?? '') : '';

        if (
            !filter_var($endpointUrl, FILTER_VALIDATE_URL)
            || !in_array($scheme, ['http', 'https'], true)
        ) {
            throw new InvalidArgumentException(sprintf(
                'Invalid endpoint URL for AI provider "%s".',
                $provider->getId()
            ));
        }

        return $endpointUrl;
    }

    /**
     * The model only applies to providers with a custom endpoint (the admin
     * picks it from the server's discovered models); it is empty for fixed
     * hosted providers, which use their own default model.
     *
     * @param array<string, mixed> $submittedProviderConfiguration
     */
    private function getSubmittedModel(array $submittedProviderConfiguration, AIProvider $provider): string
    {
        if (!$provider->supportsCustomEndpoint()) {
            return '';
        }

        return isset($submittedProviderConfiguration['model'])
            && is_string($submittedProviderConfiguration['model'])
            ? trim($submittedProviderConfiguration['model'])
            : '';
    }

    private function getFirstConfiguredProviderId(AIProvidersList $providers): string
    {
        foreach ($providers->getSelectableProviders() as $provider) {
            if ($this->isProviderUsable($provider)) {
                return $provider->getId();
            }
        }

        return '';
    }

    /**
     * Returns whether the provider can run completions with its effective
     * configuration (database merged with config file/environment).
     */
    private function isProviderUsable(?AIProvider $provider): bool
    {
        if ($provider === null) {
            return false;
        }

        return $provider->isConfigured($this->getProviderConfiguration($provider->getId()));
    }

    /**
     * Returns whether the given plugin may target a specific provider (and
     * model) per request even though a managed environment forces the default
     * provider. Controlled by the `[AIProviders] providerSelectionAllowlist[]`
     * config entries, which a managed environment keeps in its locked,
     * centrally managed config.
     *
     * This is a policy gate for centrally deployed plugins, not a sandbox:
     * the caller plugin name on an {@link \Piwik\Plugins\AIProviders\AIRequest}
     * is self-declared, and PHP code on the same instance can ultimately not
     * be restrained from anything. The guarantee that matters is that on a
     * managed instance neither this allowlist nor the values an allowlisted
     * plugin passes can be influenced by users (see the hard rule on
     * {@link \Piwik\Plugins\AIProviders\AIRequest::withProviderId()}).
     */
    public function isPluginAllowedToSelectProvider(string $pluginName): bool
    {
        if ($pluginName === '') {
            return false;
        }

        return in_array($pluginName, $this->getProviderSelectionAllowlist(), true);
    }

    /**
     * @return string[]
     */
    private function getProviderSelectionAllowlist(): array
    {
        $config = Config::getInstance()->AIProviders;
        $allowlist = is_array($config) ? ($config[self::CONFIG_PROVIDER_SELECTION_ALLOWLIST] ?? []) : [];

        // A single `providerSelectionAllowlist = "X"` entry (without `[]`)
        // parses as a string; accept it as a one-element list.
        if (is_string($allowlist)) {
            $allowlist = [$allowlist];
        }

        if (!is_array($allowlist)) {
            return [];
        }

        return array_values(array_filter($allowlist, 'is_string'));
    }

    /**
     * @return array{apiKey: string, endpointUrl: string, model: string}
     */
    private function getConfigFileProviderConfiguration(string $providerId): array
    {
        return [
            'apiKey' => $this->getConfigFileValue($providerId, 'ApiKey', 'API_KEY'),
            'endpointUrl' => $this->getConfigFileValue($providerId, 'EndpointUrl', 'ENDPOINT_URL'),
            'model' => $this->getConfigFileValue($providerId, 'Model', 'MODEL'),
        ];
    }

    private function getConfigFileValue(string $providerId, string $configSuffix, string $envSuffix): string
    {
        $diValue = $this->getDiConfigValue($providerId, $configSuffix);
        if ($diValue !== '') {
            return $diValue;
        }

        $config = Config::getInstance()->AIProviders;
        $configKey = $providerId . $configSuffix;

        if (is_array($config) && isset($config[$configKey]) && is_string($config[$configKey]) && trim($config[$configKey]) !== '') {
            return trim($config[$configKey]);
        }

        $envKey = 'MATOMO_AIPROVIDERS_' . strtoupper(str_replace('-', '_', $providerId)) . '_' . $envSuffix;
        $envValue = getenv($envKey);

        if (is_string($envValue) && trim($envValue) !== '') {
            return trim($envValue);
        }

        return '';
    }

    private function getDiConfigValue(string $providerId, string $configSuffix): string
    {
        $diKey = self::PLUGIN_NAME . '.' . $providerId . $configSuffix;
        $container = StaticContainer::getContainer();

        if (!$container->has($diKey)) {
            return '';
        }

        $value = $container->get($diKey);

        return is_string($value) && trim($value) !== '' ? trim($value) : '';
    }
}

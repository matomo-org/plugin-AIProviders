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
use Piwik\Common;
use Piwik\Settings\FieldConfig;
use Piwik\Settings\Plugin\SystemSetting;
use Piwik\Plugins\AIProviders\AIProvidersList;
use Piwik\Plugins\AIProviders\Provider\AIProvider;

class Configuration
{
    public const SETTING_DEFAULT_PROVIDER = 'defaultProvider';
    public const SETTING_DEFAULT_CAPABILITY_LEVEL = 'defaultCapabilityLevel';
    public const SETTING_PROVIDER_CREDENTIALS = 'providerCredentials';

    public const CAPABILITY_INSTANT = 'instant';
    public const CAPABILITY_THINKING = 'thinking';

    private const PLUGIN_NAME = 'AIProviders';
    private const DEFAULT_PROVIDER_ID = 'openai';

    /**
     * Stored default provider ID. Persisted in the plugin settings storage, but
     * not shown on the generic plugin settings page (the settings are not
     * registered in a settings container). The value can be overridden and
     * locked from `config.ini.php`, which is how managed environments such as
     * Matomo Cloud force a provider:
     *
     *     [AIProviders]
     *     defaultProvider = "bedrock"
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
        $providerConfigurations = $this->getProviderConfigurations();

        return [
            'defaultProviderId' => $this->getDefaultProviderId($providers),
            'defaultCapabilityLevel' => $this->getDefaultCapabilityLevel(),
            'canEditProviderConfiguration' => $this->canEditProviderConfiguration(),
            'canEditCapabilityLevel' => $this->canEditCapabilityLevel(),
            'capabilityLevels' => $this->getCapabilityLevels(),
            'providers' => array_map(function (AIProvider $provider) use ($providerConfigurations): array {
                $providerConfiguration = $providerConfigurations[$provider->getId()] ?? [];

                return array_merge($provider->toArray(), [
                    'configuration' => [
                        'hasApiKey' => !empty($providerConfiguration['apiKey']),
                        'endpointUrl' => $providerConfiguration['endpointUrl'] ?? '',
                        'isUsable' => $this->isProviderConfiguredForUse($provider, $providerConfiguration),
                    ],
                ]);
            }, $providers->getProviders()),
        ];
    }

    /**
     * Saves the default provider, capability level, and provider connection settings.
     *
     * In a managed environment (for example Matomo Cloud) the provider and its
     * credentials are forced from configuration, so nothing is persisted here.
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
            $providerConfigurations = $this->saveProviderConfigurations($providers, $submittedProviderConfigurations);
            $this->saveDefaultProviderId($providers, $defaultProviderId, $providerConfigurations);
        }

        if ($this->canEditCapabilityLevel()) {
            $this->saveDefaultCapabilityLevel($defaultCapabilityLevel);
        }
    }

    /**
     * Returns a server-side provider configuration including the API key.
     *
     * This method is intended for PHP services in trusted plugins, not for API
     * responses or browser output.
     *
     * @return array<string, string>
     */
    public function getProviderConfiguration(string $providerId): array
    {
        $providerConfigurations = $this->getProviderConfigurations();

        return $providerConfigurations[$providerId] ?? [
            'apiKey' => '',
            'endpointUrl' => '',
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
        $existingProviderConfigurations = $this->getProviderConfigurations();
        $providerId = $provider->getId();
        $submittedProviderConfiguration = array_merge(
            $existingProviderConfigurations[$providerId] ?? [],
            $submittedProviderConfiguration
        );

        return [
            'apiKey' => $this->getSubmittedApiKey(
                $submittedProviderConfiguration,
                $existingProviderConfigurations,
                $providerId
            ),
            'endpointUrl' => $this->getSubmittedEndpointUrl($submittedProviderConfiguration, $provider),
        ];
    }

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
     * from configuration (a managed environment such as Matomo Cloud).
     */
    public function canEditProviderConfiguration(): bool
    {
        return !$this->isManaged();
    }

    public function canEditCapabilityLevel(): bool
    {
        return !$this->isManaged();
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
     * Returns the configured default provider ID, falling back to the first configured provider.
     */
    public function getDefaultProviderId(AIProvidersList $providers): string
    {
        $providerConfigurations = $this->getProviderConfigurations();
        $providerId = $this->defaultProvider->getValue();

        /**
         * When forced from configuration (for example Matomo Cloud), use the
         * configured provider as-is so misconfiguration surfaces a clear error
         * from the provider rather than silently falling back.
         */
        if ($this->isManaged() && is_string($providerId) && $providerId !== '' && $providers->hasProvider($providerId)) {
            return $providerId;
        }

        if (
            is_string($providerId)
            && $providers->hasProvider($providerId)
            && $this->isProviderConfiguredForUse(
                $providers->getProvider($providerId),
                $providerConfigurations[$providerId] ?? []
            )
        ) {
            return $providerId;
        }

        if (
            $providers->hasProvider(self::DEFAULT_PROVIDER_ID)
            && $this->isProviderConfiguredForUse(
                $providers->getProvider(self::DEFAULT_PROVIDER_ID),
                $providerConfigurations[self::DEFAULT_PROVIDER_ID] ?? []
            )
        ) {
            return self::DEFAULT_PROVIDER_ID;
        }

        return $this->getFirstConfiguredProviderId($providers, $providerConfigurations);
    }

    /**
     * @param array<string, array<string, string>> $providerConfigurations
     */
    private function saveDefaultProviderId(
        AIProvidersList $providers,
        string $providerId,
        array $providerConfigurations
    ): void {
        $providerId = trim($providerId);

        if ($providerId === '') {
            $providerId = $this->getFirstConfiguredProviderId($providers, $providerConfigurations);

            if ($providerId === '') {
                $this->defaultProvider->setValue('');
                $this->defaultProvider->save();
                return;
            }
        }

        if (!$providers->hasProvider($providerId)) {
            throw new InvalidArgumentException(sprintf('Unknown AI provider "%s".', $providerId));
        }

        if (
            !$this->isProviderConfiguredForUse(
                $providers->getProvider($providerId),
                $providerConfigurations[$providerId] ?? []
            )
        ) {
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
    private function isManaged(): bool
    {
        return !$this->defaultProvider->isWritableByCurrentUser();
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
        $decoded = json_decode(Common::unsanitizeInputValue($providerConfigurationsJson), true);

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Provider configurations must be a JSON object.');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $submittedProviderConfigurations
     * @return array<string, array<string, string>>
     */
    private function saveProviderConfigurations(
        AIProvidersList $providers,
        #[\SensitiveParameter]
        array $submittedProviderConfigurations
    ): array {
        $existingProviderConfigurations = $this->getProviderConfigurations();
        $providerConfigurations = [];

        foreach ($providers->getProviders() as $provider) {
            $providerId = $provider->getId();
            $submittedProviderConfiguration = $submittedProviderConfigurations[$providerId] ?? [];

            if (!is_array($submittedProviderConfiguration)) {
                throw new InvalidArgumentException(sprintf('Invalid configuration for AI provider "%s".', $providerId));
            }

            $apiKey = $this->getSubmittedApiKey($submittedProviderConfiguration, $existingProviderConfigurations, $providerId);
            $endpointUrl = $this->getSubmittedEndpointUrl($submittedProviderConfiguration, $provider);

            if ($apiKey === '' && $endpointUrl === '') {
                continue;
            }

            $providerConfigurations[$providerId] = [
                'apiKey' => $apiKey,
                'endpointUrl' => $endpointUrl,
            ];
        }

        $this->providerCredentials->setValue($providerConfigurations);
        $this->providerCredentials->save();

        return $providerConfigurations;
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
     * @param array<string, array<string, string>> $providerConfigurations
     */
    private function getFirstConfiguredProviderId(AIProvidersList $providers, array $providerConfigurations): string
    {
        foreach ($providers->getProviders() as $provider) {
            $providerId = $provider->getId();

            if ($this->isProviderConfiguredForUse($provider, $providerConfigurations[$providerId] ?? [])) {
                return $providerId;
            }
        }

        return '';
    }

    /**
     * @param array<string, string> $providerConfiguration
     */
    private function isProviderConfiguredForUse(?AIProvider $provider, array $providerConfiguration): bool
    {
        if ($provider === null) {
            return false;
        }

        return $provider->isConfigured($providerConfiguration);
    }
}

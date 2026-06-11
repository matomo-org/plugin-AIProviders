<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders;

use Exception;
use Piwik\Container\StaticContainer;
use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugin\API as PluginAPI;
use Piwik\Plugins\AIProviders\AIRequest;
use Piwik\Plugins\AIProviders\Model\Configuration;
use Piwik\Plugins\AIProviders\Provider\AIProvider;

/**
 * Exposes AI provider configuration methods for the Matomo administration UI.
 *
 * @method static \Piwik\Plugins\AIProviders\API getInstance()
 */
class API extends PluginAPI
{
    /**
     * Returns AI provider settings for the administration UI.
     *
     * @return array<string, mixed> Provider metadata and masked configuration values.
     * @throws Exception
     */
    public function getSettings(): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $providers = AIProviders::getAvailableProviders();

        return $this->getConfiguration()->getSettings($providers);
    }

    /**
     * Saves AI provider settings from the administration UI.
     *
     * API keys should be submitted as POST data and are never returned by this
     * API. In a managed environment the provider is
     * forced from configuration, so the submitted default provider, credentials,
     * and capability level are all ignored.
     *
     * @param string $defaultProviderId Provider ID to use by default.
     * @param string $defaultCapabilityLevel Default model capability level.
     * @param string $providerConfigurations JSON object keyed by provider ID
     *                                      with connection settings.
     * @return array<string, mixed> Updated provider metadata and masked configuration values.
     * @throws Exception
     */
    public function saveSettings(
        string $defaultProviderId,
        string $defaultCapabilityLevel,
        #[\SensitiveParameter]
        string $providerConfigurations = '{}'
    ): array {
        Piwik::checkUserHasSuperUserAccess();

        $providers = AIProviders::getAvailableProviders();
        $configuration = $this->getConfiguration();
        $configuration->saveSettings(
            $providers,
            $defaultProviderId,
            $defaultCapabilityLevel,
            $providerConfigurations
        );

        return $configuration->getSettings($providers);
    }

    /**
     * Tests one provider using the submitted connection settings.
     *
     * @param string $providerId Provider ID to test.
     * @param string $providerConfiguration JSON object with unsaved apiKey
     *                                      and endpointUrl values.
     * @return array<string, string> Provider response metadata and completion text.
     * @throws Exception
     */
    public function testConnection(
        string $providerId,
        #[\SensitiveParameter]
        string $providerConfiguration = '{}'
    ): array {
        Piwik::checkUserHasSuperUserAccess();

        $providers = AIProviders::getAvailableProviders();
        $provider = $this->getSelectableProvider($providers, $providerId);
        $configuration = $this->getConfiguration()->getProviderConfigurationForUse(
            $provider,
            $this->decodeProviderConfiguration($providerConfiguration)
        );

        $request = (new AIRequest('why is the sky blue, answer in 7 words', 'AIProviders'))
            ->withFeatureKey('test-connection')
            ->withMaxTokens(32);

        return $this->getAIProviderService()
            ->testProviderConnection($provider, $configuration, $request)
            ->toArray();
    }

    /**
     * Removes a stored provider connection.
     *
     * @param string $providerId Provider ID to disconnect.
     * @return array<string, mixed> Updated provider metadata and masked configuration values.
     * @throws Exception
     */
    public function disconnectProvider(string $providerId): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $providers = AIProviders::getAvailableProviders();
        $this->getSelectableProvider($providers, $providerId);

        $configuration = $this->getConfiguration();
        $configuration->removeProviderConfiguration($providerId);

        return $configuration->getSettings($providers);
    }

    private function getConfiguration(): Configuration
    {
        return StaticContainer::get(Configuration::class);
    }

    private function getAIProviderService(): AIProviderService
    {
        return StaticContainer::get(AIProviderService::class);
    }

    /**
     * Resolves a provider for the administration endpoints. Restricted
     * providers (registered as non-selectable by a managed environment) are
     * reported as unknown on purpose, so admin surfaces neither reveal nor
     * operate on them.
     */
    private function getSelectableProvider(AIProvidersList $providers, string $providerId): AIProvider
    {
        $provider = $providers->getProvider($providerId);

        if ($provider === null || !$providers->isSelectable($providerId)) {
            throw new \InvalidArgumentException(sprintf('Unknown AI provider "%s".', $providerId));
        }

        return $provider;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeProviderConfiguration(
        #[\SensitiveParameter]
        string $providerConfigurationJson
    ): array {
        $decoded = json_decode(Common::unsanitizeInputValue($providerConfigurationJson), true);

        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('Provider configuration must be a JSON object.');
        }

        return $decoded;
    }
}

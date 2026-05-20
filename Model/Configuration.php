<?php

/**
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE:  All information contained herein is, and remains the property of InnoCraft Ltd.
 * The intellectual and technical concepts contained herein are protected by trade secret or copyright law.
 * Redistribution of this information or reproduction of this material is strictly forbidden
 * unless prior written permission is obtained from InnoCraft Ltd.
 *
 * You shall use this code only in accordance with the license agreement obtained from InnoCraft Ltd.
 *
 * @link https://www.innocraft.com/
 * @license For license details see https://www.innocraft.com/license
 */

declare(strict_types=1);

namespace Piwik\Plugins\AIProviders\Model;

use InvalidArgumentException;
use Piwik\Option;
use Piwik\Plugin\Manager;
use Piwik\Plugins\AIProviders\AIProvidersList;
use Piwik\Plugins\AIProviders\Provider\AIProvider;

class Configuration
{
    public const OPTION_DEFAULT_PROVIDER_ID = 'AIProviders.defaultProviderId';
    public const OPTION_DEFAULT_CAPABILITY_LEVEL = 'AIProviders.defaultCapabilityLevel';
    public const OPTION_PROVIDER_CONFIGURATIONS = 'AIProviders.providerConfigurations';

    public const CAPABILITY_INSTANT = 'instant';
    public const CAPABILITY_THINKING = 'thinking';

    private const DEFAULT_PROVIDER_ID = 'openai';

    /**
     * Returns masked AI provider settings for the administration UI.
     *
     * @return array<string, mixed> Provider metadata and masked configuration values.
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
                    ],
                ]);
            }, $providers->getProviders()),
        ];
    }

    /**
     * Saves the default provider, capability level, and provider connection settings.
     *
     * On Matomo Cloud, provider connection settings are managed outside this
     * plugin, so only the default provider is persisted.
     */
    public function saveSettings(
        AIProvidersList $providers,
        string $defaultProviderId,
        string $defaultCapabilityLevel,
        #[\SensitiveParameter]
        string $providerConfigurationsJson
    ): void {
        $this->saveDefaultProviderId($providers, $defaultProviderId);

        if (!$this->canEditCapabilityLevel()) {
            return;
        }

        $this->saveDefaultCapabilityLevel($defaultCapabilityLevel);

        if (!$this->canEditProviderConfiguration()) {
            return;
        }

        $submittedProviderConfigurations = $this->decodeProviderConfigurations($providerConfigurationsJson);
        $this->saveProviderConfigurations($providers, $submittedProviderConfigurations);
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
     * @return array<string, string>
     */
    public function getCapabilityLevels(): array
    {
        return [
            self::CAPABILITY_INSTANT => 'AIProviders_InstantCapability',
            self::CAPABILITY_THINKING => 'AIProviders_ThinkingCapability',
        ];
    }

    public function canEditProviderConfiguration(): bool
    {
        return !Manager::getInstance()->isPluginActivated('Cloud');
    }

    public function canEditCapabilityLevel(): bool
    {
        return !Manager::getInstance()->isPluginActivated('Cloud');
    }

    /**
     * Returns the configured default provider ID, falling back to the first available provider.
     */
    public function getDefaultProviderId(AIProvidersList $providers): string
    {
        $providerId = Option::get(self::OPTION_DEFAULT_PROVIDER_ID);

        if (!is_string($providerId) || !$providers->hasProvider($providerId)) {
            return $providers->hasProvider(self::DEFAULT_PROVIDER_ID)
                ? self::DEFAULT_PROVIDER_ID
                : $this->getFirstProviderId($providers);
        }

        return $providerId;
    }

    private function saveDefaultProviderId(AIProvidersList $providers, string $providerId): void
    {
        $providerId = trim($providerId);

        if (!$providers->hasProvider($providerId)) {
            throw new InvalidArgumentException(sprintf('Unknown AI provider "%s".', $providerId));
        }

        Option::set(self::OPTION_DEFAULT_PROVIDER_ID, $providerId);
    }

    /**
     * Returns the configured default model capability level.
     */
    public function getDefaultCapabilityLevel(): string
    {
        $capabilityLevel = Option::get(self::OPTION_DEFAULT_CAPABILITY_LEVEL);

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

        Option::set(self::OPTION_DEFAULT_CAPABILITY_LEVEL, $capabilityLevel);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getProviderConfigurations(): array
    {
        $rawValue = Option::get(self::OPTION_PROVIDER_CONFIGURATIONS);

        if (!is_string($rawValue) || $rawValue === '') {
            return [];
        }

        $decoded = json_decode($rawValue, true);

        if (!is_array($decoded)) {
            return [];
        }

        $providerConfigurations = [];
        foreach ($decoded as $providerId => $providerConfiguration) {
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
        $decoded = json_decode($providerConfigurationsJson, true);

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Provider configurations must be a JSON object.');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $submittedProviderConfigurations
     */
    private function saveProviderConfigurations(
        AIProvidersList $providers,
        #[\SensitiveParameter]
        array $submittedProviderConfigurations
    ): void {
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

        $encodedProviderConfigurations = json_encode($providerConfigurations);

        if (!is_string($encodedProviderConfigurations)) {
            throw new InvalidArgumentException('Provider configurations could not be encoded.');
        }

        Option::set(self::OPTION_PROVIDER_CONFIGURATIONS, $encodedProviderConfigurations);
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

    private function getFirstProviderId(AIProvidersList $providers): string
    {
        $availableProviders = $providers->getProviders();

        if (empty($availableProviders)) {
            throw new InvalidArgumentException('No AI providers are available.');
        }

        return $availableProviders[0]->getId();
    }
}

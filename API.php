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

namespace Piwik\Plugins\AIProviders;

use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugin\API as PluginAPI;
use Piwik\Plugins\AIProviders\Model\Configuration;

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
     * API keys are accepted only as POST data and are never returned by this API.
     * On Matomo Cloud, provider credentials and capability level are ignored so
     * only the default provider can be changed.
     *
     * @param string $defaultProviderId Provider ID to use by default.
     * @param string $defaultCapabilityLevel Default model capability level.
     * @param string $providerConfigurations JSON object keyed by provider ID with connection settings.
     * @return array<string, mixed> Updated provider metadata and masked configuration values.
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

    private function getConfiguration(): Configuration
    {
        return StaticContainer::get(Configuration::class);
    }
}

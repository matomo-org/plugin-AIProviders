<?php

/**
 * Copyright (C) InnoCraft Ltd - All rights reserved.
 *
 * NOTICE: All information contained herein is, and remains the property of InnoCraft Ltd.
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

use Piwik\Piwik;
use Piwik\Plugin;

class AIProviders extends Plugin
{
    public function registerEvents(): array
    {
        return [
            'AIProviders.addAIProviders'             => 'addAIProviders',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
        ];
    }

    public function addAIProviders(AIProvidersList $providers): void
    {
        $providers->addProvider(new Provider\Claude());
        $providers->addProvider(new Provider\Gemini());
        $providers->addProvider(new Provider\OpenAI());
        $providers->addProvider(new Provider\CustomProvider());
    }

    /**
     * Returns all AI providers registered by active plugins.
     */
    public static function getAvailableProviders(): AIProvidersList
    {
        $providers = new AIProvidersList();

        /**
         * Triggered to let plugins register AI providers.
         *
         * Plugins can add providers by calling `$providers->addProvider()` with
         * a `Piwik\Plugins\AIProviders\Provider\AIProvider` instance.
         *
         * **Example**
         *
         *     public function registerEvents()
         *     {
         *         return ['AIProviders.addAIProviders' => 'addAIProviders'];
         *     }
         *
         *     public function addAIProviders(AIProvidersList $providers): void
         *     {
         *         $providers->addProvider(new MyProvider());
         *     }
         *
         * @param AIProvidersList $providers Provider registry to mutate.
         */
        Piwik::postEvent('AIProviders.addAIProviders', [$providers]);

        /**
         * Triggered after providers have been registered, so plugins can remove
         * or adjust providers before they are shown or used.
         *
         * @param AIProvidersList $providers Provider registry to mutate.
         */
        Piwik::postEvent('AIProviders.filterAIProviders', [$providers]);

        return $providers;
    }

    public function getClientSideTranslationKeys(array &$translations): void
    {
        $translations[] = 'AIProviders_ApiKey';
        $translations[] = 'AIProviders_ApiKeyAlreadyConfiguredPlaceholder';
        $translations[] = 'AIProviders_ApiKeyPlaceholder';
        $translations[] = 'AIProviders_ClaudeDescription';
        $translations[] = 'AIProviders_CloudConfigurationHelp';
        $translations[] = 'AIProviders_ConfigurationIntro';
        $translations[] = 'AIProviders_CustomProviderDescription';
        $translations[] = 'AIProviders_DefaultBadge';
        $translations[] = 'AIProviders_DefaultCapabilityLevel';
        $translations[] = 'AIProviders_DefaultCapabilityLevelHelp';
        $translations[] = 'AIProviders_DefaultProvider';
        $translations[] = 'AIProviders_DefaultProviderHelp';
        $translations[] = 'AIProviders_DefaultsTitle';
        $translations[] = 'AIProviders_Disconnect';
        $translations[] = 'AIProviders_Disconnecting';
        $translations[] = 'AIProviders_DisconnectSuccess';
        $translations[] = 'AIProviders_EndpointUrl';
        $translations[] = 'AIProviders_EndpointUrlPlaceholder';
        $translations[] = 'AIProviders_GeminiDescription';
        $translations[] = 'AIProviders_InstantCapability';
        $translations[] = 'AIProviders_InstantCapabilityDescription';
        $translations[] = 'AIProviders_MenuTitle';
        $translations[] = 'AIProviders_NoDefaultProviderWarning';
        $translations[] = 'AIProviders_OpenAIDescription';
        $translations[] = 'AIProviders_RequestFailed';
        $translations[] = 'AIProviders_SelectedConfiguration';
        $translations[] = 'AIProviders_SettingsSaveSuccess';
        $translations[] = 'AIProviders_StatusConnected';
        $translations[] = 'AIProviders_StatusNotConnected';
        $translations[] = 'AIProviders_TestConnection';
        $translations[] = 'AIProviders_TestConnectionSuccess';
        $translations[] = 'AIProviders_TestingConnection';
        $translations[] = 'AIProviders_ThinkingCapability';
        $translations[] = 'AIProviders_ThinkingCapabilityDescription';
        $translations[] = 'General_Cancel';
        $translations[] = 'General_LoadingData';
    }
}

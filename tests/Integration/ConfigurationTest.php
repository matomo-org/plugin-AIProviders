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

namespace Piwik\Plugins\AIProviders\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Option;
use Piwik\Plugins\AIProviders\API;
use Piwik\Plugins\AIProviders\AIProviderService;
use Piwik\Plugins\AIProviders\Model\Configuration;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group AIProviders
 * @group AIProvidersConfiguration
 * @group Plugins
 */
class ConfigurationTest extends IntegrationTestCase
{
    /**
     * @var API
     */
    private $api;

    public function setUp(): void
    {
        parent::setUp();

        Option::delete(Configuration::OPTION_DEFAULT_PROVIDER_ID);
        Option::delete(Configuration::OPTION_DEFAULT_CAPABILITY_LEVEL);
        Option::delete(Configuration::OPTION_PROVIDER_CONFIGURATIONS);

        Fixture::createSuperUser();
        $this->setSuperUser();

        $this->api = API::getInstance();
    }

    public function testGetSettingsReturnsDefaultProviderConfiguration(): void
    {
        $settings = $this->api->getSettings();

        $this->assertSame('openai', $settings['defaultProviderId']);
        $this->assertSame(Configuration::CAPABILITY_INSTANT, $settings['defaultCapabilityLevel']);
        $this->assertTrue($settings['canEditProviderConfiguration']);
        $this->assertTrue($settings['canEditCapabilityLevel']);
        $this->assertCount(4, $settings['providers']);

        $customProvider = $this->getProvider($settings, 'custom-provider');
        $this->assertSame('Custom Provider', $customProvider['name']);
        $this->assertTrue($customProvider['supportsCustomEndpoint']);
    }

    public function testSaveSettingsStoresApiKeyWithoutReturningIt(): void
    {
        $settings = $this->api->saveSettings(
            'claude',
            Configuration::CAPABILITY_THINKING,
            (string) json_encode([
                'claude' => [
                    'apiKey' => 'secret-claude-key',
                    'endpointUrl' => '',
                ],
            ])
        );

        $this->assertSame('claude', $settings['defaultProviderId']);
        $this->assertSame(Configuration::CAPABILITY_THINKING, $settings['defaultCapabilityLevel']);

        $claude = $this->getProvider($settings, 'claude');
        $this->assertTrue($claude['configuration']['hasApiKey']);
        $this->assertArrayNotHasKey('apiKey', $claude['configuration']);
        $this->assertStringNotContainsString('secret-claude-key', (string) json_encode($settings));
    }

    public function testSaveSettingsPreservesExistingApiKeyWhenInputIsEmpty(): void
    {
        $this->api->saveSettings(
            'claude',
            Configuration::CAPABILITY_THINKING,
            (string) json_encode([
                'claude' => [
                    'apiKey' => 'secret-claude-key',
                    'endpointUrl' => '',
                ],
            ])
        );

        $settings = $this->api->saveSettings(
            'openai',
            Configuration::CAPABILITY_INSTANT,
            (string) json_encode([
                'claude' => [
                    'apiKey' => '',
                    'endpointUrl' => '',
                ],
            ])
        );

        $claude = $this->getProvider($settings, 'claude');
        $this->assertTrue($claude['configuration']['hasApiKey']);
    }

    public function testServiceReturnsServerSideDefaultProviderConfiguration(): void
    {
        $this->api->saveSettings(
            'claude',
            Configuration::CAPABILITY_THINKING,
            (string) json_encode([
                'claude' => [
                    'apiKey' => 'secret-claude-key',
                    'endpointUrl' => '',
                ],
            ])
        );

        $service = StaticContainer::get(AIProviderService::class);

        $this->assertSame('claude', $service->getDefaultProvider()->getId());
        $this->assertSame(Configuration::CAPABILITY_THINKING, $service->getDefaultCapabilityLevel());
        $this->assertSame('secret-claude-key', $service->getDefaultProviderConfiguration()['apiKey']);
    }

    public function testSaveSettingsRejectsUnknownProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown AI provider');

        $this->api->saveSettings(
            'unknown-provider',
            Configuration::CAPABILITY_INSTANT,
            '{}'
        );
    }

    public function testGetSettingsRequiresSuperUserAccess(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('checkUserHasSuperUserAccess');

        $this->setAnonymousUser();
        $this->api->getSettings();
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function getProvider(array $settings, string $providerId): array
    {
        foreach ($settings['providers'] as $provider) {
            if ($provider['id'] === $providerId) {
                return $provider;
            }
        }

        $this->fail(sprintf('Provider "%s" was not found.', $providerId));
    }

    private function setSuperUser(): void
    {
        FakeAccess::clearAccess(true);
    }

    private function setAnonymousUser(): void
    {
        FakeAccess::clearAccess();
        FakeAccess::$identity = 'anonymous';
    }

    public function provideContainerConfig(): array
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }
}
